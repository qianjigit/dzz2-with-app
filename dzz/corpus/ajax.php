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
if($_GET['do']=='updateview'){
	$cid=intval($_GET['cid']);
	C::t('corpus')->increase($cid,array('viewnum'=>1,'lastviewtime'=>array(TIMESTAMP)));
	if($_G['uid']){
		DB::query("update %t set lastvisit=%d where cid=%d and uid=%d ",array('corpus_user',TIMESTAMP,$cid,$_G['uid']));
	}
 exit('success');
}elseif($_GET['do']=='lock'){
	$fid=intval($_GET['fid']);
	if($lockinfo=C::t('corpus_lock')->islock($fid)){
		exit(json_encode(array('error'=>lang('document_is_locked').'，<a href="user.php?uid='.$lockinfo['uid'].'">'.$lockinfo['username'].'</a> 正在编辑')));
	}else{
		C::t('corpus_lock')->lock($fid);
		exit(json_encode(array('msg'=>'success')));
	}
}elseif($_GET['do']=='unlock'){
	$fid=intval($_GET['fid']);
	if($lockinfo=C::t('corpus_lock')->islock($fid)){
		exit(json_encode(array('error'=>lang('document_is_locked').'，<a href="user.php?uid='.$lockinfo['uid'].'">'.$lockinfo['username'].'</a> 正在编辑')));
	}else{
		C::t('corpus_lock')->unlock($fid);
		exit(json_encode(array('msg'=>'success')));
	}

}elseif($_GET['do']=='setStar'){
	$cid=dintval($_GET['cids'],true);
	$usercache=C::t('corpus_usercache')->fetch($_G['uid']);
	$cids=explode(',',$usercache['paixu_stared']);
	if(in_array($cid,$cids)){
		 foreach ($cids as $k=>$v){
        	if($v == $cid){
        		unset($cids[$k]);
        	}
        }
		$state = 0;
	}else{
		array_push($cids,$cid);
		$state = 1;
	}
	$cids=implode(',',$cids);
	if(C::t('corpus_usercache')->update_by_uid($_G['uid'],array('paixu_stared'=>$cids))){
		exit(json_encode(array('msg'=>'success','state'=>$state)));
	}
	exit();
}elseif($_GET['do']=='addStar'){
	$cid=intval($_GET['cid']);
	$usercache=C::t('corpus_usercache')->fetch($_G['uid']);
	$cids=$usercache['paixu_stared']?explode(',',$usercache['paixu_stared']):array();
	
	if($_GET['action']=='add'){
		$cids[]=$cid;
	}else{
		foreach($cids as $key => $val){
			if($val==$cid) unset($key);
		}
	}
	if($cids) $cids=implode(',',array_unique($cids));
	else $cids='';
	C::t('corpus_usercache')->update_by_uid($_G['uid'],array('paixu_stared'=>$cids));	
	exit('success');
}elseif($_GET['do']=='imageupload'){
	include libfile('class/uploadhandler');
		$options=array( 'accept_file_types' => '/\.(gif|jpe?g|jpg|png)$/i',
						'upload_dir' =>$_G['setting']['attachdir'].'cache/',
						'upload_url' => $_G['setting']['attachurl'].'cache/',
						'thumbnail'=>array('max-width'=>512,'max-height'=>512)
						);
		$upload_handler = new uploadhandler($options);
		exit();
}elseif($_GET['do']=='importupload_book'){
	 include_once libfile('class/uploadhandler');
		$options=array( 'accept_file_types' => '/\.(epub|txt)$/i',
						'upload_dir' =>$_G['setting']['attachdir'].'cache/',
						'upload_url' => $_G['setting']['attachurl'].'cache/',
						//'tospace'=>false
						);
		$upload_handler = new uploadhandler($options);
		exit();
}elseif($_GET['do']=='importupload'){
	include_once libfile('class/uploadhandler');
		$options=array( 'accept_file_types' => '/\.(CHM|MOBI|EPUB|PDF|DOCX|MD|INI|DZZDOC|HTM|HTML|SHTM|SHTML|HTA|HTC|XHTML|STM|SSI|JS|JSON|AS|ASC|ASR|XML|XSL|XSD|DTD|XSLT|RSS|RDF|LBI|DWT|ASP|ASA|ASPX|ASCX|ASMX|CONFIG|CS|CSS|CFM|CFML|CFC|TLD|TXT|PHP|JSP|WML|TPL|LASSO|JSF|VB|VBS|VTM|VTML|INC|SQL|JAVA|EDML|MASTER|INFO|INSTALL|THEME|CONFIG|MODULE|PROFILE|ENGINE)$/i',
						'upload_dir' =>$_G['setting']['attachdir'].'cache/',
						'upload_url' => $_G['setting']['attachurl'].'cache/',
						//'tospace'=>false
						);
		$upload_handler = new uploadhandler($options);
		exit();
}elseif($_GET['do']=='setSave'){

	include_once libfile('function/corpus');
	$orgid=intval($_GET['orgid']);
	$perm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
	if($perm<3){
		exit(lang('have_no_right'));
	}
	$setarr=array();
	$setarr[trim($_GET['name'])]=$_GET['val'];
	switch($_GET['name']){
		case 'name':
			if(empty($_GET['val'])) exit(lang('name_cannot_be_empty'));
			$setarr['name']=getstr($_GET['val'],255);
			break;
		case 'desc':
			$setarr['desc']=getstr($_GET['val']);
			break;
		case 'color':
			if(preg_match("/#\w{6}/i",$_GET['val'])){
				$setarr['color']=$_GET['val'];
			}else{
				$setarr['color']='';
			}
		
			break;
			
		case 'privacy':
			$setarr[trim($_GET['name'])]=intval($_GET['val']);
			break;
		case 'cover':
			$org=C::t('corpus_organization')->fetch($orgid);
			if($org['cover'] && $org['cover']!=intval($_GET['val'])) C::t('attachment')->delete_by_aid($org['cover']);
			if(C::t('corpus_organization')->update($orgid,array('cover'=>intval($_GET['val'])))){
				C::t('attachment')->addcopy_by_aid(intval($_GET['val']));
				exit('success');
			}
			exit('error');
			break;
		case 'logo':
			$org=C::t('corpus_organization')->fetch($orgid);
			if($org['logo'] && $org['logo']!=intval($_GET['val'])) C::t('attachment')->delete_by_aid($org['logo']);
			if(C::t('corpus_organization')->update($orgid,array('logo'=>intval($_GET['val'])))){
				C::t('attachment')->addcopy_by_aid(intval($_GET['val']));
				exit('success');
			}
			exit('error');
			break;
			
	}
	C::t('corpus_organization')->update($orgid,$setarr);
	exit('success');
}elseif($_GET['do']=='org_member_add'){
	$orgid=intval($_GET['orgid']);
	$uid=intval($_GET['uid']);
	//判断权限
	$perm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
	if($perm<3){
		exit(json_encode(array('error'=>lang('have_no_right'))));
	}
	if(!getuserbyuid($uid)){//激活此用户
		exit(json_encode(array('error'=>lang('fail_to_add_retry_after'))));		
	}
	if(C::t('corpus_organization_user')->insert($orgid,$uid,2)){
		exit(json_encode(array('msg'=>'success')));
	}else{
		exit(json_encode(array('error'=>lang('fail_to_add'))));
	}	
}elseif($_GET['do']=='member_invite'){
	$email=trim($_GET['email']);
	$username=trim($_GET['username']);
	$orgid=intval($_GET['orgid']);
	$cid=intval($_GET['cid']);
	$password=random(32);
	loaducenter();
	$uid = uc_user_register(addslashes($username), $password, $email, '', '', '');
	if($uid <= 0) {
		if($uid == -1) {
			exit(json_encode(array('error'=>lang('message','profile_username_illegal'))));
		} elseif($uid == -2) {
			exit(json_encode(array('error'=>lang('message','profile_username_protect'))));
		} elseif($uid == -3) {
			exit(json_encode(array('error'=>lang('message','profile_username_duplicate'))));
		} elseif($uid == -4) {
			exit(json_encode(array('error'=>lang('message','profile_email_illegal'))));
		} elseif($uid == -5) {
			exit(json_encode(array('error'=>lang('message','profile_email_domain_illegal'))));
		} elseif($uid == -6) {
			exit(json_encode(array('error'=>lang('message','profile_email_duplicate'))));
		} elseif($uid == -7) {
			exit(json_encode(array('error'=>lang('message','profile_username_illegal'))));
		} else {
			exit(json_encode(array('error'=>lang('message','undefined_action'))));
		}
	}
	$salt=substr(uniqid(rand()), -6);
	$groupid = $_G['setting']['regverify'] ? 8 : $_G['setting']['newusergroupid'];
	$setarr=array(  'uid'=>$uid,
					'salt'=>$salt,
					'password'=>md5(md5($password).$salt),
					'username'=>$username,
					'secques'=>'',
					'email'=>$email,
					'regdate'=>TIMESTAMP,
					'groupid'=>$groupid
					);
	if(DB::insert('user',$setarr,1)){
		$status = array(
						'uid' => $uid,
						'regip' => '',
						'lastip' => '',
						'lastvisit' => 0,
						'lastactivity' => 0,
						'lastsendmail' => 0
					);
		C::t('user_status')->insert($status, false, true);
		if($cid){
			C::t('corpus_user')->insert_uids_by_cid($cid,array($uid),2);
		}else{
			//添加到组织
			C::t('corpus_organization_user')->insert($orgid,$uid,2);
		}
		//发送邀请邮件
		$idstring = random(6);
		C::t('user')->update($uid, array('authstr' => "$_G[timestamp]\t3\t$idstring"));
		require_once libfile('function/mail');
		$invite_passwd_subject = lang('email', 'invite_passwd_subject');
		$invite_passwd_message = lang(
			'email',
			'invite_passwd_message',
			array(
				'username' => $username,
				'author' => $_G['username'],
				'sitename' => $_G['setting']['sitename'],
				'siteurl' => $_G['siteurl'],
				'uid' => $uid,
				'idstring' => $idstring,
				'clientip' => $_G['clientip'],
			)
		);
		if(!sendmail("$username <$email>", $invite_passwd_subject, $invite_passwd_message)) {
			runlog('sendmail', "$email sendmail failed.");
			exit(json_encode(array('error'=>$email.lang('sending_failed_check_if_the_mailbox_is_correct'))));
		}
		exit(json_encode(array('msg'=>'success')));
	}
}elseif($_GET['do']=='org_member_invite_sendmail'){
		$uid=intval($_GET['uid']);
		$user=getuserbyuid($uid);
		list($dateline, $operation, $idstring) = explode("\t", $user['authstr']);
		if(empty($idstring)) $idstring = random(6);
		C::t('user')->update($uid, array('authstr' => "$_G[timestamp]\t3\t$idstring"));
	    require_once libfile('function/mail');
		$invite_passwd_subject = lang('email', 'invite_passwd_subject');
		$invite_passwd_message = lang(
			'email',
			'invite_passwd_message',
			array(
				'username' => $user['username'],
				'author' => $_G['username'],
				'sitename' => $_G['setting']['sitename'],
				'siteurl' => $_G['siteurl'],
				'uid' =>$uid,
				'idstring' => $idstring,
				'clientip' => $_G['clientip'],
			)
		);
		if(!sendmail("$user[username] <$user[email]>", $invite_passwd_subject, $invite_passwd_message)) {
			runlog('sendmail', "$user[email] sendmail failed.");
			exit(json_encode(array('error'=>$user['email'].lang('sending_failed_check_if_the_mailbox_is_correct'))));
		}
		exit(json_encode(array('msg'=>'success')));

}elseif($_GET['do']=='search_user_list'){
	$orgid=intval($_GET['orgid']);
	$cid=intval($_GET['cid']);
	$term=getstr($_GET['term']);
	//检查email
	if(strlen($term)<2){
		exit(json_encode(array('isemail'=>0,'num'=>0,'term'=>$term)));
	}
	$sqladd = $sqlsearch ='where 1';
	if($isemail=isemail($term)){
		
		$sqladd.=" and email = '{$term}'";
		$sqlsearch .=" and u.email = %s";
		$param = $term;
	}else{
		$sqladd.=" and username like '%".$term."%'";
		$sqlsearch .=" and u.username like %s";
		$param = "%".$term."%";
	}
	$list=array();
	foreach(DB::fetch_all("select u.uid,u.username,u.email,u.avatarstatus,us.lastactivity,ou.uid as joined from %t u 
									LEFT JOIN %t us ON u.uid=us.uid
									LEFT JOIN %t ou ON ou.uid=u.uid and ou.orgid=%d 
									$sqlsearch" ,array('user','user_status','corpus_organization_user',$orgid,$param)) as $value){
		$value['avatar_block']=avatar_block($value['uid'],null,'iconFirstWord');
		$list[]=$value;
	}
	if($isemail){
		list($username,$temp)=explode('@',$term);
		include libfile("function/user",'','user');
		if(preg_match("/_(\d+)$/i",$username,$matches)){
			$pnum=intval($matches[1]);
		}else{
			$pnum=1;
		}
		
		$username=$username.$padding.$pnum;	
	}
	exit(json_encode(array('isemail'=>$isemail,'username'=>$username,'num'=>count($list),'term'=>$term,'list'=>$list)));
}elseif($_GET['do']=='member_add'){
	$cid=intval($_GET['cid']);
	$uid=intval($_GET['uid']);
	$corpus=C::t('corpus')->fetch_by_cid($cid,$_G['uid']);
	//判断权限
	if($corpus['perm']<3){
		exit(json_encode(array('error'=>lang('have_no_right'))));
	}
	if(!getuserbyuid($uid)){//激活此用户
		exit(json_encode(array('error'=>lang('fail_to_add_retry_after'))));
	}
	if(C::t('corpus_user')->insert_uids_by_cid($cid,array($uid),2)){
		exit(json_encode(array('msg'=>'success')));
	}else{
		exit(json_encode(array('error'=>lang('fail_to_add'))));
	}
	
	
}elseif($_GET['do']=='mobile_member_add'){
	$cid=intval($_GET['cid']);
	$ids=$_GET['ids'];
	$uid=explode(',',$ids);
	$corpus=C::t('corpus')->fetch_by_cid($cid,$_G['uid']);
	//判断权限
	if($corpus['perm']<3){
		exit(json_encode(array('error'=>lang('have_no_right'))));
	}
	if(C::t('corpus_user')->insert_uids_by_cid($cid,$uid,2)){
		exit(json_encode(array('success' => 1)));
	}
}elseif($_GET['do']=='getuser'){
	
	$term=trim($_GET['term']);
	$page=empty($_GET['page'])?1:intval($_GET['page']);
	$perpage=30;
	$start=($page-1)*$perpage;
	$not_uids=$_GET['notuids']?explode(',',$_GET['notuids']):array();
	
	$param_user=array('user','user_status');
	$sql_user="where u.status<1 ";
	if($not_uids) {
		$sql_user.=" and u.uid NOT IN(%n)";
		$param_user[]=$not_uids;
	}
	if($term){
	   $sql_user.=" and (u.username LIKE %s OR u.email LIKE %s)";
	   $param_user[]='%'.$term.'%';
	   $param_user[]='%'.$term.'%';
	}
	$data=array();
	
	if($count=DB::result_first("select COUNT(*) from %t u  LEFT JOIN %t s on u.uid=s.uid  $sql_user",$param_user)){
	  foreach(DB::fetch_all("select DISTINCT u.uid,u.username  from %t u LEFT JOIN %t s on u.uid=s.uid  $sql_user order by s.lastactivity DESC limit $start,$perpage",$param_user) as $value){
			
			 $data[]=array('uid'=>$value['uid'],
						   'username'=>$value['username']
						);
			
	  }
	}

  exit(json_encode(array('total_count'=>$count,'items'=>$data)));
}

?>
