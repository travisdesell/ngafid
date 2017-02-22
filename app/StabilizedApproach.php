<?php namespace NGAFID;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;


class StabilizedApproach extends Model {

    protected $table = 'stabilized_approach';
    protected $maxVal = 30;
    protected $minVal = 20;
    protected $slowSpd = -5;
    protected $fastSpd = 10;
    protected $lowHAT = -5;
    protected $highHAT = 10;

    public function scopeApproachData($query, $fleet, $runway, $params, $date)
    {
        $date  = explode('-', $date);
        $month = $date[1];
        $year  = $date[0];

        switch($params){
            //cross track error
            case 'CTE':
                $fields = "avgCrsTrk,
                            CASE
                                WHEN ABS(avgCrsTrk) > {$this->maxVal} THEN 'rgba(223, 83, 83, .7)'
                                WHEN ABS(avgCrsTrk) > {$this->minVal} AND ABS(avgCrsTrk) <= {$this->maxVal} THEN 'rgba(255, 204, 0, .7)'
                                ELSE 'rgba(0, 153, 151, .7)'
                            END 'avgCrsTrkColor',
                            LEAST(ABS(minCrsTrk), ABS(maxCrsTrk)) AS 'minCrsTrk',
                            CASE
                                WHEN LEAST(ABS(minCrsTrk), ABS(maxCrsTrk)) > {$this->maxVal} THEN 'rgba(223, 83, 83, .7)'
                                WHEN LEAST(ABS(minCrsTrk), ABS(maxCrsTrk)) > {$this->minVal} AND LEAST(ABS(minCrsTrk), ABS(maxCrsTrk)) <= {$this->maxVal} THEN 'rgba(255, 204, 0, .7)'
                                ELSE 'rgba(0, 153, 151, .7)'
                            END 'minCrsTrkColor',
                            GREATEST(ABS(minCrsTrk), ABS(maxCrsTrk)) AS 'maxCrsTrk',
                            CASE
                                WHEN GREATEST(ABS(minCrsTrk), ABS(maxCrsTrk)) > {$this->maxVal} THEN 'rgba(223, 83, 83, .7)'
                                WHEN GREATEST(ABS(minCrsTrk), ABS(maxCrsTrk)) > {$this->minVal} AND GREATEST(ABS(minCrsTrk), ABS(maxCrsTrk)) <= {$this->maxVal} THEN 'rgba(255, 204, 0, .7)'
                                ELSE 'rgba(0, 153, 151, .7)'
                            END 'maxCrsTrkColor',
                            0 AS 'yValues'";
                break;

            //high/low - fast/slow
            case 'HLFS':
                $fields = "avgHAT,
                            CASE
                                WHEN avgWind < 2 THEN 'diamond'
                                WHEN avgWind < 10 THEN 'square'
                                ELSE 'triangle'
                            END 'tailWindSymbol',
                            CASE
                                WHEN (avgHAT NOT BETWEEN ({$this->lowHAT}*2) AND ({$this->highHAT}*2)) OR (avgSpeed NOT BETWEEN ({$this->slowSpd}*2) AND ({$this->fastSpd}*2)) THEN 'rgba(223, 83, 83, .7)'
                                WHEN (avgHAT NOT BETWEEN {$this->lowHAT} AND {$this->highHAT}) OR (avgSpeed NOT BETWEEN {$this->slowSpd} AND {$this->fastSpd}) THEN 'rgba(255, 204, 0, .7)'
                                ELSE 'rgba(0, 153, 151, .7)'
                            END 'symbolColor',
                            avgWind AS 'tailWind',
                            avgSpeed";
                break;
        }

        //echo $query->toSql();

         return $query->select(\DB::raw("id AS 'rowID', nNumber, flight, fltDate, timeOfFinal,
                {$fields},
                @tot:= (SELECT  COUNT(*)
                    FROM    stabilized_approach t2
                    WHERE   t2.flight = stabilized_approach.flight AND t2.airport_id = {$runway}
                ) AS 'total',
                @ctr:= IFNULL(@ctr, 0) + 1 AS 'apprNo',
	            IF (@ctr >= @tot, @ctr:= 0, @ctr:= @ctr)"))
            ->where('fleet_id', '=', $fleet)
            ->where('airport_id', '=', $runway)
            ->whereRaw("YEAR(fltDate) = {$year}")
            ->whereRaw("MONTH(fltDate) = {$month}")
            ->orderBy('flight', 'ASC')
            ->orderBy('timeOfFinal', 'ASC');

    }

    public function scopeGraphApproach($query, $flight, $flightTime, $start, $end)
    {
        return \DB::select(\DB::raw(
            "SELECT AddTime('{$flightTime}', COALESCE(SEC_TO_TIME(FLOOR(time/1000)), 0)) AS time_sec,
                roll_attitude AS roll,
                CASE
                    WHEN roll_attitude < 30 THEN 'rgba(0, 153, 151, .7)'
                    WHEN roll_attitude > 30 AND roll_attitude < 40 THEN 'rgba(255, 204, 0, .7)'
                    ELSE 'rgba(223, 83, 83, .7)'
                END AS 'roll_color',
                pitch_attitude AS pitch,
                'black' AS 'pitch_color',
                indicated_airspeed AS ias,
                CASE
                    WHEN indicated_airspeed > 70 THEN 'red'
                    WHEN indicated_airspeed > 65 THEN 'yellow'
                    ELSE 'green'
                END AS 'ias_color',
                vertical_airspeed AS vsi,
                CASE
                    WHEN vertical_airspeed < -1000 THEN 'red'
                    ELSE 'green'
                END AS 'vsi_color'
            FROM main
            WHERE flight = {$flight}
              AND `time` BETWEEN {$start} AND {$end}
            ORDER BY time_sec ASC;"
        ));
    }
}