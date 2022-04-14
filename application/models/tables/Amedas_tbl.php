<?php

class Amedas_tbl extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    public function updateWind($amedas_data_array, $data)
    {
        return $this
        ->db
        ->update_batch('amedas', $amedas_data_array);
    }

    public function updateThander($block_no, $date)
    {
        // var_dump($block_no);
        // var_dump($date);
        // exit;
        $data = array(
            'thander_flag' => 1
        );
        return $this
        ->db
        ->where('block_no', $block_no)
        ->where('date', $date)
        ->update('amedas', $data);
    }

    public function saveAmedas($amedas_data_array)
    {
        if(
            $this
            ->db
            ->insert_batch(
                'amedas',
                $amedas_data_array
            )
        )
        {
            return TRUE;
        }
        return FALSE;
    }

    public function getAmedasFromSamePrefectureCapital($prec_no, $block_no_array, $start_date, $end_date, $thander_flag)
    {
        return $this
        ->db
        ->where('prec_no', $prec_no)
        ->where_in('block_no', $block_no_array)
        ->where('thander_flag', $thander_flag)
        ->where("date BETWEEN '$start_date' AND '$end_date'")
        ->get('amedas')
        ->result();
    }

    public function getAmedas($request, $prec_no, $block_no, $date_array)
    {
        $pricipitation =$request['precipitation'];
        $wind_speed = $request['wind_speed'];

        $sql = $this
        ->db
        ->where('prec_no', $prec_no)
        ->where('block_no', $block_no)
        ->where('pricipitation >=', $pricipitation)
        ->where('wind_speed >=', $wind_speed);

        if($date_array)
        {
            $sql = $sql
            ->where_in("date", $date_array);
        }
        else
        {
            $start_date = $request['start_date'];
            $end_date = $request['end_date'];
            $sql = $sql
            ->where("date BETWEEN '$start_date' AND '$end_date'");
        }

        if($request['wind_direction'] != '指定なし')
        {
            $sql = $sql
                ->where('wind_direction', $request['wind_direction']);
        }

        return $sql
            ->order_by('date','DESC')
            ->group_by('date')
            ->get('amedas')
            ->result();
        }
}