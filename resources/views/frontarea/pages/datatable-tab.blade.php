{{-- Master Layout --}}
@extends('cortex/foundation::frontarea.layouts.default')

{{-- Page Title --}}
@section('title')
    {{ extract_title(Breadcrumbs::render()) }}
@endsection

{{-- Main Content --}}
@section('content')

    <div class="container">

        <div class="row profile">
            <div class="col-md-3">
                @include('cortex/auth::frontarea.partials.sidebar')
            </div>

            <div class="col-md-9">

                <div class="profile-content">

                    <nav aria-label="breadcrumb">
                        {{ Breadcrumbs::render() }}
                    </nav>

                    <div class="nav-tabs-custom">
                        {!! Menu::render("{$tabs}", 'nav-tab') !!}

                        <div class="tab-content">

                            <div class="tab-pane active" id="{{ $id }}-tab">

                                @yield('datatable-filters')
                                {!! $dataTable->pusher($pusher ?? null)->routePrefix($routePrefix ?? null)->table(['id' => $id]) !!}

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection

@push('head-elements')
    <meta name="turbolinks-cache-control" content="no-cache">
@endpush

@push('styles')
    <link href="{{ mix('css/datatables.css') }}" rel="stylesheet">
@endpush

@push('vendor-scripts')
    <script src="{{ mix('js/datatables.js') }}" defer></script>
@endpush

@push('inline-scripts')
    {!! $dataTable->scripts() !!}
@endpush
