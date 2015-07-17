@extends('NGAFID-master')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading"><b>Service Unavailable</b> <span class="pull-right">{{date("D M d, Y G:i A T")}}</span></div>

                    <div class="panel-body">
                        <p>The NGAFID is currently down for maintenance. Please try again later.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
