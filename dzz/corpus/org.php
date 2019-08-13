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
include libfile('function/corpus');
$orgid=intval($_GET['orgid']);
if(!$org=C::t('corpus_organization')->fetch($orgid)){
	showmessage(lang('group_does_not_exist_or_has_been_deleted'),dreferer());
}
if($org['cover']){
	$org['coverurl']='index.php?mod=io&op=thumbnail&original=1&path='.dzzencode('attach::'.$org['cover']);
}
if($org['logo']){
	$org['logourl']='index.php?mod=io&op=thumbnail&original=1&path='.dzzencode('attach::'.$org['logo']);
}
$navtitle=$org['name'];
$navlast=$org['name'];

//判断用户权限：0：无权限;1:观察员,2:成员权限;3:管理员权限
$perm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
if($_G['adminid']!=1 && $org['privacy'] && $perm<1){
	showmessage(lang('private_group_you_do_not_have_access'),dreferer());
}
if($_G['adminid']==1){
	$newCorpus_perm=1;
}else{
	$newCorpus_perm=C::t('corpus')->checkmaxCorpus($_G['uid']);
	$newCorpus_perm=abs(intval($newCorpus_perm));
}
$do=in_array($_GET['do'],array('members','settings'))?$_GET['do']:'';
if(empty($do)){
//获取组织内文集
	//标星的cids
	$stared_cids=array();
	if($cache=C::t('corpus_usercache')->fetch($_G['uid'])){
		$stared_cids=explode(',',$cache['paixu_stared']);
	}
	
	//获取我参与的文集
	$my=array();
	if($perm>0){
		foreach(DB::fetch_all("select c.*,u.perm as uperm from %t u LEFT JOIN %t c ON u.cid=c.cid where c.orgid=%d and u.uid=%d and c.archivetime<1 and c.deletetime<1 order by c.dateline DESC" ,array('corpus_user','corpus',$orgid,$_G['uid'])) as $value){
			$value['viewperm']=$value['perm'];
			$value['perm']=$value['uperm'];
			$my[$value['cid']]=$value;		
		}
	}
	//获取其他的可见的文集
	if($perm<1){
		$sql=" orgid=%d and  perm>1 and archivetime<1 and deletetime<1";
		$param=array('corpus',$orgid);
	}elseif($my){
		$sql=" orgid=%d and perm>0 and cid NOT IN (%n) and archivetime<1 and deletetime<1";
		$param=array('corpus',$orgid,array_keys($my));
	}else{
		$sql=" orgid=%d and perm>0 and archivetime<1 and deletetime<1";
		$param=array('corpus',$orgid);
	}
	$visible=array();
	foreach(DB::fetch_all("select * from %t where $sql order by dateline DESC" ,$param) as $value){
		$visible[$value['cid']]=$value;			
	}
	$stared_cids=array();
	if($cache=C::t('corpus_usercache')->fetch($_G['uid'])){
		if($cache['paixu_stared']) $stared_cids=explode(',',$cache['paixu_stared']);
	}
	include template('corpus_org');
}elseif($do=='members'){
	$permtitle=array('1'=>lang('observer'),'2'=>lang('members_of_the_collaboration'),'3'=>lang('manager'));
	$members=array();
	//$users=C::t('corpus_organization_user')->fetch_all_by_orgid($orgid);
	foreach(DB::fetch_all("select ou.*,u.username,u.email,u.avatarstatus,us.lastactivity from %t ou 
								LEFT JOIN %t u ON u.uid=ou.uid
								LEFT JOIN %t us ON us.uid=ou.uid
								where ou.orgid=%d  order by ou.dateline DESC" ,array('corpus_organization_user','user','user_status',$orgid)) as $value){
		$list[$value['uid']]=$value;		
	}
	include template('corpus_org_members');
}elseif($do=='settings'){
	
	include template('corpus_org_settings');
}
?>
