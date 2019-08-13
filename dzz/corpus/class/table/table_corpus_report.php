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

class table_corpus_report extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_report';
		$this->_pk    = 'id';

		parent::__construct();
	}
	public function insert($arr){
		if($ret=parent::insert($arr,1)){
			if($arr['aids']){
				$aids=explode(',',$arr['aids']);
				C::t('attachment')->addcopy_by_aid($aids);
			}
		}
		return $ret;
	}
	public function delete_by_id($id){
		$data=parent::fetch($id);
		if($data['aids']){
			 $aids=explode(',',$data['aids']);
			 C::t('attachment')->addcopy_by_aid($aids);
		}
		return parent::delete($id);
	}
}

?>
