@extends('NGAFID-master')

@section('cssScripts')
    <style>
        table {
            table-layout:fixed;
        }

        .div-table-content {
            height:250px;
            overflow-y:auto;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading"><b>Import History</b> <span class="pull-right">{{date("D M d, Y G:i A T")}}</span></div>

                    <div class="panel-body">
                        <div id="no-more-tables">
                            <table class="table table-condensed" style="margin:0px;">
                                <thead>
                                    <tr>
                                        <th class="col-md-2">N Number</th>
                                        <th class="col-md-2">Aircraft</th>
                                        <th class="col-md-2">Date Uploaded</th>
                                        <th class="col-md-2">Import Submitted</th>
                                        <th class="col-md-2">Import Status</th>
                                    </tr>
                                </thead>
                            </table>
                            <div class="div-table-content">
                                <table class="table table-condensed">
                                    <tbody>
                                    @foreach ($data as $flight)
                                        <tr class="{{($flight->status == 'Failed' ? 'danger': ($flight->status == 'Imported' ? 'success' : ($flight->status == 'Pending' ? 'warning' : 'info')))}}">
                                            <td class="col-md-2" data-title="N Number">{{$flight->n_number}}</td>
                                            <td class="col-md-2" data-title="Aircraft">{{$flight->aircraft}}</td>
                                            <td class="col-md-2" data-title="Date Uploaded">{{$flight->uploaded}}</td>
                                            <td class="col-md-2" data-title="Import Submitted">{{$flight->submitted}}</td>
                                            <td class="col-md-2" data-title="Import Status">{{$flight->status}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    {!! str_replace('/?', '?', $data->render()) !!}
                </div>
            </div>
        </div>
    </div>
@endsection
