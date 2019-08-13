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
$ismobile=helper_browser::ismobile();
require_once libfile('function/corpus');
$cid=intval($_GET['cid']);
if(!$corpus=C::t('corpus')->fetch_by_cid($cid,$_G['uid']) ){
	showmessage(lang('file_does_not_exist_or_has_been_deleted'),dreferer());
}
if($corpus['css']){
	$corpus['css']=IO::getFileUri('attach::'.$corpus['css']);
}
if($corpus['deletetime']>0 && $corpus['perm']<3){
	showmessage(lang('file_does_not_exist_or_has_been_deleted'),dreferer());
}
//封禁处理
if($corpus['modreasons']) $corpus['viewperm']=0;
if($corpus['perm']<1 && $corpus['viewperm']<1){ //私有的文件只有成员才能查看
	showmessage(lang('this_article_is_private_not_member_cannot_see'),dreferer());
}
if($corpus['orgid']>0 && $corpus['viewperm']==1 && $corpus['perm']<1 && !C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$corpus['orgid'])){ //组织内可见组织成员和书成员才能查看
	showmessage(lang('this_corpus_is_visible_in_the_group'),dreferer());
}
$archive=C::t('corpus_setting')->fetch('archiveview');
if($archive>1 && $corpus['archivetime']>0 && $corpus['perm']<3){
	showmessage(lang('this_corpus_is_archived_administrator'),dreferer());
}
if($archive==1 && $corpus['archivetime']>0 && $corpus['perm']<1){
	showmessage(lang('this_corpus_is_archived_member'),dreferer());
}
if($corpus['modreasons']){
	if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
	$modreasons=$_G['cache']['corpus:setting']['modreasons']?preg_split("/\n/",stripslashes($_G['cache']['corpus:setting']['modreasons'])):array();
	$corpus['modreasons']=explode(',',$corpus['modreasons']);
	$modtitles=array();
	foreach($corpus['modreasons'] as $key=> $val){
		$modtitles[]=($key+1).'：'.trim($modreasons[$val]).'<br>';
	}
	$modtitle=implode(' ',$modtitles);
}
$downperm=checkDownPerm($cid,$_G['uid']);
$navtitle=$corpus['name'];
if($fid=intval($_GET['fid'])){
	if($class=C::t('corpus_class')->fetch($fid)){
		$navtitle=$class['fname'].' - '.$navtitle;
	}
}
$do=empty($_GET['do'])?'index':trim($_GET['do']);
$ismobile=helper_browser::ismobile();
if($do=='newdoc'){
	require( DZZ_ROOT.'./dzz/corpus/list/newdoc.php');
	exit();
}elseif($do=='event'){
	require(DZZ_ROOT.'./dzz/corpus/list/event.php');
	exit();
}elseif($do=='recycle'){
	require(DZZ_ROOT.'./dzz/corpus/list/recycle.php');
	exit();
}elseif($do=='ctree'){
	require(DZZ_ROOT.'./dzz/corpus/list/ctree.php');
	exit();	
}elseif($do=='edit'){
	require(DZZ_ROOT.'./dzz/corpus/list/edit.php');
	exit();
}elseif($do=='view'){
	require(DZZ_ROOT.'./dzz/corpus/list/view.php');
	exit();	
}elseif($do=='cover'){
	require(DZZ_ROOT.'./dzz/corpus/list/cover.php');
	exit();
}elseif($do=='user'){
	require(DZZ_ROOT.'./dzz/corpus/list/user.php');
	exit();	
}elseif($do=='metadata'){
	require(DZZ_ROOT.'./dzz/corpus/list/metadata.php');
	exit();
}elseif($do=='ajax'){
	require(DZZ_ROOT.'./dzz/corpus/list/ajax.php');
	exit();

}elseif($do=='index'){
	if($ismobile){
		$cid = intval($_GET['cid']);
		$fid = intval($_GET['fid']);
		if($cache = C::t('corpus_usercache')->fetch($_G['uid'])){
			$paixu_stared = explode(',',$cache['paixu_stared']);
		}
		include template('mobile/ctree');
		exit();	
	}
	if(checkrobot() || $_GET['robot']){
		if($fid){
			require DZZ_ROOT.'dzz/corpus/view.php&cmk=1';
		}else{
			require DZZ_ROOT.'dzz/corpus/robot/list.php';
		}
		exit();
	}
	$fid=intval($_GET['fid']);//加入此参数可以直接定位到此分类
	include template('corpus_list');
}
dexit();
?>
