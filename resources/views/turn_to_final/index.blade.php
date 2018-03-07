@extends('NGAFID-master')

@section('cssScripts')
    <link href="//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css" rel="stylesheet" />
    <link href="https://openlayers.org/en/v4.6.4/css/ol.css" rel="stylesheet">
    <!-- The line below is only needed for old environments like Internet Explorer and Android 4.x -->
    <script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=requestAnimationFrame,Element.prototype.classList,URL"></script>

    <style>
        .ui-datepicker-calendar {
            display: none;
        }

        .vertical-line {
            border-left: 1px solid hsl(214, 7%, 80%);
        }

        #map-container {
            margin-top: 2em;
        }

        .selected-source {
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.20);
            background-image: linear-gradient(-180deg, #80d1f3 0%, #4a90e2 100%);
            box-shadow: 0 1px 2px 0 rgba(74, 144, 226, 0.44), 0 2px 8px 0 rgba(0, 0, 0, 0.14);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <b>Turn To Final Analysis</b>
                        <span class="pull-right">{{ date('D M d, Y G:i A T') }}</span>
                    </div>

                    <div class="panel-body">
                        <div class="col-md-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('flight_id', 'Flight ID:') !!}
                                    {!! form::text('flight_id', $flightId, ['class' => 'form-control', 'id' => 'flight_id']) !!}
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button type="button" id="display_single" class="btn btn-primary">
                                    Display Single Flight
                                </button>
                            </div>
                        </div>

                        <div class="col-md-9 vertical-line">
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('airport', 'Airport:') !!}
                                    {!! Form::text('airport', '', ['class' => 'form-control', 'id' => 'airport']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('runway', 'Runway:') !!}
                                    {!! Form::select('runway', $airports, null, ['placeholder' => 'Select Runway', 'class' => 'form-control', 'id' => 'runway']) !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('month_year', 'Month/Year:') !!}
                                    <div class="input-group">
                                        <input class="form-control" id="month_year" type="text" name="month_year" value="{{ $date }}" />
                                        <span class="input-group-addon">
                                            <span class="glyphicon glyphicon-calendar"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button type="button" id="display_agg" class="btn btn-primary">
                                    Display Aggregate
                                </button>
                            </div>
                        </div>

                        <div id="map-container" class="col-md-12">
                            <div id="set_source_btns" class="btn-group btn-group-justified" role="group"></div>

                            <div id="map" class="map"></div>

                            <div class="btn-group btn-group-justified" role="group">
                                <a href="" id="export" class="btn btn-default">
                                    <span class="glyphicon glyphicon-download-alt"></span>
                                    Download PNG
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('jsScripts')
    <script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>

    <script src="https://openlayers.org/en/v4.6.4/build/ol.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/5.1.5/turf.min.js" integrity="sha256-V9GWip6STrPGZ47Fl52caWO8LhKidMGdFvZbjFUlRFs=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/1.3.3/FileSaver.min.js" integrity="sha256-FPJJt8nA+xL4RU6/gsriA8p8xAeLGatoyTjldvQKGdE=" crossorigin="anonymous"></script>

    <script src="{{ elixir('js/airport-runway-autocomplete.js') }}"></script>
    <script src="{{ elixir('js/datepicker-utils.js') }}"></script>

    <script type="text/javascript">
        function transformPoint(point) {
            point = point instanceof Array ? point : point.geometry.coordinates;
            return ol.proj.fromLonLat(point);
        }

        function transformPoints(points) {
            return points.map(function (point) { return transformPoint(point); });
        }

        $(function () {
            var allData = [];
            var $setSourceBtnsContainer = $('div#set_source_btns');
            var vectorSources = [];

            function createSetSourceBtn(idx) {
                return $('<div>', {class: 'btn-group', role: 'group'}).append(
                    $('<button>', {id: 'set_source_' + idx, class: 'btn btn-default', text: 'Approach #' + (idx + 1)})
                );
            }

            function createVectorLayer(source) {
                return new ol.layer.Vector({
                    source: source,
                    style: styleFunction
                });
            }

            function createVectorSource(approach) {
                var phases = approach.phases;
                var features = [];
                Object.keys(phases).forEach(function (key) {
                    var coordinates = transformPoints(phases[key].coordinates);

                    if (coordinates.length > 1) {
                        features.push(turf.lineString(
                            coordinates, {
                                id: key,
                                type: phases[key].type,
                                severity: phases[key].severity
                            }
                        ));
                    }
                });

                var touchdown = [
                    approach.runway.touchdown_lon,
                    approach.runway.touchdown_lat
                ];
                var runwayBearing = approach.runway.true_course;

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

                // Combine extended center line & flight path into a single feature
                // collection
                var collection = turf.featureCollection([
                    ...features, extendedCenterLineString
                ]);

                return new ol.source.Vector({
                    features: (new ol.format.GeoJSON()).readFeatures(collection)
                });
            }

            function setVectorSource(idx) {
                vectorLayer.setSource(vectorSources[idx]);

                var runway = allData[idx].runway;
                var touchdownPoint = turf.point([
                    runway.touchdown_lon,
                    runway.touchdown_lat
                ]);

                flyTo(
                    transformPoint(touchdownPoint),
                    turf.degreesToRadians(360 - runway.true_course),
                    3000,  // milliseconds
                    function () {}
                );
            }

            function flyTo(location, rotation, duration, done) {
                var zoom = mapView.getZoom();
                var parts = 2;
                var called = false;
                var callback = function (complete) {
                    --parts;
                    if (called) {
                        return;
                    }
                    if (parts === 0 || !complete) {
                        called = true;
                        done(complete);
                    }
                };
                mapView.animate({
                    center: location,
                    rotation: rotation,
                    duration: duration
                }, callback);
                mapView.animate({
                    zoom: zoom - 3,
                    duration: duration / 2
                }, {
                    zoom: 15,
                    duration: duration / 2
                }, callback);
            }

            var styles = {
                'stop-and-go': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#529699',
                        width: 2
                    })
                }),
                'touch-and-go': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#004144',
                        width: 2
                    })
                }),
                'go-around': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#005906',
                        width: 2
                    })
                }),
                'aligned': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#6ac870',
                        width: 2
                    })
                }),
                'large': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#be2f2b',
                        width: 2
                    })
                }),
                'small': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#be6f2b',
                        width: 2
                    })
                }),
                'landing': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#fe8b88',
                        width: 2
                    })
                }),
                'takeoff': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#710300',
                        width: 2
                    })
                }),
                'extended_center_line': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#000000',
                        width: 1
                    })
                })
            };

            var styleFunction = function (feature) {
                var props = feature.getProperties();
                if (props.id === 'turn') {
                    if (props.type === 'aligned') return styles[props.type];
                    else return styles[props.severity];
                }

                return styles[props.type || props.id];
            };

            var vectorLayer = new ol.layer.Vector({
                style: styleFunction
            });

            var mapView = new ol.View({
                // Setting the below as defaults before the data is dynamically
                // loaded
                center: [0, 0],
                zoom: 2
            });

            var osmSourceLayer = new ol.layer.Tile({
                preload: 4,
                source: new ol.source.OSM()
            });

            var map = new ol.Map({
                layers: [
                    osmSourceLayer,
                    vectorLayer
                ],
                target: 'map',
                loadTilesWhileAnimating: true,
                view: mapView
            });

            $setSourceBtnsContainer.on('click', 'button[id^="set_source_"]', function () {
                var $sourceBtns = $setSourceBtnsContainer.find('button[id^="set_source_"]');
                var idx = $sourceBtns.index(this);
                $sourceBtns.removeClass('selected-source');
                $(this).addClass('selected-source');
                setVectorSource(idx);
            });

            $('#export').click(function () {
                map.once('postcompose', function (event) {
                    var canvas = event.context.canvas;
                    if (navigator.msSaveBlob) {
                        navigator.msSaveBlob(canvas.msToBlob(), 'map.png');
                    } else {
                        canvas.toBlob(function(blob) {
                            saveAs(blob, 'map.png');
                        });
                    }
                });
                map.renderSync();

                return false;
            });

            $('#display_single').click(function () {
                var flightId = $('#flight_id').val();

                if (! /^[1-9][0-9]*$/.test(flightId))
                    // Check to see if the user submitted ID is valid.
                    return;

                $.getJSON(
                    '{{ url('approach/turn-to-final/chart') }}/' + flightId
                ).done(function (data, textStatus, jqXHR) {
                    allData = data;

                    // Clear all layers from the map and re-add the base Open
                    // Street Maps (OSM) layer
                    map.getLayers().clear();
                    map.addLayer(osmSourceLayer);
                    map.addLayer(vectorLayer);

                    // Reset our array of vector sources
                    vectorSources = allData.map(function (approach) {
                        return createVectorSource(approach);
                    });

                    $setSourceBtnsContainer.empty()
                        .append(data.map(function (approach, idx) {
                            return createSetSourceBtn(idx);
                        }));

                    // Set the first approach as the map source by default
                    $setSourceBtnsContainer.find('button[id^="set_source_"]')
                        .first()
                        .click();
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    console.error(errorThrown);
                });
            });

            $('#display_agg').click(function () {
                var date = $('#month_year').val();
                var runwayId = $('#runway').val();

                console.log(date, runwayId);

                $.getJSON(
                    '{{ url('approach/turn-to-final/chart-agg') }}/' + runwayId + '/' + date
                ).done(function (data, textStatus, jqXHR) {
                    var approaches = data;
                    allData = [];
                    vectorSources = [];

                    // Clear all layers from the map and re-add the base Open
                    // Street Maps (OSM) layer
                    map.getLayers().clear();
                    map.addLayer(osmSourceLayer);

                    // Clear the Approach Source buttons since all the
                    // approaches will be displayed at once
                    $setSourceBtnsContainer.empty();

                    var deferreds = approaches.map(function (a) {
                        console.log(a.flight_id, a.approach_id);

                        return $.getJSON(
                            '{{ url('approach/turn-to-final/chart') }}/' + a.flight_id + '/' + a.approach_id
                        ).done(function (data, textStatus, jqXHR) {
                            allData.push(...data);

                            data.forEach(function (approach) {
                                var layer = createVectorLayer(
                                    createVectorSource(approach)
                                );

                                map.addLayer(layer);
                            });
                        }).fail(function (jqXHR, textStatus, errorThrown) {
                            console.error(errorThrown);
                        });
                    });

                    $.when(...deferreds).then(
                        function (x) {
                            alert('Deffered done!');
                        },
                        function (y) {
                            alert('Deffered fail!')
                        },
                        function (z) {
                            alert('Deferred progress');
                        }
                    );
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    console.log(errorThrown)
                });
            });
        });
    </script>
@endsection
