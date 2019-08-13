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

$class['fname']=getstr(strip_tags($class['fname']));
$revid=$class['revid'];
$version=intval($_GET['ver']);
$code=0;
if($document=C::t('corpus_reversion')->fetch($revid)){
	
	$code=$document['code'];
	$document['dateline']=dgmdate($document['dateline'],'u');
	//获取此文件的所有版本
	$versions=C::t('corpus_reversion')->fetch_all_by_fid($fid);
//	print_r($versions);
	if($version>0){//版本比较模式，显示当前版本与前一版本的差异
		$current=$versions[$version];
		$str_new=($current['remoteid'])?(IO::getFileContent($current['content'])):$current['content'];
		$str_old=($document['remoteid'])?(IO::getFileContent($document['content'])):$document['content'];
		
		//print_r($current);
		//print_r($document);
		include_once dzz_libfile('class/html_diff','document');
		$diff=new html_diff();
		$str=$diff->compare($str_old,$str_new);
		//exit($str);
	}else{
		$current=$document;
		$str=($document['remoteid'])?(IO::getFileContent($document['content'])):$document['content'];
		$navtitle=$class['fname'];
	}
}else{
	$document=$class;
	$document['subject']=$class['fname'];
	$document['dateline']=dgmdate($document['dateline'],'u');
}
$pn=C::t('corpus_class')->fetch_pn_by_fid($fid,$cid);
if($fid) $lockinfo=C::t('corpus_lock')->isLock($fid);
if($ismobile){
	$class['perm'] = C::t('corpus_user')->fetch_perm_by_uid($_G['uid'],$cid);
	include template('mobile/view');
	dexit();
}
include template('list/view');
?>
