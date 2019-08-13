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
require_once libfile('function/corpus');
$colorarr = require(DZZ_ROOT.'./dzz/corpus/config/config.php');
$colorarr=$colorarr['colorarr'];
$permtitle=array('1'=>lang('observer'),'2'=>lang('members_of_the_collaboration'),'3'=>lang('manager'));
$do=trim($_GET['do']);
$mobile = helper_browser::ismobile();
if($do=='addorg'){
	 $newperm=getCreatePerm($_G['uid'],'org');
	 if(submitcheck('addorgsubmit')){
		 if(!$newperm['new']){
			showmessage('no_privilege',dreferer(),$setarr,array('showmsg'=>true));
		 }
		 $setarr=array('name'=>getstr($_GET['name'],255),
		 			   'desc'=>getstr($_GET['desc']),
					   'uid'=>$_G['uid'],
					   'username'=>$_G['username'],
					   'dateline'=>TIMESTAMP,
					   'privacy'=>$_GET['privacy'],
					   'mperm_c'=>$_GET['mperm_c'],
					   'inviteperm'=>$_GET['inviteperm'],
					   'removeperm'=>$_GET['removeperm']
					   );
					   
		 if($setarr['orgid']=C::t('corpus_organization')->insert($setarr)){
		 	if($mobile){
		 		exit(json_encode(array('code' => 200, 'referer' => DZZSCRIPT.'?mod=corpus')));
		 	}else{
		 		showmessage('do_success',dreferer(),$setarr,array('showmsg'=>true)); 
		 	}
			 
		 }else{
		 	if($mobile){
		 		exit(json_encode(array('code' => 200, 'referer' => DZZSCRIPT.'?mod=corpus')));
		 	}else{
		 		showmessage(lang('fail_to_add'),dreferer(),array(),array('showmsg'=>true));
		 	}
			 
		 }
	 }else{
		 if(!$newperm['new']){
			$errmsg=lang('noperm_create_org_tips',$newperm);
		}
		 if($mobile){
			include template('mobile/cleate_group');
			dexit();	
		}
	 }
	
}elseif($do=='import'){
	$orgid=intval($_GET['orgid']);
}elseif($do=='newcorpus'){

	//检测创建权限
	$perm=0;
	
	 if($orgid=intval($_GET['orgid'])){
		 $perm=1;
		 $org=C::t('corpus_organization')->fetch($orgid);
		 if($org['mperm_c'] & 2){
			$perm=1;
		 }elseif($org['mperm_c'] & 1){
			$perm=0;
		 }elseif($org['mperm_c'] & 4){
			$perm=2;
		 }
	 }
	 $newperm=getCreatePerm($_G['uid']);
	 if(submitcheck('newcorpussubmit')){
		
		 if(!$newperm['new']){
			showmessage('no_privilege',dreferer(),$setarr,array('showmsg'=>true));
		}
		$setarr=array('name'=>str_replace('...','',getstr($_GET['name'],255)),
					  'color'=>preg_match("/^#\w{6}$/i",$_GET['color'])?$_GET['color']:'',
					  'perm'=>$perm,
					  'forbidcommit'=>$_GET['comment'] == 1 ? 0 : 1,
					  'orgid'=>intval($_GET['orgid']),
					  'aid'=>intval($_GET['cover']),
					  'extra'=>is_array($_GET['extra'])?serialize(dhtmlspecialchars($_GET['extra'])):'',
					  'isbn'=>preg_match("/\d{1,5}-{0,1}\d{1,5}-{0,1}\d{1,6}-{0,1}\d{1}/i",$_GET['extra']['--isbn'])?$_GET['extra']['--isbn']:''
					  );
		
			$setarr['dateline']=TIMESTAMP;
			$setarr['uid']=$_G['uid'];
			$setarr['username']=$_G['username'];
			if($setarr['cid']=C::t('corpus')->insert_by_cid($setarr)){
				$setarr1=array(    
						'fname'=>'新分类',
						'type'=>'folder',
						'cid'=>$setarr['cid'],
						'pfid'=>0,
						'uid'=>getglobal('uid'),
						'username'=>getglobal('username'),
						'disp'=>0,
						'dateline'=>TIMESTAMP 
					);
				C::t('corpus_class')->insert($setarr1,1);		
				if($setarr['aid']) $setarr['path']=dzzencode('attach::'.$setarr['aid']);
					if($mobile){
						exit(json_encode(array('code' => 200, 'referer' => DZZSCRIPT.'?mod=corpus')));	
					}else{
						showmessage('do_success',dreferer(),$setarr,array('showmsg'=>true));
					}
			}
	}elseif(submitcheck('editcorpussubmit') && $mobile){
			$cid=intval($_GET['cid']);
			$setarr=array('name'=>str_replace('...','',getstr($_GET['name'],255)),
						  'color'=>preg_match("/^#\w{6}$/i",$_GET['color'])?$_GET['color']:'',
						  'perm'=>$perm,
						  'forbidcommit'=>$_GET['comment'] == 1 ? 0 : 1,
						  'orgid'=>intval($_GET['orgid']),
						  'aid'=>intval($_GET['cover']),
						  'extra'=>is_array($_GET['extra'])?serialize(dhtmlspecialchars($_GET['extra'])):'',
						  'isbn'=>preg_match("/\d{1,5}-{0,1}\d{1,5}-{0,1}\d{1,6}-{0,1}\d{1}/i",$_GET['extra']['--isbn'])?$_GET['extra']['--isbn']:''
						  );
			$setarr['dateline']=TIMESTAMP;
			$setarr['uid']=$_G['uid'];
			$setarr['username']=$_G['username'];
			$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_cover_change_content',
							  'body_data'=>'',
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						     );

				if(C::t('corpus')->update($cid,$setarr)){
					if($event) C::t('corpus_event')->insert($event);
				}
				exit(json_encode(array('code' => 200, 'referer' => DZZSCRIPT.'?mod=corpus')));		
		
	 }else{
		if(!$newperm['new']){
			$errmsg=lang('noperm_create_corpus_tips',$newperm);
		}
	 }
	if($mobile){
		include template('mobile/my_cleate');
		dexit();		
	}
}elseif($do=='settings'){
	$orgid=intval($_GET['orgid']);
	$org=C::t('corpus_organization')->fetch($orgid);
	if($_GET['action']=='basic'){
		 $colors= array('#DB4550','#EB563E','#FAB943','#88C251','#36BC9B','#3BAEDA','#967BDC','#D870AD','#656D78','#434A54');
	}
}elseif($do=='org_member_role'){
	$uid=intval($_GET['uid']);
	$orgid=intval($_GET['orgid']);
	$perm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
	if($perm<3){
		exit('<div class="popbox-body">'.lang('have_no_right').'</div>');
	}
	
	if(!submitcheck('org_u_rolesubmit')){
		$org=C::t('corpus_organization')->fetch($orgid);
		if(!$org['perm']=DB::result_first("select `perm` from %t where orgid=%d and uid=%d",array('corpus_organization_user',$orgid,$uid))){
			exit('<div class="popbox-body">'.lang('this_user_is_not_a_team_member').'</div>');
		}
		if($org['perm']>2){
			$org['adminsum']=DB::result_first("select COUNT(*) from %t where orgid=%d and perm>2",array('corpus_organization_user',$orgid));
		}
	}else{
		$perm=intval($_GET['perm']);
		$ret=C::t('corpus_organization_user')->change_perm_by_uid($orgid,$uid,$perm);
		if($ret===true){
			showmessage('do_success',dreferer(),array('uid'=>$uid,'perm'=>$perm,'permtitle'=>$permtitle[$perm]),array('showmsg'=>true));
		}else{
			showmessage($ret['error'],dreferer(),array(),array('showmsg'=>true));
		}
	}
}elseif($do=='org_member_role_confirm'){
	$uid=intval($_G['uid']);
	$tperm=intval($_GET['perm']);
	$orgid=intval($_GET['orgid']);
	$perm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
	if($perm<3){
		exit('<div class="popbox-body">'.lang('have_no_right').'</div>');
	}
	
	if(!submitcheck('org_role_lost_confirmsubmit')){
		$org=C::t('corpus_organization')->fetch($orgid);
		$org['perm']=$perm;
	}else{
		
		$ret=C::t('corpus_organization_user')->change_perm_by_uid($orgid,$uid,$tperm);
		if($ret===true){
			showmessage('do_success',dreferer(),array('uid'=>$uid,'perm'=>$tperm,'permtitle'=>$permtitle[$tperm]),array('showmsg'=>true));
		}else{
			showmessage($ret['error'],dreferer(),array(),array('showmsg'=>true));
		}
	}
}elseif($do=='org_member_remove'){
	$uid=intval($_GET['uid']);
	$orgid=intval($_GET['orgid']);
	$perm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
	if($_G['uid']!=$uid && $perm<3){
		exit('<div class="popbox-body">'.lang('have_no_right').'</div>');
	}
	
	if(submitcheck('org_member_removesubmit')){
		if($ret=C::t('corpus_organization_user')->remove_uid_by_orgid($orgid,$uid)){
			if($ret['error']){
				showmessage($ret['error'],dreferer(),array(),array('showmsg'=>true));
			}else{
				showmessage('do_success',dreferer(),array('uid'=>$uid),array('showmsg'=>true));
			}
		}
	}
}elseif($do=='org_member_add'){
	$orgid=intval($_GET['orgid']);
	
	$perm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
	if($perm<3){
		exit('<div class="popbox-body">'.lang('have_no_right').'</div>');
	}
}elseif($do=='member_add'){
	$cid=intval($_GET['cid']);
	$corpus=C::t('corpus')->fetch_by_cid($cid,$_G['uid']);
	if($_G['adminid']!=1 && $corpus['perm']<3){
		exit('<div class="popbox-body">'.lang('have_no_right').'</div>');
	}
	$orgmembers=array();
	if($corpus['orgid']>0){
		if(!$paicu_uids=C::t('corpus_user')->fetch_uids_by_cid($cid)){
			$paicu_uids=array();
		}
		$org=C::t('corpus_organization')->fetch($corpus['orgid']);
		foreach(DB::fetch_all("select ou.*,u.username,u.email,u.avatarstatus,us.lastactivity from %t ou 
								LEFT JOIN %t u ON u.uid=ou.uid
								LEFT JOIN %t us ON us.uid=ou.uid
								where ou.orgid=%d and ou.uid NOT IN (%n) order by ou.dateline DESC" ,array('corpus_organization_user','user','user_status',$corpus['orgid'],$paicu_uids)) as $value){
			$orgmembers[$value['uid']]=$value;		
		}
	}
	
}elseif($do=='member_role'){
	$uid=intval($_GET['uid']);
	$cid=intval($_GET['cid']);
	//$perm=C::t('corpus_user')->fetch_perm_by_uid($_G['uid'],$cid);
	if($_G['adminid']!=1 && C::t('corpus_user')->fetch_perm_by_uid($_G['uid'],$cid)<3){
		exit('<div class="popbox-body">'.lang('have_no_right').'</div>');
	}
	
	if(!submitcheck('u_rolesubmit')){
		$perm=C::t('corpus_user')->fetch_perm_by_uid($uid,$cid);
		if($perm>2){
			$adminsum=DB::result_first("select COUNT(*) from %t where cid=%d and perm>2",array('corpus_user',$cid));
		}
	}else{
		$perm=intval($_GET['perm']);
		$ret=C::t('corpus_user')->change_perm_by_uid($cid,$uid,$perm);
		if($ret===true){
			showmessage('do_success',dreferer(),array('uid'=>$uid,'perm'=>$perm,'permtitle'=>$permtitle[$perm]),array('showmsg'=>true));
		}else{
			showmessage($ret['error'],dreferer(),array(),array('showmsg'=>true));
		}
	}
}elseif($do=='member_role_confirm'){
	$uid=intval($_G['uid']);
	$tperm=intval($_GET['perm']);
	$cid=intval($_GET['cid']);
	$perm=C::t('corpus_user')->fetch_perm_by_uid($_G['uid'],$cid);
	if($_G['adminid']!=1 && $perm<3){
		exit('<div class="popbox-body">'.lang('have_no_right').'</div>');
	}
	
	if(!submitcheck('role_lost_confirmsubmit')){
		//$org=C::t('corpus_organization')->fetch($orgid);
		//$org['perm']=$perm;
	}else{
		
		$ret=C::t('corpus_user')->change_perm_by_uid($cid,$uid,$tperm);
		if($ret===true){
			showmessage('do_success',dreferer(),array('uid'=>$uid,'perm'=>$tperm,'permtitle'=>$permtitle[$tperm]),array('showmsg'=>true));
		}else{
			showmessage($ret['error'],dreferer(),array(),array('showmsg'=>true));
		}
	}
}elseif($do=='member_remove'){
	$uid=intval($_GET['uid']);
	$cid=intval($_GET['cid']);
	$perm=C::t('corpus_user')->fetch_perm_by_uid($_G['uid'],$cid);
	if($_G['adminid']!=1 && $_G['uid']!=$uid && $perm<3){
		exit('<div class="popbox-body">.'.lang('have_no_right').'</div>');
	}
	if(submitcheck('member_removesubmit')){
		if($ret=C::t('corpus_user')->remove_uid_by_cid($cid,$uid)){
			if($ret['error']){
				showmessage($ret['error'],dreferer(),array(),array('showmsg'=>true));
			}else{
				showmessage('do_success',dreferer(),array('uid'=>$uid),array('showmsg'=>true));
			}
		}
	}
}elseif($do=='org_delete'){
	$orgid=intval($_GET['orgid']);
	$perm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
	if($_G['adminid']!=1 && $perm<3){
		exit('<div class="popbox-body">'.lang('have_no_right').'</div>');
	}
	if(submitcheck('org_deletesubmit')){
		if($ret=C::t('corpus_organization')->delete_by_orgid($orgid)){
			showmessage('do_success',dreferer(),array('uid'=>$uid),array('showmsg'=>true));
		}else{
			showmessage(lang('delete_unsuccessful'),dreferer(),array(),array('showmsg'=>true));
		}
	}
}elseif($do=='org_share' || $do=='org_shareurl' || $do=='org_sharetowechat'){
		$orgid=intval($_GET['orgid']);
		$org=C::t('corpus_organization')->fetch($orgid);
		$shareurl=$_G['siteurl'].MOD_URL.'&op=org&orgid='.$orgid;
		$t = sprintf("%09d", $orgid);
		$dir1 = substr($t, 0, 3);
		$dir2 = substr($t, 3, 2);
		$dir3 = substr($t, 5, 2);
		$target='qrcode/'.$dir1.'/'.$dir2.'/'.$dir3.'/org_'.$orgid.'.png';
		if(is_file($_G['setting']['attachdir'].$target)) $qrcode=$_G['setting']['attachurl'].$target;
		else{
			$targetpath = dirname($_G['setting']['attachdir'].$target);
			dmkdir($targetpath);
			QRcode::png($shareurl,getglobal('setting/attachdir').$target,'M',4,2);
			if(is_file($_G['setting']['attachdir'].$target)) $qrcode=$_G['setting']['attachurl'].$target;
		}
}elseif($do=='topic'){
	$allowdown=array('4'=>'epub','16'=>'html','64'=>'txt','256'=>'md');
	$allowdown_m=array('2'=>lang('manager'),'4'=>lang('collaborative_personnel'),'8'=>lang('observer'),'16'=>lang('team_manager'),'32'=>'小组成员','64'=>'小组观察员','128'=>'其他人(包括游客)');
	$operation=trim($_GET['operation']);
	$cid=intval($_GET['cid']);
	$fid=intval($_GET['fid']);
	$num=intval($_GET['num']);
	$corpus=C::t('corpus')->fetch_by_cid($cid,$_G['uid']);
	
	if($fid){
		$class=C::t('corpus_class')->fetch($fid);
		$name=$class['fname'];
	}else{
		$name=$corpus['name'];
	}
	if($mobile){
		if($fid){
			$shareurl=$_G['siteurl'].MOD_URL.'&op=list&do=view&cid='.$cid.'&fid='.$fid;
		}else{
			$shareurl=$_G['siteurl'].MOD_URL.'&op=list&cid='.$cid;
		}

	}else{
		$shareurl=$_G['siteurl'].MOD_URL.'&op=list&cid='.$cid.($fid?'&fid='.$fid:'');
	}
	if($operation=='shareurl' || $operation=='sharetowechat'){
		$t = sprintf("%09d", $cid);
		$dir1 = substr($t, 0, 3);
		$dir2 = substr($t, 3, 2);
		$dir3 = substr($t, 5, 2);
		$target='qrcode/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.$cid.($fid?'_fid'.$fid:'').'.png';
		if(is_file($_G['setting']['attachdir'].$target)) $qrcode=$_G['setting']['attachurl'].$target;
		else{
			$targetpath = dirname($_G['setting']['attachdir'].$target);
			dmkdir($targetpath);
			QRcode::png($shareurl,getglobal('setting/attachdir').$target,'M',4,2);
			if(is_file($_G['setting']['attachdir'].$target)) $qrcode=$_G['setting']['attachurl'].$target;
		}
		if($mobile){
			exit(json_encode(array('code' => 200, 'qrcode' => $qrcode, 'url' => $shareurl)));		
		}
		
	}elseif($operation=='reversion'){
		$versions=C::t('corpus_reversion')->fetch_all_by_fid($fid);
	
	}elseif($operation=='perm'){
		if($corpus['orgid']){
			$org=C::t('corpus_organization')->fetch($corpus['orgid']);
		}
	}elseif($operation=='moveto'){
		$myorgs=array();
		foreach(DB::fetch_all("select o.orgid,o.name,o.logo from %t u LEFT JOIN %t o ON u.orgid=o.orgid where u.uid=%d and (u.perm>2 || (u.perm>1 and o.mperm_c>0)) and o.orgid>0  order by o.dateline" ,array('corpus_organization_user','corpus_organization',$_G['uid'])) as $value){
			if($corpus['orgid']==$value['orgid']){
				$current=$value;
			}else{
				$myorgs[$value['orgid']]=$value;	
			}
		}
		if($corpus['orgid']) $org=C::t('corpus_organization')->fetch($corpus['orgid']);
		
	}elseif($operation=='allowdown'){
		if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
		if(!$corpus['orgid']){
			unset($allowdown_m['16']);
			unset($allowdown_m['32']);
			unset($allowdown_m['64']);
		}
		if($top_allowdown_m=$_G['cache']['corpus:setting']['allowdown_m']){
			foreach($allowdown_m as $key =>$val){
				if(!(intval($key) & $top_allowdown_m)){
					unset($allowdown_m[$key]);
				}
			}
		}
		if($top_allowdown=$_G['cache']['corpus:setting']['allowdown']){
			foreach($allowdown as $key =>$val){
				if(!(intval($key) & $top_allowdown)){
					unset($allowdown[$key]);
				}
			}
		}
	}elseif($operation=='download'){
		if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
		$download=C::t('corpus_convert')->fetch_all_downloads($cid,$fid);
		if($fid){
			unset($allowdown['4']);
			unset($allowdown['8']);
			unset($allowdown['128']);
		}else{
			unset($allowdown['256']);
		}
		if($top_allowdown=$_G['cache']['corpus:setting']['allowdown']){
			foreach($allowdown as $key =>$val){
				if(!(intval($key) & $top_allowdown)){
					unset($allowdown[$key]);
				}
			}
		}
	
	}elseif($operation=='ban'){
		if(!getAdminPerm()){	
			exit(lang('no_permission_to_ban_operation'));
		}
		
			if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
			$modreasons=$_G['cache']['corpus:setting']['modreasons']?preg_split("/\n/",stripslashes($_G['cache']['corpus:setting']['modreasons'])):array();
			if($corpus['modreasons']) $corpus['modreasons']=explode(',',$corpus['modreasons']);
			
		
	}elseif($operation=='setting'){
		$from=trim($_GET['from']);
	}elseif($operation=='edit'){
		
		 if(submitcheck('editsubmit')){
			$setarr=array(
						  'name'=>str_replace('...','',getstr($_GET['name'],255)),
						  'color'=>preg_match("/^#\w{6}$/i",$_GET['color'])?$_GET['color']:'',
						  'aid'=>intval($_GET['cover'])
						 );
				if(C::t('corpus')->update_by_cid($cid,$setarr)){
					showmessage('do_success',dreferer(),$setarr,array('showmsg'=>true));
				}
			
		 }
	}elseif($operation=='evernote'){
		$notes=DB::fetch_all("select * from %t where uid=%d and expires>%d",array('connect_evernote',$_G['uid'],TIMESTAMP));
	}elseif($operation=='evernote_notes'){
		$id=intval($_GET['id']);
	}
	if($mobile){
		include template('mobile/my_cleate');
		dexit();		
	}
}
include template('pop_menu');
dexit();
?>
