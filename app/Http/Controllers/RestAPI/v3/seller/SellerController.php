<?php

namespace App\Http\Controllers\RestAPI\v3\seller;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Seller;
use App\Models\SellerWallet;
use App\Models\Shop;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawRequest;
use App\Models\User;
use App\Repositories\OrderTransactionRepository;
use App\Services\DashboardService;
use App\Utils\BackEndHelper;
use App\Utils\Convert;
use App\Utils\Helpers;
use App\Utils\ImageManager;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Boolean;

class SellerController extends Controller
{
    public function __construct(
        private readonly OrderTransactionRepository $orderTransactionRepo,
        private readonly DashboardService           $dashboardService,
    )
    {
    }

    public function shop_info(Request $request)
    {
        $seller = $request->seller;

        $product_ids = Product::where(['user_id' => $seller['id'], 'added_by' => 'seller'])->pluck('id')->toArray();
        $shop = Shop::where(['seller_id' => $seller['id']])->first();
        $shop['rating'] = round(Review::whereIn('product_id', $product_ids)->avg('rating'), 3);
        $shop['rating_count'] = Review::whereIn('product_id', $product_ids)->count();

        return response()->json($shop, 200);
    }

    public function seller_delivery_man(Request $request)
    {
        $seller = $request->seller;
        $delivery_men = DeliveryMan::where(['seller_id' => $seller['id']])->get();

        return response()->json($delivery_men, 200);
    }

    public function shop_product_reviews(Request $request)
    {
        $seller = $request->seller;
        $product_ids = Product::where(['user_id' => $seller['id'], 'added_by' => 'seller'])->pluck('id')->toArray();


        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $product_id = Product::where('added_by', 'seller')->where('user_id', $seller->id)->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
            })->pluck('id')->toArray();

            $customer_id = User::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%");
                }
            })->pluck('id')->toArray();

            $reviews = Review::whereHas('product', function ($query) use ($seller) {
                    $query->where('added_by', 'seller')->where('user_id', $seller->id);
                })
                ->with(['product', 'reply'])
                ->where(function ($q) use ($product_id, $customer_id) {
                    $q->whereIn('product_id', $product_id)->orWhereIn('customer_id', $customer_id);
                });

            $query_param = ['search' => $request['search']];
        } else {
            $reviews = Review::with(['product', 'customer', 'reply'])->whereHas('product', function ($query) use ($seller) {
                $query->where('user_id', $seller->id)->where('added_by', 'seller');
            })
                ->when(($request['product_id'] != null && $request['product_id'] != 0), function ($query) use ($request) {
                    $query->where('product_id', $request['product_id']);
                })
                ->when($request->customer_id != null, function ($query) use ($request) {
                    $query->where('customer_id', $request->customer_id);
                })
                ->when($request->status != null, function ($query) use ($request) {
                    $query->where('status', $request->status);
                })
                ->when($request->from && $request->to, function ($query) use ($request) {
                    $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
                });
        }
        $reviews = $reviews->latest()->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $reviews->map(function ($data) {
            $data['attachment_full_url'] = $data->attachment_full_url;
            $data['product'] = Helpers::product_data_formatting($data['product']);
            return $data;
        });

        return response()->json([
            'total_size' => $reviews->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'reviews' => $reviews->items()
        ], 200);
    }

    public function shopProductReviewReply(Request $request): JsonResponse
    {
        $seller = $request->seller;
        $review = ReviewReply::where(['review_id' => $request['review_id'], 'added_by' => 'seller', 'added_by_id' => $seller['id']])->first();
        if (!$review) {
            ReviewReply::insert([
                'review_id' => $request['review_id'],
                'added_by' => 'seller',
                'added_by_id' => $seller['id'],
                'reply_text' => $request['reply_text'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            ReviewReply::where([
                'review_id' => $request['review_id'],
                'added_by' => 'seller',
                'added_by_id' => $seller['id']
            ])->update([
                'reply_text' => $request['reply_text'],
                'updated_at' => Carbon::now(),
            ]);
        }

        return response()->json(['message' => translate('Review_reply_successfully')], 200);
    }

    public function shop_product_reviews_status(Request $request)
    {
        $reviews = Review::find($request->id);
        $reviews->status = $request->status;
        $reviews->save();
        return response()->json(['message' => translate('status updated successfully!!')], 200);
    }

    public function getSellerInfo(Request $request): JsonResponse
    {
        $seller = $request->seller;
        $data = Seller::with(['wallet'])->withCount(['product', 'orders' => function ($query) use ($seller) {
            $query->where(['seller_id' => $seller['id'], 'seller_is' => ($seller['id'] == 0 ? 'admin' : 'seller')]);
        }])->find($seller['id']);

        $data['free_delivery_features_status'] = getWebConfig(name: 'free_delivery_status');
        $data['free_delivery_responsibility'] = getWebConfig(name: 'free_delivery_responsibility');
        $data['minimum_order_amount_by_seller'] = getWebConfig(name: 'minimum_order_amount_by_seller');
        $data['minimum_order_amount'] = \App\Utils\Convert::default($data->minimum_order_amount);
        $data['free_delivery_over_amount'] = \App\Utils\Convert::default($data->free_delivery_over_amount);

        return response()->json($data, 200);
    }

    public function shop_info_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'mimes: jpg,jpeg,png,gif',
            'banner' => 'mimes: jpg,jpeg,png,gif',
            'bottom_banner' => 'mimes: jpg,jpeg,png,gif',
            'offer_banner' => 'mimes: jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $seller = $request->seller;

        $shop = Shop::where(['seller_id' => $seller['id']])->first();

        $old_bottom_banner = $shop->bottom_banner;
        $image = $request->file('bottom_banner');
        if ($image != null) {
            $bottom_banner = ImageManager::upload('shop/banner/', 'webp', $request->file('bottom_banner'));
        } else {
            $bottom_banner = $old_bottom_banner;
        }

        $old_offer_banner = $shop->offer_banner;
        $image = $request->file('offer_banner');
        if ($image != null) {
            $offer_banner = ImageManager::upload('shop/banner/', 'webp', $request->file('offer_banner'));
        } else {
            $offer_banner = $old_offer_banner;
        }

        $old_banner = $shop->banner;
        $image = $request->file('banner');
        if ($image != null) {
            $banner = ImageManager::upload('shop/banner/', 'webp', $request->file('banner'));
        } else {
            $banner = $old_banner;
        }

        $old_image = $shop->image;
        $image = $request->file('logo');
        if ($image != null) {
            $imageName = ImageManager::update('shop/', $old_image, 'webp', $request->file('logo'));
        } else {
            $imageName = $old_image;
        }

        if ($request->has('minimum_order_amount')) {
            Seller::where(['id' => $seller['id']])->update([
                'minimum_order_amount' => BackEndHelper::currency_to_usd($request['minimum_order_amount'])
            ]);
        }

        if ($request->has('free_delivery_status')) {
            Seller::where(['id' => $seller['id']])->update([
                'free_delivery_status' => $request['free_delivery_status']
            ]);
        }

        if ($request->has('free_delivery_over_amount')) {
            Seller::where(['id' => $seller['id']])->update([
                'free_delivery_over_amount' => BackEndHelper::currency_to_usd($request['free_delivery_over_amount'])
            ]);
        }

        Shop::where(['seller_id' => $seller['id']])->update([
            'name' => $request['name'],
            'address' => $request['address'],
            'contact' => $request['contact'],
            'image' => $imageName,
            'banner' => $banner,
            'bottom_banner' => $bottom_banner,
            'offer_banner' => $offer_banner,
            'updated_at' => now()
        ]);

        return response()->json(translate('Shop info updated successfully!'), 200);
    }

    public function seller_info_update(Request $request)
    {
        $seller = $request->seller;

        $old_image = Seller::where(['id' => $seller['id']])->first()->image;
        $image = $request->file('image');
        if ($image != null) {
            $imageName = ImageManager::update('seller/', $old_image, 'webp', $request->file('image'));
        } else {
            $imageName = $old_image;
        }

        Seller::where(['id' => $seller['id']])->update([
            'f_name' => $request['f_name'],
            'l_name' => $request['l_name'],
            'bank_name' => $request['bank_name'],
            'branch' => $request['branch'],
            'account_no' => $request['account_no'],
            'holder_name' => $request['holder_name'],
            'phone' => $request['phone'],
            'password' => $request['password'] != null ? bcrypt($request['password']) : Seller::where(['id' => $seller['id']])->first()->password,
            'image' => $imageName,
            'updated_at' => now()
        ]);

        if ($request['password'] != null) {
            Seller::where(['id' => $seller['id']])->update([
                'auth_token' => Str::random('50')
            ]);
        }

        return response()->json(translate('Info updated successfully!'), 200);
    }

    public function withdraw_method_list(Request $request)
    {
        $methods = WithdrawalMethod::ofStatus(1)->get();

        return response()->json($methods, 200);
    }

    public function withdraw_request(Request $request)
    {
        $method = WithdrawalMethod::find($request['withdraw_method_id']);
        $fields = array_column($method->method_fields, 'input_name');
        $values = $request->all();

        $data['method_name'] = $method->method_name;
        foreach ($fields as $field) {
            if (key_exists($field, $values)) {
                $data[$field] = $values[$field];
            }
        }

        $seller = $request->seller;

        $wallet = SellerWallet::where('seller_id', $seller['id'])->first();
        if (($wallet->total_earning) >= Convert::usd($request['amount']) && $request['amount'] > 1) {
            DB::table('withdraw_requests')->insert([
                'seller_id' => $seller['id'],
                'amount' => Convert::usd($request['amount']),
                'transaction_note' => null,
                'withdrawal_method_id' => $request['withdraw_method_id'],
                'withdrawal_method_fields' => json_encode($data),
                'approved' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $wallet->total_earning -= BackEndHelper::currency_to_usd($request['amount']);
            $wallet->pending_withdraw += BackEndHelper::currency_to_usd($request['amount']);
            $wallet->save();
            return response()->json(translate('Withdraw request sent successfully!'), 200);
        }
        return response()->json(['message' => translate('Invalid withdraw request')], 400);
    }

    public function close_withdraw_request(Request $request)
    {
        $seller = $request->seller;

        $withdraw_request = WithdrawRequest::find($request['id']);
        $wallet = SellerWallet::where('seller_id', $seller['id'])->first();

        if (isset($withdraw_request) && $withdraw_request->approved == 0) {
            $wallet->total_earning += BackEndHelper::currency_to_usd($withdraw_request['amount']);
            $wallet->pending_withdraw -= BackEndHelper::currency_to_usd($request['amount']);
            $wallet->save();
            $withdraw_request->delete();
            return response()->json(translate('Withdraw request has been closed successfully!'), 200);
        }

        return response()->json(translate('Withdraw request is invalid'), 400);
    }

    public function transaction(Request $request)
    {
        $status = $request->status;
        if ($status == 'pending') {
            $status = 0;
        } elseif ($status == 'approve') {
            $status = 1;
        } elseif ($status == 'deny') {
            $status = 2;
        }

        $seller = $request->seller;
        $transaction = WithdrawRequest::where('seller_id', $seller['id'])
            ->when(in_array($status, ['0', 1, 2]), function ($query) use ($status) {
                $query->where('approved', $status);
            })
            ->when(($request->from && $request->to), function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            })
            ->latest()->get();

        return response()->json($transaction, 200);
    }

    public function monthly_earning(Request $request)
    {
        $seller = $request->seller;
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');
        $seller_data = '';
        $seller_earnings = OrderTransaction::where([
            'seller_is' => 'seller',
            'seller_id' => $seller['id'],
            'status' => 'disburse'
        ])->select(
            DB::raw('IFNULL(sum(seller_amount),0) as sums'),
            DB::raw('YEAR(created_at) year, MONTH(created_at) month')
        )->whereBetween('created_at', [$from, $to])->groupby('year', 'month')->get()->toArray();
        for ($inc = 1; $inc <= 12; $inc++) {
            $default = 0;
            foreach ($seller_earnings as $match) {
                if ($match['month'] == $inc) {
                    $default = $match['sums'];
                }
            }
            $seller_data .= $default . ',';
        }

        return response()->json($seller_data, 200);
    }

    public function monthly_commission_given(Request $request)
    {
        $seller = $request->seller;
        $from = Carbon::now()->startOfYear()->format('Y-m-d');
        $to = Carbon::now()->endOfYear()->format('Y-m-d');

        $commission_data = '';
        $commission_earnings = OrderTransaction::where([
            'seller_is' => 'seller',
            'seller_id' => $seller['id'],
            'status' => 'disburse'
        ])->select(
            DB::raw('IFNULL(sum(admin_commission),0) as sums'),
            DB::raw('YEAR(created_at) year, MONTH(created_at) month')
        )->whereBetween('created_at', [$from, $to])->groupby('year', 'month')->get()->toArray();
        for ($inc = 1; $inc <= 12; $inc++) {
            $default = 0;
            foreach ($commission_earnings as $match) {
                if ($match['month'] == $inc) {
                    $default = $match['sums'];
                }
            }
            $commission_data .= $default . ',';
        }

        return response()->json($commission_data, 200);
    }

    public function update_cm_firebase_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cm_firebase_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }
        $seller = $request->seller;

        DB::table('sellers')->where('id', $seller->id)->update([
            'cm_firebase_token' => $request['cm_firebase_token'],
        ]);

        return response()->json(['message' => translate('successfully updated!')], 200);
    }

    public function account_delete(Request $request)
    {
        $seller = $request->seller;
        if ($this->getCountOfOngoingOrderStatus(sellerId: $request->seller->id) > 0) {
            return response()->json(['status' => 'error', 'key' => 'ongoing_order_left', 'message' => translate('please_make_sure_you_don`t_have_any_ongoing_order')], 403);
        }

        if (!$this->checkAdminCommissionAmountClearance(sellerId: $request->seller->id)) {
            return response()->json(['status' => 'error', 'key' => 'admin_commission_not_paid', 'message' => translate('please_clear_all_the_transaction_with_admin')], 403);
        }

        if ($this->getCountOfDeliveryManTransactionNotClearWithSeller(sellerId: $request->seller->id) > 0) {
            return response()->json(['status' => 'error', 'key' => 'delivery_man_transaction_left', 'message' => translate('please_clear_all_the_transaction_with_delivery_man')], 403);
        }
        if ($seller->id) {
            Coupon::where(['coupon_bearer' => 'seller', 'seller_id' => $seller->id])->delete();
            ImageManager::delete('/seller/' . $seller['image']);
            $seller->delete();
            return response()->json(['message' => translate('Your_account_deleted_successfully!!')], 200);
        } else {
            return response()->json(['message' => 'access_denied!!'], 403);
        }
    }

    protected function checkAdminCommissionAmountClearance($sellerId)
    {
        $adminCommission = OrderTransaction::where(['seller_is' => 'seller', 'seller_id' => $sellerId])->sum('admin_commission') ?? 0;
        $sellerGivenToAdmin = SellerWallet::where('seller_id', $sellerId)->first()->admin_commission ?? 0;
        return $adminCommission >= $sellerGivenToAdmin;
    }

    protected function getCountOfDeliveryManTransactionNotClearWithSeller($sellerId): int
    {
        return DeliveryMan::with('wallet')->whereHas('wallet', function ($query) {
            return $query->where('current_balance', '!=', 0)->where('cash_in_hand', '!=', 0);
        })->where('seller_id', $sellerId)->get()->count();
    }

    protected function getCountOfOngoingOrderStatus($sellerId): int
    {
        return Order::whereIn('order_status', ['pending', 'confirmed', 'out_for_delivery', 'processing'])->where(['seller_is' => 'seller', 'seller_id' => $sellerId])->get()->count();
    }

    protected function getVendorEarning(object|array $seller, string|Carbon $from, string|Carbon $to, array $range, string $type): array
    {
        $vendorEarnings = $this->orderTransactionRepo->getListWhereBetween(
            filters: [
                'seller_is' => 'seller',
                'seller_id' => $seller?->id,
                'status' => 'disburse',
            ],
            selectColumn: 'seller_amount',
            whereBetween: 'created_at',
            whereBetweenFilters: [$from, $to],
            groupBy:  $type,
        );
        return $this->dashboardService->getDateWiseAmountInUSD(range: $range, type: $type, amountArray: $vendorEarnings);
    }

    protected function getAdminCommission(object|array $seller, string|Carbon $from, string|Carbon $to, array $range, string $type): array
    {
        $commissionGiven = $this->orderTransactionRepo->getListWhereBetween(
            filters: [
                'seller_is' => 'seller',
                'seller_id' => $seller?->id,
                'status' => 'disburse',
            ],
            selectColumn: 'admin_commission',
            whereBetween: 'created_at',
            whereBetweenFilters: [$from, $to],
            groupBy:  $type,
        );
        return $this->dashboardService->getDateWiseAmountInUSD(range: $range, type: $type, amountArray: $commissionGiven);;
    }

    public function getEarningStatics(Request $request): JsonResponse
    {
        $dateType = $request['type'];
        $dateTypeArray = $this->dashboardService->getDateTypeData(dateType: $dateType);
        $from = $dateTypeArray['from'];
        $to = $dateTypeArray['to'];
        $type = $dateTypeArray['type'];
        $range = $dateTypeArray['range'];
        $vendorEarning = $this->getVendorEarning(seller: $request->seller, from: $from, to: $to, range: $range, type: $type);
        $commissionEarn = $this->getAdminCommission(seller: $request->seller, from: $from, to: $to, range: $range, type: $type);
        $vendorEarning = array_values($vendorEarning);
        $commissionEarn = array_values($commissionEarn);

        return response()->json([
            'seller_earn' => $vendorEarning,
            'commission_earn' => $commissionEarn
        ], 200);
    }

    public function order_statistics(Request $request)
    {
        $seller = $request->seller;
        $today = $request->statistics_type == 'today' ? 1 : 0;
        $this_month = $request->statistics_type == 'this_month' ? 1 : 0;

        $pending = Order::where(['seller_is' => 'seller'])->where(['seller_id' => $seller->id])->where(['order_status' => 'pending'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $confirmed = Order::where(['seller_is' => 'seller'])->where(['seller_id' => $seller->id])->where(['order_status' => 'confirmed'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $processing = Order::where(['seller_is' => 'seller'])->where(['seller_id' => $seller->id])->where(['order_status' => 'processing'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $out_for_delivery = Order::where(['seller_is' => 'seller'])->where(['seller_id' => $seller->id])->where(['order_status' => 'out_for_delivery'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $delivered = Order::where(['seller_is' => 'seller'])->where(['seller_id' => $seller->id])
            ->where(['order_status' => 'delivered'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $canceled = Order::where(['seller_is' => 'seller'])->where(['seller_id' => $seller->id])->where(['order_status' => 'canceled'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $returned = Order::where(['seller_is' => 'seller'])->where(['seller_id' => $seller->id])->where(['order_status' => 'returned'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();
        $failed = Order::where(['seller_is' => 'seller'])->where(['seller_id' => $seller->id])->where(['order_status' => 'failed'])
            ->when($today, function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($this_month, function ($query) {
                return $query->whereMonth('created_at', Carbon::now());
            })
            ->count();

        $data = [
            'pending' => $pending,
            'confirmed' => $confirmed,
            'processing' => $processing,
            'out_for_delivery' => $out_for_delivery,
            'delivered' => $delivered,
            'canceled' => $canceled,
            'returned' => $returned,
            'failed' => $failed
        ];

        return response()->json($data, 200);
    }

    public function language_change(Request $request)
    {
        $seller = $request->seller;
        $seller->app_language = $request->current_language;
        $seller->save();

        return response()->json(['message' => 'Successfully change'], 200);
    }
}
