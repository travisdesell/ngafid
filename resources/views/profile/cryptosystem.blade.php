@extends('NGAFID-master')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <b>Data Encryption</b>
                        <span class="pull-right">{{ date("D M d, Y G:i A T") }}</span>
                    </div>

                    <div class="panel-body">
                        @include('partials.errors')

                        {!! Form::open(['method' => 'POST', 'url' => 'generate', 'class' => 'form-horizontal']) !!}
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">

                        <div class="form-group">
                            <label class="col-md-4 control-label">
                                Secret Key
                            </label>
                            <div class="col-md-5">
                                <input type="password" class="form-control" name="secretKey" value="">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-4 control-label">
                                Confirm Secret Key
                            </label>
                            <div class="col-md-5">
                                <input type="password" class="form-control" name="secretKey_confirmation" value="">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-8 col-md-offset-5">
                                <button type="submit" class="btn btn-primary">
                                    Save
                                </button>
                            </div>
                        </div>
                        {!! Form::close() !!}

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
