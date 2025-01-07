@extends('layouts.back-end.app')

@section('title', translate('banner'))

@section('content')
    <div class="content container-fluid">

        <div class="d-flex justify-content-between mb-3">
            <div>
                <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
                    <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/banner.png') }}" alt="">
                    {{ translate('banner_update_form') }}
                </h2>
            </div>
            <div>
                <a class="btn btn--primary text-white" href="{{ route('admin.section.list') }}">
                    <i class="tio-chevron-left"></i> {{ translate('back') }}</a>
            </div>
        </div>

        <div class="row text-start">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.section.update', [$section['id']]) }}" method="post" enctype="multipart/form-data"
                              class="banner_form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <input type="hidden" id="id" name="id">
                                    </div>

                                    <div class="form-group">
                                        <label for="name" class="title-color text-capitalize">{{ translate('state') }}</label>
                                        <select class="js-example-responsive form-control w-100" name="state_id" required id="state_id">
                                            @foreach($states as $key => $state)
                                                <option value="{{ $state->id }}" {{ $section['state_id'] == $state->id ? 'selected':''}}>{{ $state->ar_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="name" class="title-color text-capitalize">{{ translate('name') }}</label>
                                        <input type="text" name="name" class="form-control" id="name" required placeholder="{{ translate('enter_neme') }}" value="{{$section['name']}}">
                                    </div>

                                 


                                </div>


                                <div class="col-md-12 d-flex justify-content-end gap-3">
                                    <button type="submit" class="btn btn--primary px-4">{{ translate('update') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/section.js') }}"></script>
    <script>
        "use strict";
        $(document).on('ready', function () {
            getThemeWiseRatio();
        });
        let elementBannerTypeSelect = $('#banner_type_select');
        elementBannerTypeSelect.on('change',function(){
            getThemeWiseRatio();
        });
        function getThemeWiseRatio(){
            let bannerType = elementBannerTypeSelect.val();
            let theme = '{{ theme_root_path() }}';
            let themeRatio = {!! json_encode(THEME_RATIO) !!};
            let getRatio = themeRatio[theme][bannerType];
            $('#theme_ratio').text(getRatio);
        }
    </script>
@endpush
