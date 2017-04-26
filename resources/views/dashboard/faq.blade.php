@extends('NGAFID-master')

@section('cssScripts')
    <style>
        th{
            white-space: pre-line;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading col-xs-12"><b>Frequently Asked Questions</b> <span class="pull-right">{{date("D M d, Y G:i A T")}}</span></div>

                    <div class="panel-body">
                        <p class="col-md-12">The NGAFID is a joint industry-FAA initiative designed to bring voluntary Flight Data Monitoring (FDM) to General Aviation.
                            Users participate in two ways. (1) Data is uploaded from either their on-board avionics (for example, a G1000 or data recorder) or (2) using a newly developed mobile app — on their smart phone or tablet.

                            <br><br>This is a short list of our most frequently asked questions.
                        </p>
                        <div class="container">
                            <ul class="media-list col-md-12">
                                <li class="media">
                                    <div class="media-left">
                                        <a href="#"><span class="glyphicon glyphicon-th"></span></a>
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading text-info"><b>How Many Users Voluntarily Participate?</b></p>
                                        <p>
                                            @foreach($userInfo as $info)
                                                @if($info->type == 'N')
                                                    No. Web Accounts: {{$info->total}}
                                                @elseif($info->type == 'G')
                                                    <br>No. Mobile Accounts (GAARD): {{$info->total}}
                                                @endif
                                            @endforeach
                                        </p>
                                    </div>
                                </li>
                                <li class="media">
                                    <div class="media-left">
                                        <a href="#"><span class="glyphicon glyphicon-th"></span></a>
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading text-info"><b>Do Fleets Participate?</b></p>
                                        Yes, {{$fleetInfo[0]->total}} of our existing users are fleets/flight training institutions.
                                    </div>
                                </li>
                                <li class="media">
                                    <div class="media-left">
                                        <a href="#"><span class="glyphicon glyphicon-th"></span></a>
                                    </div>
                                    <div class="media-body">
                                        <p class="media-heading text-info"><b>How Many Flights & Flight Hours have been Contributed?</b></p>

                                        <div class="row">
                                            <div id="no-more-tables" class="col-md-12">
                                                <table class="table table-condensed" style="border-collapse:collapse;margin:0px;">
                                                    <thead>
                                                    <tr class="text-info">
                                                        <th class="col-xs-1 text-left">NGAFID</th>
                                                        <th class="col-xs-1 text-center">Flights</th>
                                                        <th class="col-xs-1 text-center">New <br>Flights<sup>&dagger;</sup></th>
                                                        <th class="col-xs-2 text-center">Flight <br>Hours</th>
                                                        <th class="col-xs-2 text-center">New <br>Flight <br>Hours<sup>&dagger;</sup></th>
                                                        <th class="col-xs-1 text-center">Accounts</th>
                                                        <th class="col-xs-1 text-center">New <br>Accounts<sup>&dagger;</sup></th>
                                                    </tr>
                                                    </thead>
                                                </table>
                                                @foreach($statistics as $stats)
                                                    <div class="div-table-content">
                                                        <table class="table table-condensed">
                                                            <tbody>
                                                            @if($stats->accountType == 'F')
                                                                <tr>
                                                                    <td class="col-xs-1 text-left text-nowrap" data-title="Account Type">Fleet</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="Flights">{{$stats->flights}}</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="New Flights">{{$stats->newFlights}}</td>
                                                                    <td class="col-xs-2 text-center text-nowrap" data-title="Flight Hours">{{$stats->flightHours}}</td>
                                                                    <td class="col-xs-2 text-center text-nowrap" data-title="New Flight Hours">{{$stats->newFlightHours}}</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="Accounts">{{$stats->accounts}}</td>
                                                                    <td class="col-xs-1 text-right text-nowrap" data-title="New Accounts">{{$stats->newAccounts}}</td>
                                                                </tr>
                                                            @elseif($stats->accountType == 'N')
                                                                <tr>
                                                                    <td class="col-xs-1 text-left text-nowrap" data-title="Account Type">Non-Fleet</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="Flights">{{$stats->flights}}</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="New Flights">{{$stats->newFlights}}</td>
                                                                    <td class="col-xs-2 text-center text-nowrap" data-title="Flight Hours">{{$stats->flightHours}}</td>
                                                                    <td class="col-xs-2 text-center text-nowrap" data-title="New Flight Hours">{{$stats->newFlightHours}}</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="Accounts">{{$stats->accounts}}</td>
                                                                    <td class="col-xs-1 text-right text-nowrap" data-title="New Accounts">{{$stats->newAccounts}}</td>
                                                                </tr>
                                                            @elseif($stats->accountType == 'G')
                                                                <tr>
                                                                    <td class="col-xs-1 text-left text-nowrap" data-title="Account Type">GAARD</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="Flights">{{$stats->flights}}</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="New Flights">{{$stats->newFlights}}</td>
                                                                    <td class="col-xs-2 text-center text-nowrap" data-title="Flight Hours">{{$stats->flightHours}}</td>
                                                                    <td class="col-xs-2 text-center text-nowrap" data-title="New Flight Hours">{{$stats->newFlightHours}}</td>
                                                                    <td class="col-xs-1 text-center text-nowrap" data-title="Accounts">{{$stats->accounts}}</td>
                                                                    <td class="col-xs-1 text-right text-nowrap" data-title="New Accounts">{{$stats->newAccounts}}</td>
                                                                </tr>
                                                            @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <p class="pull-left text-danger"><b><small><sup>&dagger;</sup>New information as of {{date("Y-n-j", strtotime("last day of previous month"))}}</small></b></p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection