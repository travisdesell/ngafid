@extends('NGAFID-master')

@section('cssScripts')
    <link href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css" rel="stylesheet" />
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <b>Approach Analysis</b>
                        <span class="pull-right">{{ date("D M d, Y G:i A T") }}</span>
                    </div>

                    <div class="panel-body">
                        {!! Form::open(['method' => 'GET', 'url' => '/approach/go-around', 'class' => 'form-horizontal', 'id' => 'approach_tool']) !!}
                        {!! Form::token() !!}
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('airport_label', 'Airport:') !!}
                                {!! Form::text('airports', '', ['class' => 'form-control', 'id' => 'airports']) !!}
                            </div>
                        </div>
                        <div class="col-md-2 col-md-offset-1">
                            <div class="form-group">
                                {!! Form::label('runways_label', 'Runway:') !!}
                                {!! Form::select('runways', $runways, $selectedRunway, ['placeholder' => 'Select Runway', 'class' => 'form-control', 'id' => 'runways']) !!}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" id="display" class="btn btn-primary btn-sm pull-right" data-link="{{ url('/approach/go-around') }}">
                                Display
                            </button>
                        </div>

                        <div id="date_range_container">
                            <div class="col-md-2 col-md-offset-1">
                                <div class="form-group">
                                    {!! Form::label('start_date_label', 'Start Date:') !!}
                                    <div class="input-group">
                                        <input type="text" class="form-control datepicker" name="start_datepicker[]" value="{{ $start_date }}" />
                                        <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-md-offset-1">
                                <div class="form-group">
                                    {!! Form::label('end_date_label', 'Start Date:') !!}
                                    <div class="input-group">
                                        <input type="text" class="form-control datepicker" name="end_datepicker[]" value="{{ $end_date }}" />
                                        <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <br /><br />

                        <div id="chart" class="col-md-12"></div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('jsScripts')
    <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <script src="http://code.highcharts.com/6.0.4/highcharts.js"></script>
    <script src="http://code.highcharts.com/6.0.4/modules/drilldown.js"></script>
    <script src="http://code.highcharts.com/6.0.4/modules/no-data-to-display.js"></script>
    <script src="http://code.highcharts.com/6.0.4/modules/exporting.js"></script>

    <script type="text/javascript">
        $(function () {
            // Provide auto-complete feature to Airports text box
            $("#airports").autocomplete({
                source: "/airports",
                minLength: 3,
                select: function (event, ui) {
                    var idx = ui.item.id;
                    var CSRF_TOKEN = $('input[name="_token"]').val();
                    $.ajax({
                        type: "GET",
                        url: "{{ url('/runways') }}",
                        data: {airport_id: idx, _token: CSRF_TOKEN},
                        success: function (data) {
                            var items = data.map(function (key) {
                                return '<option value="' + key + '">' + key + '</option>'
                            }).join('');

                            $('#runways').html(items);
                        }
                    });
                }
            });

            $('body').on('focus', '.datepicker', function () {
                $(this).datepicker({
                    dateFormat: 'yy-mm-dd'
                });
            });

            $('#add_date_range').click(function () {
                $('#date_range_container').append(
                    createDatePicker('Start Date:', 'start_datepicker[]'),
                    createDatePicker('End Date:', 'end_datepicker[]')
                );
            });

            function createDatePicker(label, name) {
                return $('<div>', {class: 'col-md-5 col-md-offset-1'}).append(
                    $('<div>', {class: 'form-group'}).append(
                        $('<label>', {text: label}),
                        $('<div>', {class: 'input-group'}).append(
                            $('<input>', {type: 'text', class: 'form-control datepicker', name: name}),
                            $('<span>', {class: 'input-group-addon'}),
                            $('<span>', {class: 'glyphicon glyphicon-calendar'})
                        )
                    )
                );
            }

            function getDate(element) {
                var date;
                try {
                    date = $.datepicker.parseDate('yyyy-mm-dd', element.value);
                } catch (error) {
                    date = null;
                }

                return date;
            }

            // jQuery UI datepicker
            // $('#start_datepicker, #end_datepicker').datepicker({
            //     changeMonth: true,
            //     changeYear: true,
            //     showButtonPanel: true,
            //     dateFormat: 'yy-mm-dd',
            //     // onClose: function (dateText, inst) {
            //     //     function isDonePressed() {
            //     //         return $('#ui-datepicker-div').html().indexOf('ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all ui-state-hover') > -1;
            //     //     }
            //     //
            //     //     if (isDonePressed()) {
            //     //         var $datepicker_div = $("#ui-datepicker-div");
            //     //         var year = $datepicker_div.find(".ui-datepicker-year :selected").val();
            //     //         var month = $datepicker_div.find(".ui-datepicker-month :selected").val();
            //     //         var day = $datepicker_div.find(".ui-datepicker-day :selected").val();
            //     //         $(this).datepicker('setDate', new Date(year, month, day)).trigger('change');
            //     //
            //     //         $(this).focusout();
            //     //     }
            //     // },
            //     // beforeShow: function (input, inst) {
            //     //     var datestr = $(this).val();
            //     //     if (datestr.length > 0) {
            //     //         var year = parseInt(datestr.substring(0, 4));
            //     //         var month = parseInt(datestr.substring(5, 7));
            //     //         var day = parseInt(datestr.substring(8, 10));
            //     //         $(this).datepicker('option', 'defaultDate', new Date(year, month - 1, day));
            //     //         $(this).datepicker('setDate', new Date(year, month - 1, day));
            //     //     }
            //     // }
            // });

            Highcharts.chart('chart', {
                chart: {
                    inverted: true,
                    type: 'column'
                },
                title: {
                    text: 'Self Defined Glide Path Analysis'
                },
                xAxis: {
                    title: {
                        text: 'Glide Path (in degrees)'
                    },
                    tickInterval: 0.5,
                    min: 0,
                    max: 10,
                    startOnTick: true,
                    reversed: false,
                    gridLineWidth: 1
                },
                yAxis: {
                    title: {
                        text: 'Number of occurrences'
                    },
                    tickInterval: 1
                },
                plotOptions: {
                    column: {
                        colorByPoint: false,
                        tooltip: {
                            pointFormatter: function () {
                                return '<b>' + this.x + '\u00B0-' + (this.x + .5) + '\u00B0</b>' +
                                    '<br /><b>Occurrences: ' + this.y + '</b>';
                            }
                        }
                    },
                    series: {
                        cursor: 'pointer',
                        events: {
                            click: function (e) {
                                window.open(
                                    "{{ url('approach/selfdefined/flights?') }}" +
                                    $.param({
                                        runway: $('#runway :selected').html(),
                                        date: $('#mthYr').val(),
                                        gpa_low: e.point.x,
                                        gpa_high: e.point.x + 0.5,
                                        flight_id: e.point.ids
                                    }), ''
                                ).focus();
                            }
                        }
                    }
//                    scatter: {
//                        tooltip: {
//                            pointFormatter: function () {
//                                return '<b>' + this.info + '</b>' +
//                                    '<br /><b>Glide Path: ' + this.x + '\u00B0</b>';
//                            }
//                        }
//                    }
                }
            });

            $('#sdTool').submit(function (e) {
                e.preventDefault();

                var chart = $('#chart').highcharts();
                var mthYr = $('#mthYr').val();
                var rnwy = $('#runway').val();
                var CSRF_TOKEN = $('input[name="_token"]').val();

                chart.showLoading('Loading ...');

                while (chart.series.length > 0)
                    chart.series[0].remove(true);

                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    url: '{{ url('/approach/selfdefined/chart') }}',
                    data: {date: mthYr, runway: rnwy, _token: CSRF_TOKEN},
                    success: function (data) {
//                        alert('success');

                        chart.addSeries({
                            name: 'Histogram',
                            type: 'column',
                            data: data['data'],
                            pointPadding: 0,
                            groupPadding: 0,
                            pointPlacement: 'between'
                        });

//                        chart.addSeries({
//                            name: 'Glide Path',
//                            type: 'scatter',
//                            data: data['data'],
//                            marker: {
//                                radius: 2,
//                                fillColor: '#000'
//                            }
//                        });

                        chart.hideLoading();
                    },
                    error: function (data) {
                    }
                });
            });
        });
    </script>
@endsection
