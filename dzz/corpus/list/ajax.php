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

$operation=trim($_GET['operation']);

if($operation=='theme'){
	if($corpus['perm']<3){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$theme=intval($_GET['theme']);
	if(C::t('corpus')->update($cid,array('theme'=>$theme))){
		//产生事件
		$event =array('uid'=>getglobal('uid'),
					  'username'=>getglobal('username'),
					  'body_template'=>'corpus_liststyle',
					  'body_data'=>serialize(array('liststyle'=>$theme>0?'无图标样式':'默认样式')),
					  'dateline'=>TIMESTAMP,
					  'bz'=>'corpus_'.$cid,
				 );
		 C::t('corpus_event')->insert($event);
	}
	exit(json_encode(array('msg'=>'success')));
}elseif($operation=='moveto'){
	$orgid=intval($_GET['orgid']);
	if($corpus['perm']<3){//判断此书管理权限
		exit(json_encode(array('error'=>'没有权限')));
	}elseif($corpus['orgperm']<3 && $corpus['orgid'] && ($org=C::t('corpus_organization')->fetch($data['orgid'])) && $org['removeperm']<1){//判断小组管理权限
		exit(json_encode(array('error'=>'当前小组不允许移除此书')));
	}
	$updatearr=array('orgid'=>$orgid);
	//判断移入组织权限
	if($orgid){
		$updatearr=array('orgid'=>$orgid);
		if(!$torg=C::t('corpus_organization')->fetch($orgid)){
			exit(json_encode(array('error'=>'没有找点此小组')));
		}
		$torgperm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid);
		
		if($torgperm<2 && $torg['mperm_c']<1) 	exit(json_encode(array('error'=>'没有此小组的创建书权限')));
		//根据目标组织的创建权限来设置书的权限
		if(!($corpus['viewperm'] & $torg['mperm_c'])){
			if($corpus['mperm_c'] & 1){
				$upadatearr['perm']=0;
			}elseif($corpus['mperm_c'] & 2){
				$upadatearr['perm']=1;
			}elseif($corpus['mperm_c'] & 4){
				$upadatearr['perm']=2;
			}
		}
	}else{
		$updatearr=array('orgid'=>0);
		if($corpus['viewperm']==1){
			$upadatearr['perm']=0;
		}
	}
	
	if(C::t('corpus')->update($cid,$updatearr)){
		exit(json_encode(array('msg'=>'success','orgid'=>$orgid)));
	}
	exit(json_encode(array('error'=>'未成功设置！')));
	
}elseif($operation=='comment'){
	if($corpus['perm']<3){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$comment=intval($_GET['comment']);
	
	if(C::t('corpus')->update($cid,array('forbidcommit'=>$comment))){
		//产生事件
		$event =array('uid'=>getglobal('uid'),
					  'username'=>getglobal('username'),
					  'body_template'=>'corpus_commit_'.($comment>0?0:1),
					  'body_data'=>'',
					  'dateline'=>TIMESTAMP,
					  'bz'=>'corpus_'.$cid,
				 );
		 C::t('corpus_event')->insert($event);
	}
	exit(json_encode(array('msg'=>'success')));
}elseif($operation=='setCorpus'){
	if($corpus['perm']<3){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$setarr=array();
	if(is_array($_GET['name'])){
		foreach($_GET['name'] as $key =>$value){
			$setarr[trim($value)]=intval($_GET['val'][$key]);
			if(trim($value)=='allowdown' || trim($value)=='allowdown_m'){
				
			//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_allowdown',
							  'body_data'=>'',
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						     );	
			}
		}
	}else{
		
		switch($_GET['name']){
			case 'name':
				if(empty($_GET['val'])) exit('名称不能为空');
				$setarr['name']=getstr($_GET['val'],255);
				$updatetime=true;
				//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_name_change',
							  'body_data'=>serialize(array('name'=>$setarr['name'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						 );
				break;
			case 'forbidcommit':
				$setarr[trim($_GET['name'])]=intval($_GET['val']);	
				//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_commit_'.($_GET['val']>0?0:1),
							  'body_data'=>'',
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						    );
				break;
			case 'theme':
				$setarr[trim($_GET['name'])]=intval($_GET['val']);	
				//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_liststyle',
							  'body_data'=>serialize(array('liststyle'=>$_GET['val']>0?'无图标样式':'默认样式')),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						 );
				break;
			case 'perm':
	
				$setarr['perm']=intval($_GET['val']);
				$perm=$setarr['perm']*2;
				if($perm<1) $perm=1;
				 if($corpus['orgperm']<3 && ($orgid=$corpus['orgid'])){
					 $org=C::t('corpus_organization')->fetch($orgid);
					 if(!($org['mperm_c'] & $perm)){
						exit(json_encode(array('error'=>'当前小组不允许设置此权限')));
					}
				 }
				
				//产生事件
				$permtitle=array('0'=>'私有','1'=>'小组内可见','2'=>'公开');
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_perm_change',
							  'body_data'=>serialize(array('permtitle'=>$permtitle[$setarr['perm']])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						    );
				break;
			case 'allowdown':
				$setarr['allowdown']=intval($_GET['val']);
				//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_allowdown',
							  'body_data'=>'',
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						 );
				break;
			case 'color':
				if(preg_match("/#\w{6}/i",$_GET['val'])){
					$setarr['color']=$_GET['val'];
				}else{
					$setarr['color']='';
				}
				//产生事件
				$event =array( 'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							
							  'body_template'=>'corpus_cover_color_change',
							  'body_data'=>'',
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						 );
				break;
			case 'aid':
				if($corpus['aid'] && $corpus['aid']!=intval($_GET['val'])) C::t('attachment')->delete_by_aid($corpus['aid']);
				if(C::t('corpus')->update($cid,array('aid'=>intval($_GET['val'])))){
					C::t('corpus')->update($cid,array('updatetime'=>TIMESTAMP));
					C::t('attachment')->addcopy_by_aid(intval($_GET['val']));
					//产生事件
					$event =array( 'uid'=>getglobal('uid'),
								  'username'=>getglobal('username'),
								
								  'body_template'=>'corpus_cover_change',
								  'body_data'=>'',
								  'dateline'=>TIMESTAMP,
								  'bz'=>'corpus_'.$cid,
							 );
				    C::t('corpus_event')->insert($event);
					exit(json_encode(array('msg'=>'success')));
				}
				break;
			case '--isbn':
				$update_corpus=array();
				$setarr['extra']=$corpus['extra'];
				//验证isbn合法性
				if(preg_match("/[0-9-\s]+/i",$_GET['val'])){
					$setarr['extra']['--isbn']=$_GET['val'];
					$update_corpus=array('isbn'=>$_GET['val']);
				}else{
					$setarr['extra']['--isbn']='';
				}
				
				$update_corpus['updatetime']=TIMESTAMP;
				//产生事件
				$event =array( 'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							
							  'body_template'=>'corpus_cover_isbn_change',
							  'body_data'=>serialize(array('content'=>$setarr['extra']['--isbn'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						 );
				break;
			case '--authors':
				$update_corpus=array();
				$setarr['extra']=$corpus['extra'];
				//验证isbn合法性
				$setarr['extra']['--authors']=getstr($_GET['val']);
				$update_corpus['updatetime']=TIMESTAMP;
				//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_cover_authors_change',
							  'body_data'=>serialize(array('content'=>$setarr['extra']['--authors'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						     );
				break;
			case '--publisher':
				$update_corpus=array();
				$setarr['extra']=$corpus['extra'];
				//验证isbn合法性
				$setarr['extra']['--publisher']=getstr($_GET['val']);
				$update_corpus['updatetime']=TIMESTAMP;
				//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_cover_publisher_change',
							  'body_data'=>serialize(array('content'=>$setarr['extra']['--publisher'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						     );
				break;
			case '--series':
				$update_corpus=array();
				$setarr['extra']=$corpus['extra'];
				$setarr['extra']['--series']=getstr($_GET['val']);
				$update_corpus['updatetime']=TIMESTAMP;
				//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_cover_series_change',
							  'body_data'=>serialize(array('content'=>$setarr['extra']['--series'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						     );
				break;
			case '--comments':
				$update_corpus=array();
				$setarr['extra']=$corpus['extra'];
				$setarr['extra']['--comments']=getstr($_GET['val']);
				$update_corpus['updatetime']=TIMESTAMP;
				//产生事件
				$event =array('uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_cover_comments_change',
							  'body_data'=>serialize(array('content'=>getstr($_GET['val'],80))),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						     );
				break;
			
		}
	}
	if($setarr['extra']) $setarr['extra']=serialize($setarr['extra']);
	if(C::t('corpus')->update($cid,$setarr)){
		if($update_corpus) C::t('corpus')->update($cid,$update_corpus);
		if($event) C::t('corpus_event')->insert($event);
	}
	exit(json_encode(array('msg'=>'success')));
}elseif($operation=='restore'){
	    if($corpus['perm']<3){
			exit(json_encode(array('error'=>'没有权限')));
		}
		if(C::t('corpus')->restore_by_cid($cid)){
			exit(json_encode(array('msg'=>'恢复成功')));
		}else{
			exit(json_encode(array('error'=>'恢复失败')));
		}
}elseif($operation=='archive'){
	 if($corpus['perm']<3){
		exit(json_encode(array('error'=>'没有权限')));
	}
	if(C::t('corpus')->archive_by_cid($cid)){
		exit(json_encode(array('msg'=>'归档成功')));
	}else{
		exit(json_encode(array('error'=>'归档失败')));
	}
}elseif($operation=='delete'){
	 if($corpus['perm']<3){
		exit(json_encode(array('error'=>'没有权限')));
	}
	if(C::t('corpus')->delete_by_cid($cid)){
		exit(json_encode(array('msg'=>'删除成功')));
	}else{
		exit(json_encode(array('error'=>'删除失败')));
	}
}elseif($operation=='ban'){
	include_once dzz_libfile('function/corpus');
	 if(!getAdminPerm()){	
		exit(json_encode(array('error'=>'没有权限')));
	}
	if(C::t('corpus')->update($cid,array('modreasons'=>implode(',',$_GET['modreasons'])))){
		//产生事件
		if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
		$modreasons=$_G['cache']['corpus:setting']['modreasons']?preg_split("/\n/",stripslashes($_G['cache']['corpus:setting']['modreasons'])):array();
		$modtitles=array();
		foreach($_GET['modreasons'] as $key=> $val){
			$modtitles[]=($key+1).'：'.trim($modreasons[$val]).'<br>';
		}
		$modtitle=implode(' ',$modtitles);
			$event =array( 'uid'=>getglobal('uid'),
						  'username'=>getglobal('username'),
						  'body_template'=>'corpus_ban',
						  'body_data'=>serialize(array('reasons'=>$modtitle)),
						  'dateline'=>TIMESTAMP,
						  'bz'=>'corpus_'.$cid,
					 );
			C::t('corpus_event')->insert($event);
		exit(json_encode(array('msg'=>'success')));
	}else{
		exit(json_encode(array('error'=>'封禁失败')));
	}
}elseif($operation=='unban'){
	include_once dzz_libfile('function/corpus');
	 if(!getAdminPerm()){	
		exit(json_encode(array('error'=>'没有权限')));
	}
	if(C::t('corpus')->update($cid,array('modreasons'=>''))){
		//产生事件
		
		$event =array( 'uid'=>getglobal('uid'),
						  'username'=>getglobal('username'),
						  'body_template'=>'corpus_unban',
						  'body_data'=>'',
						  'dateline'=>TIMESTAMP,
						  'bz'=>'corpus_'.$cid,
					 );
			C::t('corpus_event')->insert($event);
		exit(json_encode(array('msg'=>'success')));
	}else{
		exit(json_encode(array('error'=>'解除封禁失败')));
	}
}

?>
