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

require_once MOD_PATH.'/class/fulltext/fulltext_core.php';
$ismobile=helper_browser::ismobile();
Hook::listen('check_login');//检查是否登录，未登录跳转到登录界面
if(submitcheck('edit')){
	$did=intval($_GET['did']);
	//$subject=empty($_GET['subject'])?'新文档':str_replace('...','',getstr($_GET['subject'],80));
	//桌面上的文档 $area=='' && $areaid=0;
	//项目内文档  $area=='project' && $areaid==$pjid;
	$area='corpus';
	$cid=intval($_GET['cid']);
	$fid=intval($_GET['fid']);
	$new=intval($_GET['newversion']);
	$autosave=intval($_GET['autosave']);
	$code=intval($_GET['code']);
	$class=C::t('corpus_class')->fetch($fid);
	if($lockinfo=C::t('corpus_lock')->isLock($fid)){
		exit(json_encode(array('error'=>'文档被锁定，<a href="user.php?uid='.$lockinfo['uid'].'">'.$lockinfo['username'].'</a> 正在编辑')));
	}
	if($autosave) $new=0;
	//存储文档内容到文本文件内
	if($code<1){
		$message=helper_security::checkhtml($_GET['message']);
	}else{
		$message=($_GET['message']);
	}
	//str_replace(array("\r\n", "\r", "\n"), "",$_GET['message']); //去除换行
	//获取文档内附件
	$attachs=getAidsByMessage($message);
	$setarr=array('fid'=>$fid,
				  'uid'=>$_G['uid'],
				  'username'=>$_G['username'],
				  'code'=>$code,
				  'attachs'=>$attachs,
				  'content'=>$message
			    );
	if(!$arr=C::t('corpus_reversion')->insert($setarr,$new)){
		exit(json_encode(array('error'=>'保存文档错误，请检查您数据库是否正常')));
	}else{
		C::t('corpus_class')->update($fid,array('revid'=>$arr['revid'],'version'=>$arr['version']));
		if($autosave){
			C::t('corpus_lock')->lock($fid);
		}else{
			C::t('corpus_lock')->unlock($fid);
		}
		if($arr['edited']){
			$fulltext=new fulltext_core();
			$fullcontent=$fulltext->normalizeText(strip_tags($message));
			C::t('corpus_fullcontent')->insert(array('fid'=>$fid,'fullcontent'=>$fullcontent),0,1);
		}
		//print_r(array('fid'=>$fid,'fullcontent'=>$fullcontent));exit('dddd==='.strlen($fullcontent));
		
			
			//产生事件
			if(!$autosave && $arr['edited']){//自动保存不产生事件
				$event =array(    'uid'=>getglobal('uid'),
								  'username'=>getglobal('username'),
								  'body_template'=>$new?'corpus_reversion_doc':'corpus_edit_doc',
								  'body_data'=>serialize(array('cid'=>$cid,'fid'=>$fid,'fname'=>$class['fname'])),
								  'dateline'=>TIMESTAMP,
								  'bz'=>'corpus_'.$cid,
							  );
				C::t('corpus_event')->insert($event);
				//通知文档原作者
				if($class['uid']!=getglobal('uid')){
					//发送通知
					$notevars=array(
									'from_id'=>$appid,
									'from_idtype'=>'app',
									'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$class['cid'].'&fid='.$class['fid'],
									'author'=>getglobal('username'),
									'authorid'=>getglobal('uid'),
									'dataline'=>dgmdate(TIMESTAMP),
									'fname'=>getstr($class['fname'],30),
									
									);
					if($new){
						$action='corpus_doc_reversion';
						$type='corpus_doc_reversion_'.$class[$cid];
					}else{
						$action='corpus_doc_edit';
						$type='corpus_doc_edit_'.$class[$cid];
					}
					dzz_notification::notification_add($class['uid'], $type, $action, $notevars, 0,'dzz/corpus');
				}
			
			}
		
		$return=array('id'=>$fid, 'autosave'=>$autosave);
		exit(json_encode($return));
		//showmessage('do_success',dreferer(),array('data'=>rawurlencode(json_encode($return))),array('showmsg'=>true));
	}
}else{
	$navtitle='';
	$str='';
	$fid=intval($_GET['fid']);
	if($lockinfo=C::t('corpus_lock')->isLock($fid)){
		exit('<div class="alert alert-danger"><i class="fa fa-warning"></i> 文档被锁定，因为 <a href="user.php?uid='.$lockinfo['uid'].'">'.$lockinfo['username'].'</a> 正在编辑</div>');
	}
	
	
	$class=C::t('corpus_class')->fetch($fid);
	if($document=C::t('corpus_reversion')->fetch_by_revid($class['revid'])){
		if($document['remoteid']) $document['content']=IO::getFileContent($document['content']);
		$str=$document['content'];
		$code=$document['code'];
	}
	$navtitle=$class['fname'];
	
	C::t('corpus_lock')->lock($fid);
	if($ismobile){
		include template('mobile/edit');
		exit();
	}
	include template('list/newdoc');
}



?>
