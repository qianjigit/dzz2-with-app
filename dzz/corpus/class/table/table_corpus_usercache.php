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

class table_corpus_usercache extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_usercache';
		$this->_pk    = 'uid';

		parent::__construct();
	}
	public function update_by_uid($uid,$arr){
		if(DB::result_first("select COUNT(*) from %t where uid=%d",array($this->_table,$uid))){
			return parent::update($uid,$arr);
		}else{
			$arr['uid']=$uid;
			return parent::insert($arr);
		}
	}
	public function update_usesize_by_uid($uid,$size){
		if($data=parent::fetch($uid)){
			return parent::update($uid,array('usesize'=>$data['usesize']+$size));
		}else{
			$arr['uid']=$uid;
			$arr['usesize']=$size;
			return parent::insert($arr);
		}
	}
}

?>
