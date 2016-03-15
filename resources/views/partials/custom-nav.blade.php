<li  class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Flights<span class="caret"></span></a>
    <ul class="dropdown-menu" role="menu">
        <li><a href="{{ URL::route('flights') }}">All Flights</a></li>
        <li role="separator" class="divider"></li>
        <li><a href="{{ URL::route('flights/event', 'excessive-roll') }}">Excessive Roll</a></li>
        <li><a href="{{ URL::route('flights/event', 'excessive-pitch') }}">Excessive Pitch</a></li>
        <li><a href="{{ URL::route('flights/event', 'excessive-lateral-acceleration') }}">Excessive Lateral Acceleration</a></li>
        <li><a href="{{ URL::route('flights/event', 'excessive-vertical-acceleration') }}">Excessive Vertical Acceleration</a></li>
        <li><a href="{{ URL::route('flights/event', 'excessive-longitudinal-acceleration') }}">Excessive Longitudinal Acceleration</a></li>
        <li><a href="{{ URL::route('flights/event', 'excessive-vsi-final') }}">Excessive VSI on Final</a></li>

        <?php
            $fleetID = Auth::user()->org_id;
            $aircraftTable = new NGAFID\Aircraft();
            //$aircraft = $aircraftTable->uniqueAircraft($fleetID)->get()->toArray();
            //$aircraftType = array_column($aircraft, 'id');
            $aircraftInfo = $aircraftTable->uniqueAircraft($fleetID)->get();

            $aircraftInfo = $aircraftInfo->toArray();
            $aircraftType = array();
            foreach($aircraftInfo as $key => $val){
                $aircraftType[] = $val['id'];
            }
        ?>

        @if(in_array('1', $aircraftType) || in_array('2', $aircraftType))
            <li><a href="{{ URL::route('flights/event', 'excessive-speed') }}">Excessive Speed</a></li>
            <li><a href="{{ URL::route('flights/event', 'high-cht') }}">High CHT</a></li>
            <li><a href="{{ URL::route('flights/event', 'high-altitude') }}">High Altitude</a></li>
            <li><a href="{{ URL::route('flights/event', 'low-fuel') }}">Low Fuel</a></li>
            <li><a href="{{ URL::route('flights/event', 'low-oil-pressure') }}">Low Oil Pressure</a></li>
            <li><a href="{{ URL::route('flights/event', 'low-airspeed-approach') }}">Low Airspeed on Approach</a></li>
            <li><a href="{{ URL::route('flights/event', 'low-airspeed-climbout') }}">Low Airspeed on Climb-out</a></li>
        @endif
    </ul>
</li>
<li><a href="{{ url('/flights/trend') }}">Trends</a></li>
<li  class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Data Import<span class="caret"></span></a>
    <ul class="dropdown-menu" role="menu">
        <li><a href="{{ url('import/upload') }}">Import Flight Data</a></li>
        <li><a href="{{ url('import/status') }}">Import Status/History</a></li>
    </ul>
</li>
@if(Auth::user()->org_id == 1)
<li  class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Approach<span class="caret"></span></a>
    <ul class="dropdown-menu" role="menu">
        <!--<li><a href="{{ url('approach/index') }}">Info...</a></li>-->
        <li><a href="{{ url('approach/analysis') }}">Analysis</a></li>
    </ul>
</li>
@endif