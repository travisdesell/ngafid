<?php namespace NGAFID;

use Illuminate\Database\Eloquent\Model;


class FileUpload extends Model{


    protected $table = 'fdmdm.uploaded_file';
    public $timestamps = false;

    protected $fillable = ['file_name', 'path', 'user_id', 'upload_time', 'n_number', 'fleet_id', 'aircraft_type', 'is_submitted'];

    public function scopeImportStatus($query, $userID, $fleetID)
    {
        return $query->select(
            \DB::raw("COALESCE(uploaded_file.n_number, 'N/A') AS 'n_number'"),
            \DB::raw("COALESCE(a.`aircraft name`, CONCAT(a.make, ' ', a.model)) AS 'aircraft'"),
            \DB::raw("CASE IFNULL(uploaded_file.upload_time, '0000-00-00 00:00:00')
                            WHEN '0000-00-00 00:00:00' THEN 'N/A'
                            ELSE uploaded_file.upload_time
                        END AS 'uploaded'"),
            \DB::raw("CASE
                            WHEN is_submitted = 1 THEN 'Yes'
                            ELSE 'No'
                        END AS 'submitted'"),
            \DB::raw("CASE
                            WHEN (uploaded_file.total_num_of_data/uploaded_file.imported_num_of_data = 1 AND error = 0 AND is_submitted = 1) THEN 'Imported'
                            WHEN (error = 0 AND is_submitted = 1) THEN 'Pending'
                            ELSE 'Failed'
                        END AS 'status'")
        )
        ->leftJoin(\DB::raw('fdm_test.organization o'), 'o.id', '=', 'uploaded_file.fleet_id')
        ->leftJoin(\DB::raw('fdm_test.flight_id f'), 'f.id', '=', 'uploaded_file.flight_id')
        ->leftJoin(\DB::raw('fdm_test.aircraft_list a'), 'a.id', '=', 'uploaded_file.aircraft_type')
        ->where(\DB::raw('uploaded_file.user_id'), '=', $userID)
        ->where(\DB::raw('uploaded_file.fleet_id'), '=', $fleetID)
        ->orderBy(\DB::raw('uploaded_file.upload_time'), 'desc');
    }

}