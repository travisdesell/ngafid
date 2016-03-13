@extends('NGAFID-master')

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

                                            @foreach($flightInfo as $fltInfo)
                                                @if($fltInfo->type == 'F')
                                                    <div class="media">
                                                        <b class="media-heading text-primary">Fleets</b>
                                                        <div class="media-body">
                                                            No Uploaded: {{$fltInfo->uploads}}
                                                            <br>Flight Hours: {{$fltInfo->hours}}
                                                        </div>
                                                    </div>
                                                @elseif($fltInfo->type == 'O')
                                                    <div class="media">
                                                        <b class="media-heading text-primary">Non-Fleet (i.e. Individual Operators)</b>
                                                        <div class="media-body">
                                                            No Uploaded: {{$fltInfo->uploads}}
                                                            <br>Flight Hours: {{$fltInfo->hours}}
                                                        </div>
                                                    </div>
                                                @endif

                                            @endforeach
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