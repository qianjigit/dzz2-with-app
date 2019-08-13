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
if(!$_G['uid']) exit(json_encode(array('error'=>lang('need_login'))));

require libfile('class/output');
require libfile('function/corpus');
$cid=intval($_GET['cid']);
$aid=intval($_GET['aid']);
$orgid=intval($_GET['orgid']);
$filename=urldecode($_GET['filename']);
$pfid=$orgid;
$disp=0;
$ext=strtolower(substr(strrchr($filename, '.'), 1, 10));
//判断创建权限
if($orgid){//判断组织创建权限
	if($org=C::t('corpus_organization')->fetch($orgid)){
		if($org['mperm_c']<1 && ($orgperm=C::t('corpus_organization_user')->fetch_perm_by_uid($_G['uid'],$orgid))<3) return array('error'=>lang('no_permissions_created'));
	}else{
		exit(json_encode(array('error'=>lang('no_permissions_created}'))));
	}
}
$opath='attach::'.$aid;
$needconvert=0;

$metadata=array();
switch($ext){
	case 'epub':
		$format='iepub';
		$needconvert=1;
		break;
	case 'txt':
		$format='itxt';
		$needconvert=1;
		break;
	case 'docx':
		$format='idocx';
		$needconvert=1;
		break;
	case 'mobi':case 'azw3':case 'chm':
		$needconvert=2;
		$metadata=array();
		$format='epub';
		break;
	case 'pdf':
		$needconvert=2;
		$metadata=array('--no-chapters-in-toc');
		$format='epub';
		break;
	default:
		$needconvert=0;
	
		break;
}
if($needconvert){
	$cronid=intval($_GET['cronid']);
	if($cronid && ($cron=C::t('corpus_convert_cron')->fetch($cronid))){
		switch($cron['status']){
			case '-1': //转换失败
				exit(json_encode(array('error'=>$cron['error'])));
				break;
			case '0'://启动转换
				dfsockopen(CONVERTURL.'index.php?mod=corpus&op=convert&cronid='.$cronid.'&uid='.$_G['uid'],0, '', '', false, '',1);
				exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
				break;
			case '1':
				dfsockopen(CONVERTURL.'index.php?mod=corpus&op=convert&cronid='.$cronid.'&uid='.$_G['uid'],0, '', '', false, '',1);
				exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
				break;
			case '2': //转换成功
				
				if(C::t('corpus_convert_cron')->delete($cron['cronid'])){
					try{if($cron['spath']) IO::delete($cron['spath']);}catch(Exception $e){}
				}
				
				if(strpos($cron['format'],'i')===0){
					if($cron['metadata']) $ret=unserialize($cron['metadata']);
					else $ret=array();
					
					exit(json_encode($ret));
				}else{
					$format='iepub';
					$opath=$cron['tpath'];
					$setarr=array('spath'=>$opath,
								  'tpath'=>'',
								  'dateline'=>TIMESTAMP,
								  'metadata'=>'',
								  'format'=>'iepub',
								  'cid'=>$cid,
								  'fid'=>$pfid,
								  'disp'=>$disp,
								  'metadata'=>$cron['metadata']
							  );
					if($cronid=C::t('corpus_convert_cron')->insert($setarr,1)){
						dfsockopen(CONVERTURL.'index.php?mod=corpus&op=convert&cronid='.$cronid.'&uid='.$_G['uid'],0, '', '', false, '',1);
						exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
					}
				}
				
				break;
		}
	}
	//插入转换任务
	
	if($needconvert>1){
		$remoteid=C::t('local_storage')->getRemoteId(); //未指定时根据路由获取；
		$bz=io_remote::getBzByRemoteid($remoteid);
		if($bz=='dzz'){
			$tpath='dzz::cache/'.md5($opath).'.epub';
		}else{
			$tpath=$bz.'/cache/'.md5($opath).'.epub';
		}
	}
	$setarr=array('spath'=>$opath,
				  'tpath'=>$tpath?$tpath:'',
				  'dateline'=>TIMESTAMP,
				  'metadata'=>$metadata?serialize($metadata):'',
				  'format'=>$format,
				  'cid'=>$cid,
				  'fid'=>$pfid,
				  'disp'=>$disp,
			  );
	if(strpos($format,'i')===0){
		if($filename) $setarr['metadata']=serialize(array('filename'=>$filename));
	}
	if($cronid=C::t('corpus_convert_cron')->insert($setarr,1)){
		if($aid) C::t('attachment')->addCopy_by_aid($aid);
		dfsockopen(CONVERTURL.'index.php?mod=corpus&op=convert&cronid='.$cronid.'&uid='.$_G['uid'],0, '', '', false, '',1);
		exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
	}
	
	exit(json_encode(array('error'=>lang('import_failure'))));
}
else{
	exit(json_encode(array('error'=>lang('this_file_format_is_not_supported'))));
}

exit(json_encode(array('error'=>lang('transcription_error'))));
?>
