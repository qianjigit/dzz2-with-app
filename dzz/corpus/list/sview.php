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

$navtitle='';
$fid=intval($_GET['fid']);
if(!$class=C::t('corpus_class')->fetch($fid)){
	showmessage('文档不存在或已经删除',dreferer());
}
$revid=$class['revid'];

$code=0;
if($document=C::t('corpus_reversion')->fetch($revid)){
	$code=$document['code'];
	$document['dateline']=dgmdate($document['dateline'],'u');
	//获取此文件的所有版本
	$str=($document['remoteid'])?(IO::getFileContent($document['content'])):$document['content'];
	$navtitle=$class['fname'];
	
}else{
	
	$document=$class;
	$document['subject']=$class['fname'];
	$document['dateline']=dgmdate($document['dateline'],'u');
}
include template('list/sview');
?>
