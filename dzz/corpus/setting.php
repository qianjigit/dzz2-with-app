<?php
/* @authorcode  codestrings
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
 //验证管理员登录
require_once './core/function/function_misc.php';
require_once './user/function/function_user.php';
define('IN_ADMIN', TRUE);
define('ADMINSCRIPT', '');
$admincp = new dzz_admincp();
$admincp->core  =  $dzz;
$admincp->init();

if(!defined('IN_DZZ')) {
	exit('Access Denied');
}


include libfile('function/cache');
include libfile('function/organization');
$navs=array('basic'=>lang('base_setting'),
			'report'=>lang('to_report_to_deal_with'),
			'manage'=>lang('the_corpus_management'),
			'default'=>lang('default_setting')
			);
$do=empty($_GET['do'])?'basic':trim($_GET['do']);
$navtitle=$navs[$do] - lang('appname');
$navlast=$navs[$do];
$operation=trim($_GET['operation']);
$muids=array();

//判断用户是否有管理员权限
if($_G['adminid']!=1 ){
	showmessage(lang('have_no_right'),dreferer());
}

if($do=='basic'){
	// $allowdown=array('4'=>'epub','16'=>'html','8'=>'mobi','128'=>'azw3','2'=>'pdf','32'=>'docx','64'=>'txt','256'=>'md');
	$allowdown=array('4'=>'epub','16'=>'html','64'=>'txt','256'=>'md');
	$allowdown_m=array('2'=>lang('manager'),'4'=>lang('collaborative_personnel'),'8'=>lang('observer'),'16'=>lang('team_manager'),'32'=>'小组成员','64'=>'小组观察员','128'=>'其他人(包括游客)');
	if(submitcheck('settingsubmit')){
		$setarr=$_GET['settingnew'];
		$setarr['maxorganization']=intval($setarr['maxorganization']);
		$setarr['neworganization']=intval($setarr['neworganization']);
		$setarr['archiveview']=intval($_GET['archiveview']);
		
		$down=$down_m=1;
		
		foreach($setarr['allowdown'] as  $val){
			$down+=intval($val);
		}
		if(count($allowdown)==count($setarr['allowdown'])){
			$down=0;
		}
		$setarr['allowdown']=$down;
		
		foreach($setarr['allowdown_m'] as $val){
			$down_m+=intval($val);
		}
		if(count($allowdown_m)==count($setarr['allowdown_m'])){
			$down_m=0;
		}
		$setarr['allowdown_m']=$down_m;
		C::t('corpus_setting')->update_batch($setarr);
		updatecache('corpus:setting');
		showmessage('do_success',DZZSCRIPT.'?mod=corpus&op=setting&do=basic');
	}else{
		$setting=C::t('corpus_setting')->fetch_all(array('moderators','neworganization','maxorganization','archiveview','maxcorpus','allowdown','allowdown_m','modreasons'));
		$setting['neworganization']=intval($setting['neworganization']);
		$setting['maxorganization']=intval($setting['maxorganization']);
		$setting['maxcorpus']=intval($setting['maxcorpus']);
		$setting['allowdown']=intval($setting['allowdown']);
		$setting['allowdown_m']=intval($setting['allowdown_m']);
		$setting['archiveview']=intval($setting['archiveview']);
		if($setting['moderators']){
			$muids=explode(',',$setting['moderators']);
		}
		//$moderators=array();
		//$moderators=C::t('user')->fetch_all($muids);
		//处理发布权限
		$orgids=$uids=$sel_org=$sel_user=array();
		foreach($muids as $value){
			if(strpos($value,'uid_')!==false){
				$uids[]=str_replace('uid_','',$value);
			}else{
				$orgids[]=$value;
			}
		} 
		$open=array();
		if($orgids){
			$sel_org=C::t('organization')->fetch_all($orgids);
			foreach($sel_org  as $key=> $value){
				$orgpath=getPathByOrgid($value['orgid']);
				$sel_org[$key]['orgpath']=implode('-',($orgpath));
				$arr=(array_keys($orgpath));
				array_pop($arr);
				$count=count($arr);
				if($open[$arr[$count-1]]){
					if(count($open[$arr[$count-1]])>$count) $open[$arr[count($arr)-1]]=$arr;
				}else{
					$open[$arr[$count-1]]=$arr;
				}
			}
			if(in_array('other',$orgids)){
				$sel_org[]=array('orgname'=>lang('non_agency_personnel'),'orgid'=>'other','forgid'=>1);
			}
		}
		if($uids){
			$sel_user=C::t('user')->fetch_all($uids);
			if($aorgids=C::t('organization_user')->fetch_orgids_by_uid($uids)){
				foreach($aorgids as $orgid){
					$arr= C::t('organization')->fetch_parent_by_orgid($orgid,true);
					$count=count($arr);
					if($open[$arr[$count-1]]){
						if(count($open[$arr[$count-1]])>$count) $open[$arr[count($arr)-1]]=$arr;
					}else{
						$open[$arr[$count-1]]=$arr;
					}
				 }
			}
		} 
		 $openarr=json_encode(array('muids'=>$open));
	}
}elseif($do=='manage'){//文集管理
	if(submitcheck('settingsubmit')){
		@set_time_limit(0);
		foreach($_GET['del'] as $cid){
			C::t('corpus')->delete_permanent_by_cid($cid);
		}
		showmessage(lang('corpus_successfully_delete'),$_GET['refer']);
	}elseif($operation=='archive'){
		$cid=intval($_GET['cid']);
		if(C::t('corpus')->archive_by_cid($cid)){
			showmessage(lang('corpus_archive_success'),$_GET['refer']);
		}else{
			showmessage(lang('corpus_archive_failure'),$_GET['refer']);
		}
	}elseif($operation=='restore'){
		$cid=intval($_GET['cid']);
		if(C::t('corpus')->restore_by_cid($cid)){
			showmessage(lang('corpus_works'),$_GET['refer']);
		}else{
			showmessage(lang('corpus_failed'),$_GET['refer']);
		}	
	}elseif($operation=='delete'){
		$cid=intval($_GET['cid']);
		if(C::t('corpus')->delete_permanent_by_cid($cid)){
			showmessage(lang('corpus_successfully_delete'),$_GET['refer']);
		}else{
			showmessage(lang('corpus_delete_failure'),$_GET['refer']);
		}
	}elseif($operation=='forceindex'){
		$cid=intval($_GET['cid']);
		$data=C::t('corpus')->fetch($cid);
		if($data['perm']<2 && $data['forceindex']<1){
			exit(json_encode(array('error'=>lang('private_collections_cannot_be_set'))));
		}
		if(C::t('corpus')->update($cid,array('forceindex'=>$data['forceindex']?0:1))){
			exit(json_encode(array('msg'=>lang('successfully_set').'！','cid'=>$cid,'forceindex'=>!$data['forceindex'])));
		}else{
			exit(json_encode(array('error'=>lang('failed_set').'！')));
		}
	}else{
		
		$page = empty($_GET['page'])?1:intval($_GET['page']);
		$perpage=10;
		$keyword=trim($_GET['keyword']);
		$archive=intval($_GET['archive']);
		$delete=intval($_GET['delete']);
		$forceindex=intval($_GET['forceindex']);
		$orgid=intval($_GET['orgid']);
		$perm=isset($_GET['perm'])?intval($_GET['perm']):-1;
		$modreason=intval($_GET['modreason']);
		$order=in_array($_GET['order'],array('name','documents','members','viewnum','downloads','dateline','updatetime','orgid'))?$_GET['order']:'dateline';
		$gets = array(
				'mod'=>'corpus',
				'keyword'=>$keyword,
				'op' =>'setting',
				'do'=>'manage',
				'archive'=>$archive,
				'delete'=>$delete,
				'forceindex'=>$forceindex,
				'orgid'=>$orgid,
				'order'=>$order,
				'modreason'=>$modreason,
				'perm'=>$perm
			);
		$theurl = BASESCRIPT."?".url_implode($gets);
		$refer=urlencode($theurl.'&page='.$page);
		$limit=($page-1)*$perpage.'-'.$perpage;
		$temp=$list=array();
		if($count=C::t('corpus')->fetch_all_for_manage($limit,$keyword,$delete,$archive,$perm,$forceindex,$orgid,$modreason,$order,true)){
			$temp=C::t('corpus')->fetch_all_for_manage($limit,$keyword,$delete,$archive,$perm,$forceindex,$orgid,$modreason,$order);
		}
		foreach($temp as $value){
			if($value['deleteuid']){
				 $user=getuserbyuid($value['deleteuid']);
				 $value['deleteusername']=$user['username'];
			}elseif($value['archiveuid']){
				 $user=getuserbyuid($value['archiveuid']);
				 $value['archiveusername']=$user['username'];
			}
			
			$list[]=$value;
		}
		$multi=multi($count, $perpage, $page, $theurl,'pull-right');
	}
}elseif($do=='report'){
		if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
		$modreasons=$_G['cache']['corpus:setting']['modreasons']?explode("\n",stripslashes($_G['cache']['corpus:setting']['modreasons'])):array();
	if(submitcheck('reportsubmit')){
		foreach($_GET['del'] as $id){
			if(!$report=C::t('corpus_report')->fetch($id)){
				continue;
			}
			switch($_GET['action']){
				case 'ignore':
					$arr=array(
								'result'=>1,
							    'result_time'=>TIMESTAMP,
							  );
					C::t('corpus_report')->update($id,$arr);
					break;
				case 'forbid':
					$arr=array(
							   'result'=>2,
							   'result_time'=>TIMESTAMP,
							  );
					if(C::t('corpus_report')->update($id,$arr)){
						$corpus=C::t('corpus')->fetch($report['cid']);
						if(!$corpus['modreasons']){
							if(C::t('corpus')->update($corpus['cid'],array('modreasons'=>$report['reasons']))){
								$modtitles=array();
								foreach(explode(',',$report['reasons']) as $key=> $val){
									$modtitles[]=($key+1).'：'.trim($modreasons[$val]).'<br>';
								}
								//产生事件
								$modtitle=implode(' ',$modtitles);
								$event =array( 'uid'=>getglobal('uid'),
											  'username'=>getglobal('username'),
											  'body_template'=>'corpus_ban',
											  'body_data'=>serialize(array('reasons'=>$modtitle)),
											  'dateline'=>TIMESTAMP,
											  'bz'=>'corpus_'.$corpus['cid'],
										 );
								C::t('corpus_event')->insert($event);
							}
						}
					}
				
					break;
				case 'delete':
					C::t('corpus_report')->delete_by_id($id);
					break;
			}
		}
		showmessage('do_success',dreferer());
	
	
	}elseif($operation=='result'){
		$id=intval($_GET['id']);
		if(!$report=C::t('corpus_report')->fetch($id)){
			exit(json_encode(array('error'=>lang('inexistence'))));
		}
		switch($_GET['action']){
			case 'ignore':
				$arr=array('result'=>1,
						   'result_time'=>TIMESTAMP,
						  );
				if(C::t('corpus_report')->update($id,$arr)){
					exit(json_encode(array('msg'=>'success')));
				}else{
					exit(json_encode(array('error'=>lang('no_change'))));
				}
				break;
			case 'forbid':
				$arr=array(
						   'result'=>2,
						   'result_time'=>TIMESTAMP,
						  );
				if(C::t('corpus_report')->update($id,$arr)){
					$corpus=C::t('corpus')->fetch($report['cid']);
					if(!$corpus['modreasons']){
						if(C::t('corpus')->update($corpus['cid'],array('modreasons'=>$report['reasons']))){
							$modtitles=array();
							foreach(explode(',',$report['reasons']) as $key=> $val){
								$modtitles[]=($key+1).'：'.trim($modreasons[$val]).'<br>';
							}
							//产生事件
							$modtitle=implode(' ',$modtitles);
							$event =array( 'uid'=>getglobal('uid'),
										  'username'=>getglobal('username'),
										  'body_template'=>'corpus_ban',
										  'body_data'=>serialize(array('reasons'=>$modtitle)),
										  'dateline'=>TIMESTAMP,
										  'bz'=>'corpus_'.$corpus['cid'],
									 );
							C::t('corpus_event')->insert($event);
						}
					}
					exit(json_encode(array('msg'=>'success')));
				}else{
					exit(json_encode(array('error'=>lang('no_change'))));
				}
				break;
			
		}
		exit(json_encode(array('error'=>lang('no_change'))));
	}else{
	
		$page = empty($_GET['page'])?1:intval($_GET['page']);
		$perpage=10;
		$keyword=trim($_GET['keyword']);
		$result=intval($_GET['result']);
		$cid=intval($_GET['cid']);
		$reason=is_numeric($_GET['reason'])?intval($_GET['reason']):'';
		$order=in_array($_GET['order'],array('name','documents','members','viewnum','downloads','dateline','rdateline'))?$_GET['order']:'rdateline';
		$gets = array(
				'mod'=>'corpus',
				'keyword'=>$keyword,
				'op' =>'setting',
				'do'=>'report',
				'order'=>$order,
				'result'=>$result,
				'cid'=>$cid,
				'reason'=>$reason
			);
		$theurl = BASESCRIPT."?".url_implode($gets);
		$refer=urlencode($theurl.'&page='.$page);
		$start=($page-1)*$perpage;
		$sql='1';
		$param=array('corpus_report','corpus');
		if($result>0){
			$sql.=" and r.result>0";
		}else{
			$sql.=" and r.result<1";
		}
		if(is_numeric($reason)){
			$param[]=$reason;
			$sql.=" and FIND_IN_SET(%d,reasons)";
		}
		if(!empty($keyword)){
			$param[]='%'.$keyword.'%';
			$param[]=$keyword;
			$sql.=' and (c.name like %s or c.username=%s)';
		}
		if($cid){
			$param[]=$cid;
			$sql.=" and r.cid=%d";
		}
		$list=array();
		if($order=='rdateline'){
			$orderby=" order by r.dateline DESC";
		}else{
			$orderby=" order by c.".$order.' DESC';
		}
		if($count=DB::result_first("select COUNT(*) from %t r LEFT JOIN %t c ON r.cid=c.cid where $sql",$param)){
			foreach(DB::fetch_all("select c.*,r.id,r.uid as ruid ,r.username as rusername,r.aids,r.detail,r.reasons,r.dateline as rdateline,r.result,r.result_time from %t r LEFT JOIN %t c ON r.cid=c.cid where $sql $orderby limit $start,$perpage",$param) as $value){
				if($value['deleteuid']){
					 $user=getuserbyuid($value['deleteuid']);
					 $value['deleteusername']=$user['username'];
				}elseif($value['archiveuid']){
					 $user=getuserbyuid($value['archiveuid']);
					 $value['archiveusername']=$user['username'];
				
				}
				if($value['aids']){
					$value['aids']=explode(',',$value['aids']);
				}else{
					$value['aids']=array();
				}
				if($value['reasons']){
					$value['reasons']=explode(',',$value['reasons']);
				}else{
					$value['reasons']=array();
				}
				$list[]=$value;
			}
		}
		
		$multi=multi($count, $perpage, $page, $theurl,'pull-right');
	}


}elseif($do=='wxapp'){
	$setting=C::t('corpus_setting')->fetch_all();
	$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
	$baseurl_info=DZZSCRIPT.'?mod=corpus&op=setting&do=wxapp';
	$baseurl_menu=DZZSCRIPT.'?mod=corpus&op=setting&do=wxapp&operation=menu';
	$baseurl_ajax=DZZSCRIPT.'?mod=corpus&op=setting&do=wxapp&operation=ajax';
	if(empty($operation)){
		if(submitcheck('settingsubmit')){
			$settingnew=array();
			$settingnew['agentid']=intval($_GET['agentid']);
			$settingnew['appstatus']=intval($_GET['appstatus']);
			$settingnew['secret']=$_GET['secret'];
			if($appid) C::t('wx_app')->update($appid,array('agentid'=>$settingnew['agentid'],'secret'=>$settingnew['secret'],'status'=>$settingnew['appstatus']));
			C::t('corpus_setting')->update_batch($settingnew);
			
			showmessage('do_success',dreferer(),array(),array('alert'=>'right'));
		}else{
			$navtitle=lang('WeChat_application_settings');
			$navlast=lang('WeChat_set');
			$settingnew=array();
			if(empty($setting['token'])) $settingnew['token']=$setting['token']=random(8);
			if(empty($setting['encodingaeskey']))  $settingnew['encodingaeskey']=$setting['encodingaeskey']=random(43);
			if($settingnew){
				C::t('corpus_setting')->update_batch($settingnew);
			}
			$wxapp=array('appid'=>$appid,
						 'name'=>lang('appname'),
						 'desc'=>lang('enterprise_corpus_application'),
						 'icon'=>'dzz/corpus/images/0.jpg',
						 'agentid'=> $setting['agentid'],
						 'secret'=> $setting['secret'],
						 'token'=>$setting['token'],
						 'encodingaeskey'=>$setting['encodingaeskey'],
						 'host'=>$_SERVER['HTTP_HOST'],
						 'callback'=>$_G['siteurl'].'index.php?mod=corpus&op=wxreply',
						 'otherpic'=>'dzz/corpus/images/c.png',
						 'status'=>$setting['appstatus'],	//应用状态
						 'report_msg'=>1,                	//用户消息上报
						 'notify'=>0,                   	 //用户状态变更通知
						 'report_location'=>0,           	//上报用户地理位置
					);
			C::t('wx_app')->insert($wxapp,1,1);
		}
	}elseif($operation=='menu'){
		$menu=$setting['menu']?unserialize($setting['menu']):'';
	}elseif($operation=='ajax'){	
		if($_GET['action']=='setEventkey'){
			//支持的菜单事件
			$menu_select=array('click'=>array(),
								'link'=>array(
										$_G['siteurl'].DZZSCRIPT.'?mod=corpus'=>lang('mine_corpus'),
										$_G['siteurl'].DZZSCRIPT.'?mod=corpus&op=opened'=>lang('public_corpus'),
										$_G['siteurl'].DZZSCRIPT.'?mod=corpus&op=archive'=>lang('archive_corpus')
								)
						);
			 
			
			$json_menu_select=json_encode($menu_select);
			$type=trim($_GET['type']);
			$typetitle=array('click'=>lang('setup_menu_key'),'link'=>lang('set_menu_jump_links'));
			
		}elseif($_GET['action']=='menu_save'){ //菜单保存
				C::t('corpus_setting')->update('menu',array('button'=>$_GET['menu']));
				if($appid) C::t('wx_app')->update($appid,array('menu'=>serialize(array('button'=>$_GET['menu']))));
				exit(json_encode(array('msg'=>'success')));
		}elseif($_GET['action']=='menu_publish'){//发布到微信
				$data=array('button'=>$_GET['menu']);
				  C::t('corpus_setting')->update('menu',$data);
				if($appid) C::t('wx_app')->update($appid,array('menu'=>serialize($data)));
				//发布菜单到微信
				if(getglobal('setting/CorpID') && getglobal('setting/CorpSecret') && $setting['agentid']){
					$appsecret=getglobal('setting/CorpSecret');
					if(isset($setting['secret']) && $setting['secret']){
						$appsecret=$setting['secret'];
					} 
					$wx=new qyWechat(array('appid'=>getglobal('setting/CorpID'),'appsecret'=>$appsecret));
					//处理菜单数据，所有本站链接添加oauth2地址
					foreach($data['button'] as $key=>$value){
						if($value['url'] && strpos($value['url'],$_G['siteurl'])===0){
							$data['button'][$key]['url']=$wx->getOauthRedirect(getglobal('siteurl').'index.php?mod=system&op=wxredirect&url='.dzzencode($value['url']));
						}elseif($value['sub_button']){
							foreach($value['sub_button'] as $key1=>$value1){
								if($value1['url'] && strpos($value1['url'],$_G['siteurl'])===0){
									$data['button'][$key]['sub_button'][$key1]['url']=$wx->getOauthRedirect(getglobal('siteurl').'index.php?mod=system&op=wxredirect&url='.dzzencode($value1['url']));
								}
							}
						}
					}
					if($wx->createMenu($data,$setting['agentid'])){
						exit(json_encode(array('msg'=>'success')));
					}else{
						exit(json_encode(array('error'=>lang('tape_release_failure'),'errCode:'.$wx->errCode.',errMsg:'.$wx->errMsg)));
					}
				}else{
					exit(json_encode(array('error'=>lang('tape_release_failure_agentid'))));
				}
				
		}elseif($_GET['action']=='menu_default'){//恢复默认
			
			$menu_default=array('button'=>array(
												array(
													'type'=>'view',	
													'name'=>lang('mine_corpus'),
													'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=corpus'
												),
												array(
													'type'=>'view',	
													'name'=>lang('public_corpus'),
													'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=corpus&op=opened'
												),
												array(
													'type'=>'view',	
													'name'=>lang('archive_corpus'),
													'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=corpus&op=archive'
												)
										
								)
						  );
			C::t('corpus_setting')->update('menu',$menu_default);
			exit('success');
		}
		include template('common/wx_ajax');
		exit();
	}


}

include template('corpus_setting');
?>
 
