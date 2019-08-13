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

class table_corpus_convert extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_convert';
		$this->_pk    = 'id';

		parent::__construct();
	}
	public function insert($cid,$fid,$format,$path){
		if($format=='html') $format='zip';
		if($id=DB::result_first("select id from %t where cid=%d and fid=%d and format=%s",array($this->_table,$cid,$fid,$format))){
			return parent::update($id,array('path'=>$path,'dateline'=>TIMESTAMP));
		}else{
			return parent::insert(array('cid'=>$cid,'fid'=>$fid,'format'=>$format,'path'=>$path,'dateline'=>TIMESTAMP));
		}
	}
	public function fetch_by_format($cid,$fid=0,$format='',$cmk='1'){
		if($format=='html') $format='zip_0';
		elseif($cmk){
			$format = $format.'_'.$cmk;
		}
		return DB::fetch_first("select * from %t where cid=%d and fid=%d and format=%s",array($this->_table,$cid,$fid,$format));
	}
	public function fetch_all_downloads($cid,$fid){
		$data=array();
		$cid=intval($cid);
		$fid=intval($fid);
		foreach(DB::fetch_all("select downloads,format from %t where cid=%d and fid=%d",array($this->_table,$cid,$fid)) as $value){
			if($value['format']=='zip') $value['format']='html';
			if($value['format']=='epub_1') $value['format']='epub';
			$data[$value['format']]=$value['downloads'];
		};
		return $data;
	}
	
	public function delete_by_cid($cid){
		if(!$cid) return false;
		$ids=array();
		foreach(DB::fetch_all("select id,path from %t where cid=%d",array($this->_table,$cid)) as $value){
			$ids[]=$value['id'];
			try{
				if($value['path']) IO::delete($value['path']);
		     }catch(Exception $e){}
		}
		return parent::delete($ids);
	}
	public function delete_by_fid($fids){
		$fids=(array)$fids;
		$ids=array();
		foreach(DB::fetch_all("select id,path from %t where fid IN(%n)",array($this->_table,$fids)) as $value){
			$ids[]=$value['id'];
			try{
				if($value['path']) IO::delete($value['path']);
			}catch(Exception $e){}
		}
		return parent::delete($ids);
	}
}

?>
