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
$cid=intval($_GET['cid']);
$corpus=C::t('corpus')->fetch($cid);
$navtitle=$corpus['name'];
$fid=intval($_GET['fid']);
$epub=intval($_GET['epub']);
if(!$class=C::t('corpus_class')->fetch($fid)){
	$navtitle=lang('cover');
	include template('list/scover');
	exit();
}else{
	$navtitle=$class['fname'];
}
$revid=$class['revid'];
$cmk=intval($_GET['cmk']);
$code=0;
if($document=C::t('corpus_reversion')->fetch($revid)){
	$code=$document['code'];
	if($cmk && $code==1){
		$Parsedown=new markdown2html();
		$document['content']=$Parsedown->text($document['content']);
		$code=2;
	}
	$document['dateline']=dgmdate($document['dateline'],'u');
	$str=$document['content'];
	$navtitle=$class['fname'];
	$str=preg_replace(array("/<h1.*?>/i","/<\/h1>/i","/<h2.*?>/i","/<\/h2>/i"),"",$str);
}else{
	
	$document=$class;
	$document['subject']=$class['fname'];
	$document['dateline']=dgmdate($document['dateline'],'u');
}
$fidarr=array();

include template('list/sview');
?>
