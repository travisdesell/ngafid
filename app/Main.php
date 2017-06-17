<?php namespace NGAFID;

use Illuminate\Database\Eloquent\Model;


class Main extends Model{

    protected $table = 'main';
    public $timestamps = false;

    public function flightIDData()
    {
        return $this->belongsTo('NGAFID\FlightID', 'flight');
    }

    public function scopeFlightParameters($query, $parameters, $flightID)
    {
        return $query->select(\DB::raw($parameters))
                    ->where('flight', '=', $flightID)
                    ->orderBy('time', 'ASC');
    }

    public function scopeFlightSummary($query, $flightID)
    {
        return $query->select(\DB::raw("
                AVG(indicated_airspeed) AS 'avg_airspeed', MAX(indicated_airspeed) AS 'max_airspeed', MIN(indicated_airspeed) AS 'min_airspeed',
                AVG(msl_altitude) AS 'avg_msl', MAX(msl_altitude) AS 'max_msl', MIN(msl_altitude) AS 'min_msl',
                AVG(eng_1_rpm) AS 'avg_eng_rpm', MAX(eng_1_rpm) AS 'max_eng_rpm', MIN(eng_1_rpm) AS 'min_eng_rpm',
                AVG(pitch_attitude) AS 'avg_pitch', MAX(pitch_attitude) AS 'max_pitch', MIN(pitch_attitude) AS 'min_pitch',
                AVG(roll_attitude) AS 'avg_roll', MAX(roll_attitude) AS 'max_roll', MIN(roll_attitude) AS 'min_roll',
                AVG(vertical_airspeed) AS 'avg_vert', MAX(vertical_airspeed) AS 'max_vert', MIN(vertical_airspeed) AS 'min_vert'"))
            ->where('flight', '=', $flightID)
            ->groupBy('flight');
    }


}