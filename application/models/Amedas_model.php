<?php

class Amedas_model extends CI_Model
{
    public function __construct()
    {
        $this->load->helper('phpquery');
        $this->load->model('tables/Amedas_tbl');
        $this->load->model('tables/Amedas_stations_tbl');
    }

    public function scrapingAmedas($start_index, $batch_sise, $date)
    {
        // 配列の初期化
        $amedas_data_array = [];

        // 日付がなければ昨日
        if(!$date)
        {
            $year = date('Y', strtotime('-1 day'));
            $month = date('m', strtotime('-1 day'));
            $day = date('d', strtotime('-1 day'));
        }
        else
        {
            // バリデーション
            if(preg_match('/\A[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}\z/', $date) == false)
            {
                return 'invalid_date';
            }

            $date_array = explode('-',$date);
            $year = $date_array[0];
            $month = $date_array[1];
            $day = $date_array[2];
        }

        // アメダス観測点をロード
        $amedas_stations = $this->Amedas_stations_tbl->getAmedasStations();

        // バッチに分割
        $amedas_stations_batch = array_slice($amedas_stations, $start_index, $batch_sise);

        $k=0;
        foreach($amedas_stations_batch as $amedas_station)
        {
            $k++;
            $prec_no = $amedas_station->prec_no;
            $block_no = $amedas_station->block_no;

            // 降水量、風速データの取得
            if($amedas_station->capital_flag)
            {
                $html = file_get_contents("https://www.data.jma.go.jp/obd/stats/etrn/view/daily_s1.php?prec_no=".$prec_no."&block_no=".$block_no."&year=".$year."&month=".$month);
            }
            else
            {
                $html = file_get_contents("https://www.data.jma.go.jp/obd/stats/etrn/view/daily_a1.php?prec_no=".$prec_no."&block_no=".$block_no."&year=".$year."&month=".$month);
            }

            $dom = phpQuery::newDocument($html);

            $i = 0;
            foreach($dom['table:eq(5) tr'] as $row)
            {
                $i++;
                if($i < 5)
                {
                    continue;
                }
                $tr_day = pq($row)->find('td:eq(0)')->text();
                if($tr_day == $day)
                {
                    if($amedas_station->capital_flag)
                    {
                        $pricipitation = pq($row)->find('td:eq(3)')->text();
                        $wind_speed = pq($row)->find('td:eq(14)')->text();
                        $wind_direction = pq($row)->find('td:eq(15)')->text();                    
                    }
                    else
                    {
                        $pricipitation = pq($row)->find('td:eq(1)')->text();
                        $wind_speed = pq($row)->find('td:eq(10)')->text();
                        $wind_direction = pq($row)->find('td:eq(11)')->text();                    
                    }
                    $wind_direction = trim($wind_direction, ")]");
                    $amedas_data = [
                        'prec_no' => $prec_no,
                        'block_no' => $block_no,
                        'thander_flag' => 0,
                        'pricipitation' => $pricipitation,
                        'wind_speed' => $wind_speed,
                        'wind_direction' => $wind_direction,
                        'date' => $year."-".$month."-".$day
                    ];
                    break;
                }
            }
            // 現在使用されていない観測所
            if($wind_direction == "///" || $wind_direction == "")
            {
                continue;
            }

            // 雷雲フラグの取得
            if(!$amedas_station->capital_flag)
            {
                $amedas_data['thander_flag'] = 2;
            }
            else
            {
                $html2 = file_get_contents("https://www.data.jma.go.jp/obd/stats/etrn/view/hourly_a1.php?prec_no=".$prec_no."&block_no=".$block_no."&year=".$year."&month=".$month."&day=".$day."&view=p1");
                $dom2 = phpQuery::newDocument($html2);
                $j = 0;
                foreach($dom2['table:eq(4) tr'] as $row)
                {
                    $j++;
                    if($j < 2)
                    {
                        continue;
                    }
                    $alt = pq($row)->find('td:eq(14) img')->attr('alt');
                    if($alt == "雷電")
                    {
                        $amedas_data['thander_flag'] = 1;
                        break;
                    }
                }
            }
            // 配列に追加
            array_push($amedas_data_array, $amedas_data);
        }

        // 配列をリターン
        return $amedas_data_array;
    }

    public function saveAmedas($amedas_data_array)
    {
        if( !$this->Amedas_tbl->saveAmedas($amedas_data_array))
        {
            return FALSE;
        }
        return TRUE;
    }

    public function getAmedas($request, $lon, $lat)
    {
        // 最寄りの観測点の県NoとブロックNoを求める
        $stations = $this->Amedas_stations_tbl->getNearestStation($lon, $lat);
        $prec_no = $stations[0]->prec_no;
        $block_no = $stations[0]->block_no;

        // 検索開始日付 & 終了日付
        $start_date = $request['start_date'];
        $end_date = $request['end_date'];

        // 落雷指定があるとき、同一県内の都市の観測所データから落雷の有無を取得
        if($request['thander'] != '指定なし')
        {
            // 同一県内の都市の観測所を取得
            $stations = $this->Amedas_stations_tbl->getCapitalAmedasStationsFromSamePrefecture($prec_no);
            $block_no_array = [];
            foreach($stations as $station)
            {
                array_push($block_no_array, $station->block_no);
            }

            // 落雷ありの場合
            if($request['thander'] == 'あり')
            {
                $amedas_data_array = $this->Amedas_tbl->getAmedasFromSamePrefectureCapital($prec_no, $block_no_array, $start_date, $end_date, 1);
            }
            // 落雷なしの場合
            if($request['thander'] == 'なし')
            {
                $amedas_data_array = $this->Amedas_tbl->getAmedasFromSamePrefectureCapital($prec_no, $block_no_array, $start_date, $end_date, 0);
            }
            // 落雷条件を満たす日付を取得
            $date_array = [];
            foreach($amedas_data_array as $amedas_data)
            {
                array_push($date_array, $amedas_data->date);
            }
            $date_array = array_unique($date_array);
        }
        else
        {
            $date_array = FALSE;
        }

        return $this->Amedas_tbl->getAmedas($request, $prec_no, $block_no, $date_array);
    }
}