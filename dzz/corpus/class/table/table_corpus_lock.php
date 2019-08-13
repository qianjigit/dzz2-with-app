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

class table_corpus_lock extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_lock';
		$this->_pk    = 'fid';

		parent::__construct();
	}
	public function lock($fid){
		
		return DB::insert($this->_table,array('fid'=>$fid,'uid'=>getglobal('uid'),'locktime'=>TIMESTAMP),0,1);
	}
	public function unlock($fid){
		return DB::insert($this->_table,array('fid'=>$fid,'uid'=>getglobal('uid'),'locktime'=>0),0,1);
	}
	public function isLock($fid){
		if($data=parent::fetch($fid)){
			if($data['uid']!=getglobal('uid') && $data['locktime']>0 && (TIMESTAMP-$data['locktime'])<5*60){//最长锁定时间300s
				$user=getuserbyuid($data['uid']);
				$data['username']=$user['username'];
				return $data;
			}
		}
		return false;
	}
}

?>
