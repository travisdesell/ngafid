<?php
namespace NGAFID;

use Eloquent;

class FlightID extends Eloquent
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'flight_id';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'n_number',
        'time',
        'date',
        'fleet_id',
        'aircraft_type',
        'origin',
        'destination',
    ];

    /*************************************************************************-
     * Model Scopes
     */

    public function scopeFlightDetails(
        $query,
        $fleet,
        $startDate = null,
        $endDate = null,
        $archived = '',
        $sort = null,
        $column = null,
        $duration = '00:00',
        $flightID = ''
    ) {
        $query->select(
            'flight_id.id',
            'n_number',
            'date',
            'time',
            'origin',
            'destination',
            'duration',
            'aircraft name',
            'year',
            'make',
            'model',
            \DB::raw("COALESCE(num_exceedances, 0) num_events"),
            \DB::raw("main_exceedances.archived AS 'archived'")
        )
            ->distinct();

        $query->leftJoin(
            'main_exceedances',
            'main_exceedances.flight',
            '=',
            'flight_id.id'
        );

        $query->leftJoin(
            'aircraft_list',
            'aircraft_list.id',
            '=',
            'flight_id.aircraft_type'
        );

        $query->where('flight_id.fleet_id', '=', $fleet);

        if ($archived !== '') {
            $query->where(
                \DB::raw('main_exceedances.`archived`'),
                '=',
                \DB::raw("'$archived'")
            );
        }

        if ($startDate !== '') {
            $query->where(
                \DB::raw('flight_id.`date`'),
                '>=',
                \DB::raw("'startDate'")
            );
        }

        if ($endDate !== '') {
            $query->where(
                \DB::raw('flight_id.`date`'),
                '<=',
                \DB::raw("'$endDate'")
            );
        }

        if ($column !== '') {
            $query->where(
                \DB::raw('main_exceedances.' . $column),
                '=',
                \DB::raw("'Y'")
            );
        }

        if (($duration !== '00:00') && $sort == 5) {
            $query->where(
                \DB::raw("TIME_FORMAT(flight_id.`duration`, '%H:%i')"),
                '>=',
                \DB::raw("'$duration'")
            );
        }

        if ($flightID !== '' && $flightID > 0) {
            $query->where(\DB::raw('flight_id.`id`'), '=', $flightID);
        }

        switch ($sort) {
            case 1:
                $query->orderBy('num_exceedances', 'DESC');
                break;

            case 2:
                $query->orderBy('date', 'ASC');
                $query->orderBy('time', 'ASC');
                break;

            case 3:
                $query->orderBy('destination', 'ASC');
                break;

            case 4:
                $query->orderBy('origin', 'ASC');
                break;

            case 5:
                $query->orderBy('duration', 'ASC');
                break;

            default:
                $query->orderBy('date', 'DESC');
                $query->orderBy('time', 'DESC');
        }

        return $query;
    }

    public function scopeExceedanceStats($query, $fleet)
    {
        return \DB::select(
            \DB::raw(
                "SELECT
                    f.aircraft_type,
                    SUM(CASE WHEN excessive_roll = 'Y' THEN 1 ELSE 0 END) AS 'ExcessiveRoll',
                    SUM(CASE WHEN excessive_pitch = 'Y' THEN 1 ELSE 0 END) AS 'ExcessivePitch',
                    SUM(CASE WHEN excessive_speed = 'Y' THEN 1 ELSE 0 END) AS 'ExcessiveSpeed',
                    SUM(CASE WHEN high_cht = 'Y' THEN 1 ELSE 0 END) AS 'HighCHT',
                    SUM(CASE WHEN high_altitude = 'Y' THEN 1 ELSE 0 END) AS 'HighAltitude',
                    SUM(CASE WHEN low_fuel = 'Y' THEN 1 ELSE 0 END) AS 'LowFuel',
                    SUM(CASE WHEN low_oil_pressure = 'Y' THEN 1 ELSE 0 END) AS 'LowOilPress',
                    SUM(CASE WHEN low_airspeed_on_approach = 'Y' THEN 1 ELSE 0 END) AS 'LowAirspeedApproach',
                    SUM(CASE WHEN excessive_lateral_acceleration = 'Y' THEN 1 ELSE 0 END) AS 'ExcessiveLat',
                    SUM(CASE WHEN excessive_vertical_acceleration = 'Y' THEN 1 ELSE 0 END) AS 'ExcessiveVert',
                    SUM(CASE WHEN excessive_longitudinal_acceleration = 'Y' THEN 1 ELSE 0 END) AS 'ExcessiveLon',
                    SUM(CASE WHEN low_airspeed_on_climbout = 'Y' THEN 1 ELSE 0 END) AS 'LowAirspeedClimbout',
                    SUM(CASE WHEN excessive_vsi_on_final = 'Y' THEN 1 ELSE 0 END) AS 'ExcessiveVSI'
                FROM main_exceedances m
                INNER JOIN flight_id f
                ON f.id = m.flight
                WHERE f.fleet_id = $fleet
                AND m.archived = 'N'
                GROUP BY f.aircraft_type"
            )
        );
    }

    public function scopeTotalFlightsByAircraft($query, $fleet)
    {
        return \DB::select(
            \DB::raw(
                "SELECT aircraft_type AS 'aircraft_id', COUNT(*) AS 'total'
                FROM flight_id
                WHERE fleet_id = $fleet
                GROUP BY aircraft_type;
            "
            )
        );
    }

    public function scopeTotalFlightHours($query, $fleet)
    {
        return $query->select(
            \DB::raw("SUM(TIME_TO_SEC(duration)) AS 'duration'")
        )
            ->where('fleet_id', '=', "$fleet");
    }

    /*************************************************************************
     * Model Eloquent Relationships
     */

    public function fleet()
    {
        return $this->belongsTo('NGAFID\Fleet', 'fleet_id');
    }

    public function aircraft()
    {
        return $this->hasOne('NGAFID\Aircraft', 'id', 'aircraft_type');
    }

    public function mainTableData()
    {
        return $this->hasMany('NGAFID\Main', 'flight');
    }

    public function selfDefinedApproaches()
    {
        return $this->hasMany('NGAFID\SelfDefinedApproach', 'flight', 'id');
    }

    /*************************************************************************
     * Public Methods
     */

    public function hasDataInMainTable()
    {
        $this->mainTableData()->exists();
    }
}
