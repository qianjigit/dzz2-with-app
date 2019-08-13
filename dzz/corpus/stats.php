<?php
/* @authorcode  codestrings
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}

$navtitle=lang('statistical_statement');
$type=empty($_GET['type'])?'user':trim($_GET['type']);
$starttime=trim($_GET['starttime']);
$endtime=trim($_GET['endtime']);
$time=trim($_GET['time']);
if(empty($time)) $time='day';
$operation=trim($_GET['operation']);

	switch($time){
		case 'month':
			if(!$starttime){
				$start=strtotime("-6 month",TIMESTAMP);
				$starttime=dgmdate($start,'Y-m');
			}
			if(!$endtime){
				$endtime=dgmdate(TIMESTAMP,'Y-m');
			}
			break;
		case 'week':
			if(!$starttime){
				$start=strtotime("-12 week",TIMESTAMP);
			}else{
				$start=strtotime($starttime);
			}
			
			//$darr=getdate($stamp);
			$stamp_l=strtotime("this Monday",$start);
			//$stamp_u=strtotime("+6 day",$stamp_l);
			$starttime=dgmdate($stamp_l,'Y-m-d');
			
			if(!$endtime){
				$end=TIMESTAMP;
			}else{
				$end=strtotime($endtime);
			}
			
			/*$darr=getdate($end);
			$stamp_l=strtotime("this Monday",$end);
			$stamp_u=strtotime("+6 day",$stamp_l);*/
			$endtime=dgmdate($end,'Y-m-d');
			break;
		case 'day':
			if(!$starttime){
				$start=strtotime("-12 day",TIMESTAMP);
				$starttime=dgmdate($start,'Y-m-d');
			}
			if(!$endtime){
				$endtime=dgmdate(TIMESTAMP,'Y-m-d');
			}
			break;
		
	}
	if($operation=='getdata'){
		 $data=getData($time,$starttime,$endtime,$type);
		 include template('stats/stats_ajax');
	}else{
		$data=getData($time,$starttime,$endtime,$type);
		include template('stats/stats');
	}

function getData($time,$starttime,$endtime,$type){
	
	$endtime=strtotime($endtime);
	$data=array('total'=>array(),
				'add'=>array(),
				'total_d'=>array(),
				'add_d'=>array(),
				);
	switch($time){
			case 'month':
				$stamp=strtotime($starttime);
				$arr=getdate($stamp);
				$key=$arr['year'].'-'.$arr['mon'];
				$low=strtotime($key);
				$up=strtotime('+1 month',$low);
				if($type=='book'){
					$ltotal=$data['total'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d",array('corpus',$up));
					$data['add'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d and dateline>=%d ",array('corpus',$up,$low));
					$ltotal+=$data['add'][$key];
					$ltotal_d=$data['total_d'][$key]=0;
					$data['add_d'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d and dateline>=%d",array('corpus_class',$up,$low));
					$ltotal_d+=$data['add_d'][$key];
					
				}else{
					$ltotal=$data['total'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d",array('user',$up));
					$data['add'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d and regdate>=%d",array('user',$up,$low));
					$ltotal+=$data['add'][$key];
				}
				while($up<=$endtime){
					
					$key=dgmdate($up,'Y-m');
					$low=strtotime($key);
					$up=strtotime('+1 month',$low);
					if($type=='book'){
						$data['add'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d and dateline>=%d ",array('corpus',$up,$low));
						$ltotal+=$data['add'][$key];
						$data['total'][$key]=$ltotal;
						$data['add_d'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d and dateline>=%d",array('corpus_class',$up,$low));
						$ltotal_d+=$data['add_d'][$key];
						$data['total_d'][$key]=$ltotal_d;
						
					}else{
						$data['add'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d and regdate>=%d",array('user',$up,$low));
						$ltotal+=$data['add'][$key];
						$data['total'][$key]=$ltotal;
					}
				}
				
				
				break;
			case 'week':
				$stamp=strtotime($starttime);
				$arr=getdate($stamp);
				$low=strtotime('+'.(1-$arr['wday']).' day',$stamp);
				$up=strtotime('+1 week',$low);
				$key=dgmdate($low,'m-d').'~'.dgmdate($up-60*60*24,'m-d');
				if($type=='book'){
					$ltotal=$data['total'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d",array('corpus',$up));
					$data['add'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d and dateline>=%d ",array('corpus',$up,$low));
					$ltotal+=$data['add'][$key];
					$ltotal_d=$data['total_d'][$key]=C::t('corpus_class')->fetch_allcount("dateline<'{$up}'");
					$data['add_d'][$key]=C::t('corpus_class')->fetch_allcount("dateline<'{$up}' and dateline>='{$low}'");
					$ltotal_d+=$data['add_d'][$key];
				}else{
					$ltotal=$data['total'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d",array('user',$up));
					$data['add'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d and regdate>=%d",array('user',$up,$low));
					$ltotal+=$data['add'][$key];
				}
				
				while($up<$endtime){
					$low=$up;
					$up=strtotime('+1 week',$low);
					$key=dgmdate($low,'m-d').'~'.dgmdate($up-60*60*24,'m-d');
									
					if($type=='book'){
						$data['add'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d and dateline>=%d ",array('corpus',$up,$low));
						$ltotal+=$data['add'][$key];
						$data['total'][$key]=$ltotal;
						$data['add_d'][$key]=C::t('corpus_class')->fetch_allcount("dateline<'{$up}' and dateline>='{$low}'");
						$ltotal_d+=$data['add_d'][$key];
						$data['total_d'][$key]=$ltotal_d;
						
					}else{
						$data['add'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d and regdate>=%d",array('user',$up,$low));
						$ltotal+=$data['add'][$key];
						$data['total'][$key]=$ltotal;
					}
				}
				
				break;
			case 'day':
				
				$low=strtotime($starttime);//strtotime('+'.(1-$arr['hours']).' day',$stamp);
				$up=$low+24*60*60;
				$key=dgmdate($low,'Y-m-d');
				
				if($type=='book'){
					$ltotal=$data['total'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d",array('corpus',$up));
					$data['add'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d and dateline>=%d",array('corpus',$up,$low));
					$ltotal+=$data['add'][$key];
					
					$ltotal_d=$data['total_d'][$key]=C::t('corpus_class')->fetch_allcount("dateline<'{$up}'");
					$data['add_d'][$key]=C::t('corpus_class')->fetch_allcount("dateline<'{$up}' and dateline>='{$low}'");
					$ltotal_d+=$data['add_d'][$key];
					
				}else{
					$ltotal=$data['total'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d",array('user',$up));
					$data['add'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d and regdate>=%d",array('user',$up,$low));
					$ltotal+=$data['add'][$key];
				}
				while($up<=$endtime){
					$low=$up;
					$up=strtotime('+1 day',$low);
					$key=dgmdate($low,'Y-m-d');
					if($type=='book'){
						$data['add'][$key]=DB::result_first("select COUNT(*) from %t where dateline<%d and dateline>=%d ",array('corpus',$up,$low));
						$ltotal+=$data['add'][$key];
						$data['total'][$key]=$ltotal;
						$data['add_d'][$key]=C::t('corpus_class')->fetch_allcount("dateline<'{$up}' and dateline>='{$low}'");
						$ltotal_d+=$data['add_d'][$key];
						$data['total_d'][$key]=$ltotal_d;
						
					}else{
						$data['add'][$key]=DB::result_first("select COUNT(*) from %t where regdate<%d and regdate>=%d",array('user',$up,$low));
						$ltotal+=$data['add'][$key];
						$data['total'][$key]=$ltotal;
					}
				}
				break;
			case 'all':
				if($type=='book'){
					$min=DB::result_first("select min(dateline) from %t where dateline>0",array('corpus'));
				}else{
					$min=DB::result_first("select min(regdate) from %t where regdate>0",array('user'));
				}
				$min-=60;
				$max=TIMESTAMP+60*60*8;
				
				$days=($max-$min)/(60*60*24);
				if($days<20){
					$time='day';
					$starttime=gmdate('Y-m-d',$min);
					$endtime=gmdate('Y-m-d',$max);
				}elseif($days<70){
					$time='week';
					$starttime=gmdate('Y-m-d',$min);
					$endtime=gmdate('Y-m-d',$max);
				}else{
					$time='month';
					$starttime=gmdate('Y-m',$min);
					$endtime=gmdate('Y-m',$max);
				}
				$data=getData($time,$starttime,$endtime,$type);
				break;
		}
		
	return $data;
}

?>
