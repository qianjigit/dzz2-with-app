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
require libfile('class/output');
require_once libfile('function/corpus');

$cid=intval($_GET['cid']);
if(!checkDownPerm($cid,$_G['uid'])){
	exit(json_encode(array('error'=>lang('no_download_privileges'))));
}
$fid=intval($_GET['fid']);
$format=trim($_GET['format']);
if($_GET['check']){
	$ret=convert::output($cid,$fid,$format);
	exit(json_encode($ret));
}else{
	convert::download($cid,$fid,$format);
}
