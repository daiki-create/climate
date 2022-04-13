<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Scraping extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->helper('file');
		$this->load->library('email');

		$this->load->model('Amedas_model');
		$this->load->model('Amedas_stations_model');
		$this->load->model('Liden_model');
	}

	public function thanderPatch($year)
	{
		$date = read_file('../var/thander_patch/'.$year.'.txt');
		$date_array = explode('-',$date);
        $date_array_year = $date_array[0];
		$block_no = "";
		if($date_array_year == $year)
		{
			$result = $this->Amedas_model->thanderPatch($date);
			if($result == "updated")
			{
				$next_date = date('Y-m-d', strtotime('-1 day', strtotime($date)));
				write_file('../var/thander_patch/'.$year.'.txt', $next_date, 'w');
				log_message('debug', 'patch success!!!!!!!!!!!!!!!!!!!!!!!');
				exit;
			}
			elseif(count($result) == 2)
			{
				$block_no = $result[0];
				$date == $result[1];
			}
		}
		// メールで山崎に報告
		$this->email->from('info@weather-info-ss.com', 'CLIMATE SYSTEM');
		$this->email->to('6280ikiad@gmail.com');
		$this->email->subject('落雷データ修正スクレイピング失敗');
		$this->email->message('日付：'.$date.'ブロック：'.$block_no);
		$this->email->send();
		log_message('debug', $date.'ブロック：'.$block_no.'--pacth FAILED!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
	}

	// 1分起きに実行
	public function scrapingAmedasCronJob($batch_no)
	{
		$start_time = microtime(true);

		// ファイルから日付、開始インデックス、バッチNo.を取得
		$date = trim(read_file('../var/amedas'.$batch_no.'.txt'));

		// exe scrapingAmedas
		if($this->scrapingAmedasForCronJob(163 * ($batch_no - 1), 163, $date))
		{

			$next_date = date('Y-m-d', strtotime('-1 day', strtotime($date)));

			write_file('../var/amedas'.$batch_no.'.txt', $next_date, 'w');
			echo('cron success.');
			log_message('debug', '日付：'.$date.
			'	バッチNo：'.$batch_no);
			log_message('debug', 'cron success!!!!!!!!!!!!!!!!!!!!!!!');
		}
		else{
			// メールで山崎に報告
			$this->email->from('info@weather-info-ss.com', 'CLIMATE SYSTEM');
			$this->email->to('6280ikiad@gmail.com');
			$this->email->subject('【過去】アメダスデータスクレイピング失敗');
			$this->email->message('日付：'.$date.
			'	バッチNo：'.$batch_no);
			$this->email->send();
			log_message('debug', '日付：'.$date.
			'	バッチNo：'.$batch_no);
			echo('cron FAILED.');
			log_message('debug', 'cron FAILED!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
		}
		$end_time = microtime(true);
		$processing_time = $end_time - $start_time;
		log_message('debug', '実行時間：'.$processing_time);
	}

	// アメダス過去自動cron用
	public function scrapingAmedasForCronJob($start_index, $batch_sise, $date)
	{
		// if ( is_cli() ) 
		{
			log_message('debug', '【過去】scraping amedas start.');

			// 気象庁の過去のデータをスクレイピング
			$amedas_data_array = $this->Amedas_model->scrapingAmedas($start_index, $batch_sise, $date);
			if($amedas_data_array == "invalid_date")
			{
				echo 'invalid_date';
			}
			// 保存
			if($this->Amedas_model->saveAmedas($amedas_data_array))
			{
				return True;
			}
			return FALSE;
		}
	}

	

















	public function scrapingLidenCronJob($day_batch_no)
	{
		$start_time = microtime(true);

		// ファイルから日付、開始インデックス、バッチNo.を取得
		$date = trim(read_file('../var/liden/'.$day_batch_no.'.txt'));

		// exe scrapingAmedas
		if($date >= '2021-07-01')
		{
			if($this->scrapingLiden($date, $date, $day_batch_no))
			{
				$next_date = date('Y-m-d', strtotime('-1 day', strtotime($date)));
				write_file('../var/liden/'.$day_batch_no.'.txt', $next_date, 'w');
				echo('cron success.');
				log_message('debug', 'cron success!!!!!!!!!!!!!!!!!!!!!!!');
			}
			else
			{
				// メールで山崎に報告
				$this->email->from('everyday-crawler@weather-info-ss.com', 'CLIMATE SYSTEM');
				$this->email->to('6280ikiad@gmail.com');
				$this->email->subject('【定期】ライデンデータスクレイピング失敗');
				$this->email->message('日付：'.$date."	バッチNo：".$day_batch_no);
				$this->email->send();
				echo('cron FAILED.');
				log_message('debug', 'cron FAILED!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
			}
			$end_time = microtime(true);
			$processing_time = $end_time - $start_time;
			log_message('debug', '実行時間：'.$processing_time);
		}
		else
		{
			echo('nothing to do. completed.');
			log_message('debug', 'nothing to do. completed.');
		}
	}




























	public function scrapingCurrentAmedasCronJob()
	{
		$start_time = microtime(true);

		// ファイルから日付、開始インデックス、バッチNo.を取得
		$txt = read_file('../var/everydayAmedas.txt');
		$array = explode(',', $txt);
		$start_index = $array[0];
		$batch_no = $array[1];
		$completed_flag = trim($array[2]);
		$yesterday = date('Y-m-d', strtotime('-2 day'));
		$date = date('Y-m-d', strtotime('-1 day'));

		log_message('debug', $txt);
		log_message('debug', $date);
		log_message('debug', $yesterday.'--1');
		log_message('debug', $completed_flag);
		// exe scrapingAmedas
		if($completed_flag == $yesterday.'--1')
		{
			if($this->scrapingAmedas($start_index, 163))
			{
				if($batch_no == 6)
				{
					$next_start_index = 0;
					$next_batch_no = 1;
					$next_completed_flag = $date.'--1';
				}
				else{
					$next_start_index = $start_index + 163;
					$next_batch_no = $batch_no + 1;
					$next_completed_flag = $completed_flag;
				}
				$data = $next_start_index.",".$next_batch_no.",".$next_completed_flag;
				write_file('../var/everydayAmedas.txt', $data, 'w');
				echo('cron success.');
				log_message('debug', 'cron success!!!!!!!!!!!!!!!!!!!!!!!');
			}
			else{
				// メールで山崎に報告
				$this->email->from('info@weather-info-ss.com', 'CLIMATE SYSTEM');
				$this->email->to('6280ikiad@gmail.com');
				$this->email->subject('【過去】アメダスデータスクレイピング失敗');
				$this->email->message('日付：'.$date.
										'	開始インデックス：'.$start_index.
										'	バッチNo：'.$batch_no);
				$this->email->send();
				echo('cron FAILED.');
				log_message('debug', 'cron FAILED!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
			}
			$end_time = microtime(true);
			$processing_time = $end_time - $start_time;
			log_message('debug', '実行時間：'.$processing_time);
		}
		else
		{
			echo('nothing to do. completed.');
			log_message('debug', 'nothing to do. completed.');
		}
	}

	// アメダス手動用 or 定期スクレイピング用
	public function scrapingAmedas($start_index, $batch_sise, $date=FALSE)
	{
		// if ( is_cli() ) 
		{
			if($date)
			{
				log_message('debug', '【手動】scraping amedas start.');
			}
			else{
				log_message('debug', '【定期】scraping amedas start.');
			}

			// 気象庁の過去のデータをスクレイピング
			$amedas_data_array = $this->Amedas_model->scrapingAmedas($start_index, $batch_sise, $date);
			if($amedas_data_array == "invalid_date")
			{
				echo 'invalid_date';
				return FALSE;
			}
			// 保存
			if($this->Amedas_model->saveAmedas($amedas_data_array))
			{
				echo 'scraping amedas success.';
				return TRUE;
			}
			// メールで山崎に報告
			$this->email->to('6280ikiad@gmail.com');
			if($date)
			{
				$this->email->from('info@weather-info-ss.com', 'CLIMATE SYSTEM');
				$this->email->subject('【手動】アメダスデータスクレイピング失敗');
				$this->email->message('日付：'.$date.
								'	開始インデックス：'.$start_index.
								'	バッチサイズ：'.$batch_sise);
				$this->email->send();
			}
			return FALSE;
		}
		// die('not CLI.');
	}

















	public function scrapingCurrentLidenCronJob()
	{
		$start_time = microtime(true);

		// ファイルから日付、開始インデックス、バッチNo.を取得
		$txt = read_file('../var/everydayLiden.txt');
		$array = explode(',', $txt);
		$day_batch_no = intval($array[0]);
		$completed_flag = trim($array[1]);
		$yesterday = date('Y-m-d', strtotime('-2 day'));
		$date = date('Y-m-d', strtotime('-1 day'));

		$date_array = explode('-', $date);
		$month = $date_array[1];

		log_message('debug', $txt);
		log_message('debug', $date);
		log_message('debug', $yesterday.'--1');
		log_message('debug', $completed_flag);
		// exe scrapingAmedas
		if($completed_flag == $yesterday.'--1')
		{
			if($this->scrapingCurrentLiden($day_batch_no))
			{
                if($month=="07" ||$month=="08" ||$month=="09")
				{
					if($day_batch_no == 6)
					{
						$next_day_batch_no = 1;
						$next_completed_flag = $date.'--1';
					}
					else
					{
						$next_day_batch_no = $day_batch_no + 1;
						$next_completed_flag = $completed_flag;
					}
				}
				else
				{
					if($day_batch_no == 4)
					{
						$next_day_batch_no = 1;
						$next_completed_flag = $date.'--1';
					}
					else
					{
						$next_day_batch_no = $day_batch_no + 1;
						$next_completed_flag = $completed_flag;
					}
				}				
				$data = $next_day_batch_no.','.$next_completed_flag;
				write_file('../var/everydayLiden.txt', $data, 'w');
				echo('cron success.');
				log_message('debug', 'cron success!!!!!!!!!!!!!!!!!!!!!!!');
			}
			else
			{
				// メールで山崎に報告
				$this->email->from('everyday-crawler@weather-info-ss.com', 'CLIMATE SYSTEM');
				$this->email->to('6280ikiad@gmail.com');
				$this->email->subject('【定期】ライデンデータスクレイピング失敗');
				$this->email->message('日付：'.date()."	バッチNo：".$day_batch_no);
				$this->email->send();
				echo('cron FAILED.');
				log_message('debug', 'cron FAILED!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
			}
			$end_time = microtime(true);
			$processing_time = $end_time - $start_time;
			log_message('debug', '実行時間：'.$processing_time);
		}
		else
		{
			echo('nothing to do. completed.');
			log_message('debug', 'nothing to do. completed.');
		}
	}

	// ライデン定期用
	public function scrapingCurrentLiden($day_batch_no)
	{
		// if ( is_cli() ) 
		{
			log_message('debug', 'scraping current_liden start.');

			// 参考サイトの昨日のデータをスクレイピング
			$liden_data_array = $this->Liden_model->scrapingCurrentLiden($day_batch_no);

			if($liden_data_array == 'no_data')
			{
				log_message('debug', 'NO DATA. scraping current_liden success.');
				echo 'NO DATA. scraping current_liden success.';
				return TRUE;
			}
			// 保存
			if($this->Liden_model->saveLiden($liden_data_array))
			{
				log_message('debug', 'scraping current_liden success.');
				echo 'scraping current_liden success.';
				return TRUE;
			}
			return FALSE;
		}
	}

	// ライデン手動用
	public function scrapingLiden($start_date,$end_date,$day_batch_no=FALSE)
	{
		// if ( is_cli() ) 
		{
			log_message('debug', 'scraping liden start.');

			// 参考サイトの指定日付範囲のデータをスクレイピング
			$liden_data_array = $this->Liden_model->scrapingLiden($start_date,$end_date,$day_batch_no);

			if($liden_data_array == 'no_data')
			{
				log_message('debug', 'NO DATA. scraping current_liden success.');
				echo 'NO DATA. scraping current_liden success.';
				return TRUE;
			}

			if($liden_data_array){
				// 保存
				if($this->Liden_model->saveLiden($liden_data_array))
				{
					log_message('debug', 'scraping liden success.');
					echo 'scraping liden success.';
					return TRUE;
				}
			}

			// バリデーション
			elseif($liden_data_array == "invalid_start_date")
			{
				echo '開始の日付の形式が正しくありません。\n';
			}
			elseif($liden_data_array == "invalid_end_date")
			{
				echo '終了の日付の形式が正しくありません。\n';
			}
			
			elseif($liden_data_array == "too_old")
			{
				echo '開始の日付は2020-10-02以前を選択できません。\n';
			}
			elseif($liden_data_array == "too_new")
			{
				echo '終了の日付は本日以降を選択できません。\n';
			}

			
			log_message('debug', 'scraping liden failed.');
			echo 'scraping liden failed.';
			return FALSE;
		}
	}

	public function scrapingAmedasStations($start_prec, $end_prec)
	{
		// if ( is_cli() ) 
		{
			log_message('debug', 'scraping amedas_stations start.');

			// 気象庁の過去のデータをスクレイピング
			$amedas_stations_data = $this->Amedas_stations_model->scrapingAmedasStations($start_prec, $end_prec);

			// 保存
			if($this->Amedas_stations_model->saveAmedasStations($amedas_stations_data))
			{
				log_message('debug', 'scraping amedas_stations success.');
				echo 'scraping amedas_stations success.';
				exit;
			}
			log_message('debug', 'scraping amedas_stations failed.');
			echo 'scraping amedas_stations failed.';
		}
	}
}
