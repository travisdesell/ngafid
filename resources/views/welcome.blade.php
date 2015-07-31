@extends('NGAFID-master')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading"><b>Welcome to the National General Aviation Flight Information Database (NGAFID)</b></div>

                    <div class="panel-body">
                        <p>The NGAFID is a joint industry-FAA initiative designed to bring voluntary Flight Data Monitoring (FDM) to General Aviation.
                            While sharing flight data is voluntary, there are many reasons pilots and operators should consider participating.
                        </p>
                        <p>
                            <span class="text-info"><b>What is digital Flight Data Monitoring (FDM)?</b></span><br>
                            FDM is the recording of flight‐related information. Analysis of FDM data can help pilots, instructors, or operator groups improve performance and safety.
                        </p>
                        <p>
                            <span class="text-info"><b>Why should I participate in the GA Demonstration Project?</b></span><br>
                            <ul>
                                <li>You can replay your own flights and view your data to identify potential safety risks.</li>
                                <li>Pilots in safety programs are less likely to be involved in an accident (GAO 13‐36, pg. 13).</li>
                                <li>Attitude data can be collected, giving you enhanced feedback to improve your skills.</li>
                                <li>Your data can help the whole GA community.</li>
                                <li><strong><em>Your data will not be used for enforcement purposes.</em></strong></li>
                            </ul>
                        </p>
                        <p>
                            <span class="text-info"><b>How will this project benefit the GA community?</b></span><br>
                            <ul>
                                <li>By working together, the community will identify risks and safety hazards specific to the general aviation environment.</li>
                                <li>The general aviation community can develop and implement solutions to recognized problems.</li>
                            </ul>
                        </p>
                        <p>
                            <span class="text-info"><b>How can I participate?</b></span><br>
                            You can participate in two ways. (1) Data can come from either your on-board avionics (for example, a G1000 or data recorder) or (2) using a newly developed mobile app — on your smart phone or tablet. To sign up for an account, <a href="http://fdm.und.edu/auth/register">click here</a>.<br>
                        </p>
                        <p>
                            <span class="text-info"><b>Download the GAARD (GA Recording Device) App for IOS and Android below</b></span>
                            <span class="col-xs-6">
                                For iOS devices, search for GAARD at the App Store or click here to <br>
                                <a href="https://itunes.apple.com/us/app/general‐aviation‐airborne/id929718718?mt=8" target="_blank">download the free app</a><br>
                                <a href="https://geo.itunes.apple.com/us/app/gaard-general-aviation-airborne/id929718718?mt=8" target="_blank" style="display:inline-block;overflow:hidden;background:url(http://linkmaker.itunes.apple.com/images/badges/en-us/badge_appstore-lrg.svg) no-repeat;width:165px;height:40px;"></a>
                            </span>
                            <span class="col-xs-6">
                                For Android devices, search for GAARD at Google Play or click here to <br>
                                <a href="http://tinyurl.com/gaardapp" target="_blank">download the free app</a><br>
                                <a href="https://play.google.com/store/apps/details?id=org.mitre.asgaard.beta" target="_blank">
                                    <img alt="download the GAARD App" src="https://developer.android.com/images/brand/en_app_rgb_wo_45.png" style="height:40px" />
                                </a>
                            </span>
                            <br>
                            <div class="row">
                                <span class="pull-left col-md-12"><b>GAARD App Screenshots</b></span>
                                <div class="col-xs-12">
                                    <div class="col-xs-4"><img class="img-responsive" src="{{ asset('images/gaardImg1.png') }}" height="75"/></div>
                                    <div class="col-xs-4"><img class="img-responsive" src="{{ asset('images/gaardImg2.png') }}" height="75"/></div>
                                    <div class="col-xs-4"><img class="img-responsive" src="{{ asset('images/gaardImg3.png') }}" height="75"/></div>
                                </div>
                            </div>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection