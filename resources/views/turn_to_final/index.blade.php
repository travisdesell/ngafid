@extends('NGAFID-master')

@section('cssScripts')
    <link href="https://openlayers.org/en/v4.6.4/css/ol.css" rel="stylesheet">
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <b>Turn To Final Analysis - {{ $flightId }} - {{ $approach->approach_id }}</b>
                        <span class="pull-right">{{ date('D M d, Y G:i A T') }}</span>
                    </div>

                    <div class="panel-body">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('flight_id', 'Flight ID:') !!}
                                {!! form::text('flight_id', $flightId, ['class' => 'form-control', 'id' => 'flight_id']) !!}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="button" id="display" class="btn btn-primary btn-sm pull-right">
                                Display
                            </button>
                        </div>

                        <div id="map" class="map col-md-12"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('jsScripts')
    <script src="https://openlayers.org/en/v4.6.4/build/ol.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/5.1.5/turf.min.js" integrity="sha256-V9GWip6STrPGZ47Fl52caWO8LhKidMGdFvZbjFUlRFs=" crossorigin="anonymous"></script>

    <script type="text/javascript">
        function transformPoint(point) {
            point = point instanceof Array ? point : point.geometry.coordinates;
            return ol.proj.fromLonLat(point);
        }

        function transformPoints(points) {
            return points.map(function (point) { return transformPoint(point); });
        }

        $(function () {
            var coordinates = transformPoints({!! json_encode($coordinates) !!});
            var touchdown = {!! json_encode($touchdown) !!};
            var runwayBearing = {!! $runway->true_course !!};

            // We need the reciprocal of the runway heading for calculating
            // the extended center line point
            var reverseBearing = (180 + runwayBearing) % 360;

            // Need to normalize bearing from -180 to 180 degrees from north
            if (reverseBearing > 180)
                reverseBearing -= 360;

            // Calculate a point that is 1 mile in the opposite heading as the
            // runway for an extended center line
            var touchdownPoint = turf.point(touchdown);
            var extendedTouchdownPoint = turf.destination(
                touchdownPoint, 1, reverseBearing, {units: 'miles'}
            );
            var extendedCenterLineString = turf.lineString(
                transformPoints([extendedTouchdownPoint, touchdownPoint]),
                {id: 'extended_center_line'}
            );

            var flightPath = turf.lineString(coordinates, {id: 'flight_path'});

            // Combine extended center line & flight path into a single feature
            // collection
            var collection = turf.featureCollection([
                flightPath, extendedCenterLineString
            ]);

            var styles = {
                'flight_path': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#00B7B7',
                        width: 2
                    })
                }),
                'extended_center_line': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#AC0000',
                        width: 1
                    })
                })
            };

            var styleFunction = function (feature) {
                return styles[feature.getProperties().id];
            };

            var vectorSource = new ol.source.Vector({
                features: (new ol.format.GeoJSON()).readFeatures(collection)
            });
            var vectorLayer = new ol.layer.Vector({
                source: vectorSource,
                style: styleFunction
            });

            var map = new ol.Map({
                layers: [
                    new ol.layer.Tile({source: new ol.source.OSM()}),
                    vectorLayer
                ],
                target: 'map',
                view: new ol.View({
                    center: transformPoint(touchdownPoint),
                    zoom: 15,
                    rotation: turf.degreesToRadians(360 - runwayBearing)
                })
            });

            $('#display').click(function () {
                alert($('#flight_id').val());
            });
        });
    </script>
@endsection
