<?php
namespace NGAFID;

use Eloquent;

/**
 * NGAFID\Airport
 *
 * @property int $id
 * @property string|null $AirportName
 * @property string|null $AirportCode
 * @property string|null $Runway
 * @property float|null $pointALat
 * @property float|null $pointALong
 * @property float|null $pointBLat
 * @property float|null $pointBLong
 * @property float|null $pointCLat
 * @property float|null $pointCLong
 * @property float|null $pointDLat
 * @property float|null $PointDLong
 * @property float|null $touchdownLat
 * @property float|null $touchdownLong
 * @property float|null $extendedcenterlineLat
 * @property float|null $extendedcenterlineLong
 * @property float|null $runwayCourse
 * @property float|null $glidepathAngle
 * @property int|null $tdze
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereAirportCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereAirportName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereExtendedcenterlineLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereExtendedcenterlineLong($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereGlidepathAngle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         wherePointALat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         wherePointALong($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         wherePointBLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         wherePointBLong($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         wherePointCLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         wherePointCLong($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         wherePointDLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         wherePointDLong($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereRunway($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereRunwayCourse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereTdze($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereTouchdownLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\NGAFID\Airport
 *         whereTouchdownLong($value)
 * @mixin \Eloquent
 */
class Airport extends Eloquent
{
    public $timestamps = false;

    public function runways()
    {
        return $this->hasMany('NGAFID\Runway', 'airport_id', 'id');
    }
}
