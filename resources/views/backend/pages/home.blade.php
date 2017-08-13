{{-- Master Layout --}}
@extends('cortex/foundation::backend.layouts.default')

{{-- Page Title --}}
@section('title')
    {{ config('app.name') }} » {{ trans('cortex/foundation::common.backend') }}
@stop

{{-- Main Content --}}
@section('content')

    <div class="content-wrapper">

        <!-- Main content -->
        <section class="content">
            <!-- Small boxes (Stat box) -->
            <div class="row">
                <div class="col-md-12">
                    <h1><i class="fa fa-dashboard"></i> {{ trans('cortex/foundation::common.welcome') }}</h1>
                    <h4>{{ trans('cortex/foundation::common.welcome_body') }}</h4>
                </div>

            </div>
            <!-- /.row -->

        </section>
        <!-- /.content -->
    </div>

@endsection
