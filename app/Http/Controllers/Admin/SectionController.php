<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Enums\ViewPaths\Admin\Section;
use APP\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Contracts\Repositories\SectionRepositoryInterface;
use App\Contracts\Repositories\BrandRepositoryInterface;
use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\ShopRepositoryInterface;
use App\Enums\ViewPaths\Admin\Banner;

use App\Http\Requests\Admin\BannerAddRequest;
use App\Http\Requests\Admin\BannerUpdateRequest;
use App\Services\BannerService;
use App\Services\SectionService;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;


class SectionController extends BaseController
{
    use FileManagerTrait {
        delete as deleteFile;
        update as updateFile;
    }

    public function __construct(
        private readonly SectionRepositoryInterface        $sectionRepo,
        private readonly CategoryRepositoryInterface      $categoryRepo,
        private readonly ShopRepositoryInterface          $shopRepo,
        private readonly BrandRepositoryInterface         $brandRepo,
        private readonly ProductRepositoryInterface       $productRepo,
        private readonly BannerService       $bannerService,
        private readonly SectionService       $sectionService,

    )
    {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */
    public function index(Request|null $request, string $type = null): View
    { 
        //$states = State::get();
        $states = DB::table('states')->get();
        //dd($states);
        return $this->getListView($request);
    }

    public function getListView(Request $request): View
    {
        //$bannerTypes = $this->bannerService->getBannerTypes();
        $states = DB::table('states')->get();
        
        $sections = $this->sectionRepo->getListWhereIn(
            orderBy: ['id'=>'desc'],
            searchValue: $request['searchValue'],
            
            
            dataLimit: getWebConfig(name: 'pagination_limit'),
        );

        

        return view(Section::LIST[VIEW],  compact('sections','states' ));
    }

    public function add(Request $request): RedirectResponse
    {   
        $data = $request->validate([
            'state_id' => 'required',
            'name' => 'required'
        ]);

        //$data = $this->sectionService->getProcessedData(request: $request);
        $this->sectionRepo->add(data:$data);
        Toastr::success(translate('section_added_successfully'));
        return redirect()->route('admin.section.list');
    }

    public function getUpdateView($id): View
    {
        
        $section = $this->sectionRepo->getFirstWhere(params: ['id'=>$id]);
        $states = DB::table('states')->get();

        return view(Section::UPDATE[VIEW], compact('section', 'states'));
    }

    public function update(Request $request, $id): RedirectResponse
    {   
        $data = $request->validate([
            'state_id' => 'sometimes',
            'name' => 'sometimes'
        ]);

        
        $section = $this->sectionRepo->getFirstWhere(params: ['id'=>$id]);
        //$data = $this->sectionService->getProcessedData(request: $request, image: $section['photo']);
        $this->sectionRepo->update(id:$section['id'], data:$data);
        Toastr::success(translate('section_updated_successfully'));
        return redirect()->route(Section::UPDATE[ROUTE]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $status = $request->get('status', 0);
        $this->bannerRepo->update(id:$request['id'], data:['published'=>$status]);
        return response()->json([
            'message' => $status == 1 ? translate("banner_published_successfully") : translate("banner_unpublished_successfully"),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $banner = $this->sectionRepo->getFirstWhere(params: ['id' => $request['id']]);
        //$this->deleteFile(filePath: '/banner/' . $banner['photo']);
        $this->sectionRepo->delete(params: ['id' => $request['id']]);
        return response()->json(['message' => translate('section_deleted_successfully')]);
    }
}
