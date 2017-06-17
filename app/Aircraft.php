<?php namespace NGAFID;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Aircraft extends Model {


	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'aircraft_list';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	//protected $fillable = ['aircraft name', 'year', 'make', 'model'];

    public function flights()
    {
        return $this->hasMany('NGAFID\FlightID', 'aircraft_type');
    }

    public function scopeUniqueAircraft($query, $fleet)
    {
        return $query->select(\DB::raw('aircraft_list.id AS id'), 'aircraft name', 'year', 'make', 'model')
            ->distinct()
            ->leftJoin('flight_id','flight_id.aircraft_type','=','aircraft_list.id')
            ->where('flight_id.fleet_id', '=', $fleet);
    }

    public function scopeAircraftTrendDetection($query, $fleetID, $startDate = '', $endDate = '', $selectedEvent = '', $selectedAircraft = '')
    {
        $sql = \DB::raw(
            "SELECT q.`name`, DATE_FORMAT(qs.`date`, '%m-%Y') AS 'date',
                  (qs.event_count/qf.total_events)*100 AS 'percentage'
                FROM query_sums qs
                INNER JOIN queries q
                  ON q.id = qs.query_id
                INNER JOIN query_fleet_sums qf
                  ON qf.fleet_id = qs.fleet_id AND qf.`date` = qs.`date` AND qf.`aircraft_id` = qs.`aircraft_id`
                WHERE qs.fleet_id = {$fleetID}");

        if($selectedEvent != '')
        {
            $sql .= \DB::raw(" AND qs.query_id = {$selectedEvent}");
        }
        if($selectedAircraft != '')
        {
            $sql .= \DB::raw(" AND qf.aircraft_id = {$selectedAircraft}");
        }

        if($startDate != '')
        {
            $sql .= \DB::raw(" AND qf.`date` >= '{$startDate}'");
        }

        if($endDate != '')
        {
            $sql .= \DB::raw(" AND qf.`date` <= '{$endDate}'");
        }

        $sql .= \DB::raw(" ORDER BY qf.`date` ASC");

        return \DB::select($sql);
    }

}
