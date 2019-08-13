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

class table_corpus_event extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_event';
		$this->_pk    = 'eid';

		parent::__construct();
	}
	public function delete_by_bz($bz){ //通过标志删除日志，通常用于彻底删除时，必须删除相关的日志；
		return DB::delete($this->_table,"bz='{$bz}'");
	}
	public function fetch_all_by_bz($bz){
		return DB::fetch_all("select * from %t where bz=%s order by dateline DESC",array($this->_table,$bz));
	}
	public function fetch_all_by_bz_date($bz,$date,$uid=0,$iscount=false){
		$limit=20;
		$uid=intval($uid);
		$dateline=strtotime($date);
		$uidsql='';
		if($uid){
			$uidsql.=" and uid = '{$uid}'";
		}
		$count= DB::result_first("select COUNT(*) from %t where bz=%s and dateline<%d $uidsql ",array($this->_table,$bz,$dateline));
		if($iscount) return $count;
		if($count>$limit){
			$data= DB::fetch_all("select * from %t where bz=%s and dateline<%d $uidsql order by dateline DESC limit %d ",array($this->_table,$bz,$dateline,$limit));
		}else{
			$data= DB::fetch_all("select * from %t where bz=%s and dateline<%d $uidsql order by dateline DESC",array($this->_table,$bz,$dateline));
		}
		
		//按日期分组
		$arr=$lastdate=array();
		foreach($data as $value){
			$value['date']=dgmdate($value['dateline'],'Y-m-d');
			$value['body_data']=unserialize($value['body_data']);
			$value['body_data']['dzzscript']=DZZSCRIPT;
			$value['body']=lang($value['body_template'],$value['body_data']);
			$value['body']=preg_replace("/<span class=\"hideincorpus\">.+?<\/span>/i",'',$value['body']);
			$arr[$value['date']][]=$value;
			$lastdate=$value['date'];
		}
		
		if($count>$limit){
			if(count($arr[$lastdate])>$limit/2){
				$arr[$lastdate]=array();
				$dateline_low=strtotime($lastdate);
				$dateline_up=strtotime('+24 hours',$dateline_low);
				foreach(DB::fetch_all("select * from %t where bz=%s and dateline>%d and dateline<%d $uidsql order by dateline DESC",array($this->_table,$bz,$dateline_low,$dateline_up)) as $value){
					$value['date']=gmdate($value['dateline'],'Y-m-d');
					$value['body_data']=unserialize($value['body_data']);
					$value['body_data']['dzzscript']=DZZSCRIPT;
					
					$value['body']=lang($value['body_template'],$value['body_data']);
					$value['body']=preg_replace("/<span class=\"hideincorpus\">.+?<\/span>/i",'',$value['body']);
					$arr[$lastdate][]=$value;
				}
			}else{
				unset($arr[$lastdate]);
			}
		}
		return $arr;
	}
	public function fetch_allpublish_by_date($bz,$date,$uid,$iscount){
		if(!is_array($bz)) $bz=array('corpus_publish_success','corpus_publish_push','corpus_publish_push_repeat','corpus_publish_status_refuse');
		$limit=20;
		$uid=intval($uid);
		$dateline=strtotime($date);
		$uidsql='';
		if($uid){
			$uidsql.=" and uid = '{$uid}'";
		}
		$count= DB::result_first("select COUNT(*) from %t where body_template IN (%n) and dateline<%d $uidsql ",array($this->_table,$bz,$dateline));
		
		if($iscount) return $count;
		if($count>$limit){
			$data= DB::fetch_all("select * from %t where body_template IN (%n) and dateline<%d $uidsql order by dateline DESC limit %d ",array($this->_table,$bz,$dateline,$limit));
		}else{
			$data= DB::fetch_all("select * from %t where body_template IN (%n) and dateline<%d $uidsql order by dateline DESC",array($this->_table,$bz,$dateline));
		}
		//按日期分组
		$arr=$lastdate=array();
		foreach($data as $value){
			$value['date']=dgmdate($value['dateline'],'Y-m-d');
			$value['body_data']=unserialize($value['body_data']);
			$value['body_data']['dzzscript']=DZZSCRIPT;
			$value['body']=lang($value['body_template'],$value['body_data']);
			$arr[$value['date']][]=$value;
			$lastdate=$value['date'];
		}
		
		if($count>$limit){
			if(count($arr[$lastdate])>$limit/2){
				$arr[$lastdate]=array();
				$dateline_low=strtotime($lastdate);
				$dateline_up=strtotime('+24 hours',$dateline_low);
				foreach(DB::fetch_all("select * from %t where body_template IN (%n) and dateline>%d and dateline<%d $uidsql order by dateline DESC",array($this->_table,$bz,$dateline_low,$dateline_up)) as $value){
					$value['date']=gmdate($value['dateline'],'Y-m-d');
					$value['body_data']=unserialize($value['body_data']);
					$value['body_data']['dzzscript']=DZZSCRIPT;
					$value['body']=lang($value['body_template'],$value['body_data']);
					$arr[$lastdate][]=$value;
				}
			}else{
				unset($arr[$lastdate]);
			}
		}
		return $arr;
	}
}

?>
