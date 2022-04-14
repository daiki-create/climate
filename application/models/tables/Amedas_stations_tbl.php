<?php

class Amedas_stations_tbl extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAmedasNotCapitalStations()
    {
        return $this
            ->db
            ->where('capital_flag', 0)
            ->get('amedas_stations')
            ->result();
    }

    public function getAmedasCapitalStations()
    {
        return $this
            ->db
            ->where('capital_flag', 1)
            ->get('amedas_stations')
            ->result();
    }

    public function saveAmedasStations($amedas_stations_data)
    {
        if(
            $this
            ->db
            ->insert_batch(
                'amedas_stations',
                $amedas_stations_data
            )
        )
        {
            return TRUE;
        }
        return FALSE;
    }

    public function getAmedasStations()
    {
        return $this
            ->db
            ->get('amedas_stations')
            ->result();
    }

    public function getNearestStation($lon, $lat)
    {
        return $this
            ->db
            ->query("
                select prec_no, block_no, st_name, 
                (6371 * acos(cos(radians($lat)) * cos(radians(lat)) * cos(radians(lon) - radians($lon)) + sin(radians($lat)) * sin(radians(lat)))) 
                as distance from amedas_stations order by distance limit 1
            ")
            ->result();
    }

    public function getCapitalAmedasStationsFromSamePrefecture($prec_no)
    {
        return $this
            ->db
            ->where('prec_no', $prec_no)
            ->where('capital_flag', 1)
            ->get('amedas_stations')
            ->result();
    }
}