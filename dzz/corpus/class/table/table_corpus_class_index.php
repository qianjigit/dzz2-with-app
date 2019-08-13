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
class table_corpus_class_index extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_class_index';
		$this->_pk    = 'fid';
		$this->_split_sum=5000000;
		parent::__construct();
	}
	
	public function getTableidByFid($fid){
		if($data=parent::fetch($fid)){
			return $data['tableid'];
		}
		return 0;
	}
	
	public function getTableidByCid($cid,$force=false){
		
		if($tableid=DB::result_first("select tableid from %t where cid=%d",array($this->_table,$cid))){
			return $tableid;
		}elseif($force){
			$data=array();
			foreach(DB::fetch_all("select tableid,COUNT(*) as sum from %t group by tableid order by tableid ASC",array($this->_table)) as $data){
				if($data['sum']<$this->_split_sum){//最大
					$tableid=intval($data['tableid']);
					self::checkTable($tableid);
					return $tableid;
				}
			}
			if($data['tableid']){
				$tableid=intval($data['tableid'])+1;
			}else{
				$tableid=1;
			}
			self::checkTable($tableid);
		}else{
			$tableid=0;
		}
		return $tableid;
	}
	
	public function checkTable($tableid){//创建表结构
		global $_G;
		$table=str_replace('index',$tableid,$this->_table);
		$tablepre = $_G['config']['db'][1]['tablepre'];
		//如果表不存在，创建次表
		DB::query("CREATE TABLE IF NOT EXISTS ".$tablepre.$table." ("
  					 ."fid int(10) unsigned NOT NULL,"
					 ."fname varchar(255) NOT NULL DEFAULT '',"
					 ."`type` enum('folder','file') NOT NULL DEFAULT 'folder',"
					 ."revid int(10) NOT NULL DEFAULT '0',"
					 ."version smallint(6) unsigned NOT NULL DEFAULT '0',"
					 ."cid int(10) unsigned NOT NULL DEFAULT '0',"
					 ."pfid int(10) unsigned NOT NULL DEFAULT '0',"
					 ."uid int(10) NOT NULL DEFAULT '0',"
					 ."username char(30) NOT NULL DEFAULT '',"
					 ."disp smallint(6) NOT NULL DEFAULT '0',"
					 ."dateline int(10) NOT NULL DEFAULT '0',"
					 ."deletetime int(10) NOT NULL DEFAULT '0',"
					 ."deleteuid int(10) NOT NULL DEFAULT '0',"
					 ."PRIMARY KEY (fid),"
					 ."KEY cid (cid),"
					 ."KEY disp (disp),"
					 ."KEY pfid (pfid,deletetime)"
					 .") ENGINE=MyISAM  DEFAULT CHARSET=utf8;"
				);
		 return $table;
	}
	
	public function insert($arr){
		if(empty($arr['cid'])) return false;
		if(empty($arr['tableid'])) $arr['tableid']=self::getTableidByCid($arr['cid']);
		return parent::insert($arr,1);
	}
	public function delete_by_cid($cids){
		return DB::delete($this->_table,"cid IN(".dimplode($cid).")");
	}
	
	public function initData($num=1000){//检查数据
		static $tables=array();
		$ret=0;
		$fids=array();
		foreach(DB::fetch_all("select * from %t where 1 order by cid limit %d",array(str_replace('_index','',$this->_table),$num)) as $value){
			$arr=array('fid'=>$value['fid'],'cid'=>$value['cid']);
			if($tables[$value['cid']]) $arr['tableid']=$tables[$value['cid']];
			else $tables[$value['cid']]=$arr['tableid']=self::getTableidByCid($value['cid'],true);
			$table=str_replace('index',$arr['tableid'],$this->_table);
			if(parent::insert($arr)){
				if(DB::insert($table,$value)){
					$fids[]=$value['fid'];
					//DB::delete(str_replace('_index','',$this->_table),"fid = '{$value[fid]}'");
				}
			}
			$ret+=1;
		}
		if($fids) DB::delete(str_replace('_index','',$this->_table),"fid IN (".dimplode($fids).")");
		return $ret;//DB::result_first("select COUNT(*) from %t where 1",array(str_replace('_index','',$this->_table)));
	}
	public function getTableids(){
		return DB::fetch_all("select tableid from %t group by tableid order by tableid ",array($this->_table));
	}
}

?>
