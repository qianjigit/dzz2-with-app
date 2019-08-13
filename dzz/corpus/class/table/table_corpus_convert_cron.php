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

class table_corpus_convert_cron extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_convert_cron';
		$this->_pk    = 'cronid';

		parent::__construct();
	}
	
}

?>
