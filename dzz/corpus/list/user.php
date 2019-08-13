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

$perm=$corpus['perm'];

$permtitle=array('1'=>'观察员','2'=>'协作成员','3'=>'管理员');
$members=array();
$users=C::t('corpus_organization_user')->fetch_all_by_orgid($orgid);
foreach(DB::fetch_all("select ou.*,u.username,u.email,u.avatarstatus,us.lastactivity from %t ou 
							LEFT JOIN %t u ON u.uid=ou.uid
							LEFT JOIN %t us ON us.uid=ou.uid
							where ou.cid=%d  order by ou.dateline DESC" ,array('corpus_user','user','user_status',$cid)) as $value){
	$list[$value['uid']]=$value;		
}

if($ismobile){
	$adm_userlist = array();
	$cooper_userlist = array();
	$follow_userlist = array();
	foreach($list as $k => $v){
		if($v['perm'] == 3){
			array_push($adm_userlist,$list[$k]);
		}elseif($v['perm'] == 2){
			array_push($cooper_userlist,$list[$k]);
		}elseif($v['perm'] == 1){
			array_push($follow_userlist,$list[$k]);
		}	
	}

	$adm_ids = implode(',', array_column($adm_userlist, 'uid'));
	$cooper_ids = implode(',',array_column($cooper_userlist, 'uid'));
	$follow_ids = implode(',',array_column($follow_userlist, 'uid'));
	include template('mobile/user');
}else{
	include template('list/user');
}



?>
