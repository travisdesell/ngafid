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
                        <div class="col-md-3 col-md-offset-2">
                            <div class="form-group">
                                {!! Form::label('airport_label', 'Airport:') !!}
                                {!! Form::text('airports', '', ['class' => 'form-control', 'id' => 'airports']) !!}
                            </div>
                        </div>
                        <div class="col-md-3 col-md-offset-2">
                            <div class="form-group">
                                {!! Form::label('runways_label', 'Runway:') !!}
                                {!! Form::select('runways', $runways, $selectedRunway, ['placeholder' => 'Select Runway', 'class' => 'form-control', 'id' => 'runways']) !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="btn-group-vertical" role="group">
                                <button type="button" id="add_date_range" class="btn btn-default">
                                    Add Date Range
                                </button>
                                <button type="submit" id="display" class="btn btn-primary" data-link="{{ url('/approach/go-around') }}">
                                    Display
                                </button>
                            </div>
                        </div>

                        <div id="date_range_container"></div>

                        <br /><br />

                        <!-- Chart tabs -->
                        <ul class="nav nav-tabs nav-justified" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#chart_crosstrack" aria-controls="crosstrack" role="tab" data-toggle="tab">
                                    Crosstrack
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#chart_heading" aria-controls="heading" role="tab" data-toggle="tab">
                                    Heading
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#chart_ias" aria-controls="indicated airspeed" role="tab" data-toggle="tab">
                                    Indicated Airspeed
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#chart_vsi" aria-controls="vertical speed indicated" role="tab" data-toggle="tab">
                                    Vertical Speed Indicated
                                </a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane fade in active" id="chart_crosstrack"></div>
                            <div role="tabpanel" class="tab-pane fade" id="chart_heading"></div>
                            <div role="tabpanel" class="tab-pane fade" id="chart_ias"></div>
                            <div role="tabpanel" class="tab-pane fade" id="chart_vsi"></div>
                        </div>

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

            $('#approach_tool').submit(function (e) {
                e.preventDefault();

                // var $charts = $('div[id^="chart_"]').map(function (idx, chart) {
                //     return $(chart).highcharts();
                // });
                var startDates = $('input[name="start_datepicker[]"]').get().map(function (element) {
                    return element.value;
                });
                var endDates = $('input[name="end_datepicker[]"]').get().map(function (element) {
                    return element.value;
                });

                // Remove all existing series from every chart
                charts.forEach(function (chart) {
                    chart.showLoading('Loading ...');
                    while (chart.series.length > 0)
                        chart.series[0].remove(true);
                });

                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    url: '{{ url('/approach/go-around/chart') }}',
                    data: {startDates: startDates, endDates: endDates},
                    success: function (data) {
                        charts.forEach(function (chart) {
                            Object.keys(data).forEach(function (key) {
                                chart.addSeries({
                                    name: key,
                                    type: 'column',
                                    data: data[key][chart.param],
                                    pointPlacement: 'between'
                                });
                            });

                            chart.hideLoading();
                        });
                    },
                    error: function (data) {
                        alert('error');
                    }
                });

                return false;
            });

            var datepickerOptions = {
                dateFormat: 'yy-mm-dd',
                changeYear: true,
                changeMonth: true,
            };

            $('body').on('focus', '.datepicker', function () {
                $(this).datepicker(datepickerOptions);
            });

            $('#add_date_range').click(function () {
                var $startDatepicker = createDatePicker('Start Date:', 'start_datepicker[]')
                    .find('.datepicker').datepicker(datepickerOptions).change(function () {
                        $endDatepicker.find('.datepicker').datepicker('option', 'minDate', getDate(this));
                    }).end();
                var $endDatepicker = createDatePicker('End Date:', 'end_datepicker[]')
                    .find('.datepicker').datepicker(datepickerOptions).change(function () {
                        $startDatepicker.find('.datepicker').datepicker('option', 'maxDate', getDate(this));
                    }).end();

                $('#date_range_container').append($startDatepicker, $endDatepicker);
            }).click();

            function createDatePicker(label, name) {
                return $('<div>', {class: 'col-md-3 col-md-offset-2'}).append(
                    $('<div>', {class: 'form-group'}).append(
                        $('<label>', {text: label}),
                        $('<div>', {class: 'input-group'}).append(
                            $('<input>', {type: 'text', class: 'form-control datepicker', name: name}),
                            $('<span>', {class: 'input-group-addon'}).append(
                                $('<span>', {class: 'glyphicon glyphicon-calendar'})
                            )
                        )
                    )
                );
            }

            function getDate(element) {
                var date;
                try {
                    date = $.datepicker.parseDate('yy-mm-dd', element.value);
                } catch (error) {
                    date = null;
                }

                return date;
            }

            var chartParameters = [
                {chartName: 'crosstrack', chartTitle: 'Crosstrack', chartUnit: 'ft', chartStep: 5.0},
                {chartName: 'heading', chartTitle: 'Heading', chartUnit: 'degrees', chartStep: 1.0},
                {chartName: 'ias', chartTitle: 'Indicated Airspeed', chartUnit: 'kts', chartStep: 5.0},
                {chartName: 'vsi', chartTitle: 'Vertical Speed Indicated', chartUnit: 'ft/min', chartStep: 50.0},
            ];

            var charts = chartParameters.map(function (param) {
                var chart = Highcharts.chart('chart_' + param.chartName, {
                    chart: {
                        type: 'column',
                    },
                    title: {
                        text: 'Histogram for ' + param.chartTitle,
                    },
                    subtitle: {
                        text: '',
                    },
                    xAxis: {
                        title: {
                            text: param.chartTitle + ' Error (' + param.chartUnit + ')',
                        },
                        crosshair: true,
                        startOnTick: true,
                        tickInterval: param.chartStep,
                    },
                    yAxis: {
                        min: 0,
                        title: {
                            text: 'Number of occurrences',
                        },
                    },
                    tooltip: {
                        headerFormat: '<span style="font-size:10px">{point.key} ' + param.chartUnit + '</span><table>',
                        pointFormat: '<tr><td style="color:{series.color};padding:2px">{series.name}: </td>' +
                            '<td style="padding:0"><b>{point.y}</b></td></tr>',
                        footerFormat: '</table>',
                        valueSuffix: ' occurrence(s)',
                        shared: true,
                        useHTML: true,
                    },
                    plotOptions: {
                        column: {
                            pointPadding: 0,
                            groupPadding: 0.1,
                            borderWidth: 0,
                        },
                    },
                });

                // Store param name so we can access it later after an AJAX call
                chart.param = param.chartName;
                return chart;
            });
        });
    </script>
@endsection
