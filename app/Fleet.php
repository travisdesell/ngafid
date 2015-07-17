<?php namespace NGAFID;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Fleet extends Model {


	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'organization';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
    protected $fillable = ['name', 'address', 'city', 'state', 'zip_code', 'country', 'phone', 'fax'];

    public function scopeDuration($query, $fleetID)
    {
        return $query->where('fleet_id', '=', $fleetID);
    }

    public function user()
    {
        return $this->hasMany('NGAFID\User');
    }

    public function flights(){
        return $this->hasMany('NGAFID\FlightID');
    }


}
