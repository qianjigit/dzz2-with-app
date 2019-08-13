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

class table_corpus_follow extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_follow';
		$this->_pk    = '';

		parent::__construct();
	}
	public function insert($cid,$uid){
		parent::insert(array('cid'=>$cid,'uid'=>$uid?$uid:getglobal('uid')),0,1);
	}
	public function delete($cid,$uid){
		return DB::query("delete from %t where cid=%d and uid=%d ",array($this->_table,$cid,getglobal('uid')));
	}
}

?>
