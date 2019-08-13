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
$data=array();
$operation=trim($_GET['operation']);
if($operation && !in_array($operation,array('getParentFid','search','getchildren')) && $corpus['archivetime']>0){
	exit(json_encode(array('error'=>'文集已归档，无法操作！')));
}
$ismobile=helper_browser::ismobile();
if($operation=='rename'){
	$fid=intval($_GET['fid']);
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$fname=trim(str_replace('...','',getstr(strip_tags($_GET['text']),255)));
	if(empty($fname)) exit(json_encode(array('error'=>'名称不能为空')));
	C::t('corpus_class')->rename_by_fid($fid,$fname);
	exit(json_encode(array('msg'=>'success','fname'=>$fname)));
	
}elseif($operation=='move'){
	if(!$cid){
		exit(json_encode(array('error'=>'文件不存在或已删除')));
	}
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$fid=intval($_GET['fid']);
	$pfid=intval($_GET['pfid']);
	$position=intval($_GET['position']);
	C::t('corpus_class')->setDispByFid($fid,$pfid,$position);
	exit('success');
}elseif($operation=='delete'){	
	$fid=intval($_GET['fid']);
	C::t('corpus_class')->delete_by_fid($fid);
	exit('success');
}elseif($operation=='deleteVersion'){	
	$fid=intval($_GET['fid']);
	$revid=intval($_GET['revid']);
	$class=C::t('corpus_class')->fetch($fid);
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}elseif($corpus['perm']==2 && $_G['uid']!=$class['uid']){
		exit(json_encode(array('error'=>'没有权限')));
	}
	if($ver=C::t('corpus_reversion')->delete_by_version($fid,$revid)){
		exit(json_encode(array('msg'=>'success','ver'=>$ver)));
	}else{
		exit(json_encode(array('error'=>'删除失败')));
	}
}elseif($operation=='applyVersion'){	
	$revid=intval($_GET['revid']);
	$fid=intval($_GET['fid']);
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	if($ver=C::t('corpus_reversion')->reversion($fid,$revid,$cid)){
		exit(json_encode(array('msg'=>'success','ver'=>$ver)));
	}
	exit(json_encode(array('error'=>'使用版本失败')));
	

}elseif($operation=='create'){

	if(!$cid){
		exit(json_encode(array('error'=>'文件不存在或已删除')));
	}
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$pfid=intval($_GET['pfid']);
	$type=trim($_GET['type']);
	$code=intval($_GET['code']);
	$disp=intval($_GET['disp']);
	$fname=trim($_GET['fname']);
	$setarr=array(    'fname'=>$fname,
					  'type'=>$type,
					  'cid'=>$cid,
					  'pfid'=>$pfid,
					  'uid'=>getglobal('uid'),
					  'username'=>getglobal('username'),
					  'disp'=>$disp>-1?$disp:(C::t('corpus_class')->fetch_count_by_pfid($pfid)),
					  'dateline'=>TIMESTAMP
					  );
	if($fid=C::t('corpus_class')->insert($setarr,1)){
		$setarr1=array('fid'=>$fid,
				   'uid'=>$_G['uid'],
				   'username'=>$_G['username'],
				   'code'=>$code,
				   'attachs'=>'',
				   'content'=>'',
				);
		if($arr=C::t('corpus_reversion')->insert($setarr1)){
			C::t('corpus_class')->update($fid,array('revid'=>$arr['revid'],'version'=>$arr['version']));
		}
		$data=array(
					'id'=>$fid,
					'text'=>$setarr['fname'],
					'type'=>$type
					);
		exit(json_encode($data));
	}else{
		exit(json_encode(array('error'=>'创建分类失败')));
	}
}elseif($operation=='import'){
	require_once libfile('class/output');
	require_once libfile('function/corpus');
	@ini_set('memory_limit','2048M');
	if(!$cid){
		exit(json_encode(array('error'=>'文件不存在或已删除')));
	}
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$pfid=intval($_GET['pfid']);
	$type=trim($_GET['type']);
	$aid=intval($_GET['aid']);
	$disp=intval($_GET['disp']);
	$filename=urldecode($_GET['filename']);
	$ext=strtolower(substr(strrchr($filename, '.'), 1, 10));
	$needconvert=0;
	$opath='attach::'.$aid;
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
			
		case 'mobi':case 'chm':case 'pdf':case 'azw3':
			$needconvert=2;
			$metadata=array();
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
					dfsockopen(CONVERTURL.MOD_URL.'&op=convert&cronid='.$cronid.'&uid='.$_G['uid'],0, '', '', false, '',1);
					exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
					break;
				case '1':
					dfsockopen(CONVERTURL.MOD_URL.'&op=convert&cronid='.$cronid.'&uid='.$_G['uid'],0, '', '', false, '',1);
					exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
					break;
				case '2': //转换成功
					
					C::t('corpus_convert_cron')->delete($cron['cronid']);
					try{IO::delete($cron['spath']);}catch(Exception $e){}
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
									  'metadata'=>$cron['metadate']
								  );
						if($cronid=C::t('corpus_convert_cron')->insert($setarr,1)){
							dfsockopen(CONVERTURL.MOD_URL.'&op=convert&cronid='.$cronid.'&uid='.$_G['uid'],0, '', '', false, '',1);
							exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
						}
					}
					
					break;
			}
		}
		//插入转换任务
			
			/*if($cronid=DB::result_first("select cronid from %t where spath=%s and format=%s",array('corpus_convert_cron',$opath,$format))){
				C::t('corpus_convert_cron')->update($cronid,array('dateline'=>TIMESTAMP));
				exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
			}else{*/
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
							  'tpath'=>$needconvert>1?$tpath:'',
							  'dateline'=>TIMESTAMP,
							  'metadata'=>'',
							  'format'=>$format,
							  'cid'=>$cid,
							  'fid'=>$pfid,
							  'disp'=>$disp
						  );
				if(strpos($format,'i')===0){
					if($filename) $setarr['metadata']=serialize(array('filename'=>$filename));
				}
				if($cronid=C::t('corpus_convert_cron')->insert($setarr,1)){
					dfsockopen(CONVERTURL.MOD_URL.'&op=convert&cronid='.$cronid.'&uid='.$_G['uid'],0, '', '', false, '',1);
					exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
				}
			//}
		
		
		exit(json_encode(array('error'=>'导入失败')));
	}
	//其他类型文件
	$setarr=array(    'fname'=>$filename,//substr($filename,0,strrpos($filename,'.')),
					  'type'=>$type,
					  'cid'=>$cid,
					  'pfid'=>$pfid,
					  'uid'=>getglobal('uid'),
					  'username'=>getglobal('username'),
					  'disp'=>$disp>-1?$disp:(C::t('corpus_class')->fetch_count_by_pfid($pfid)),
					  'dateline'=>TIMESTAMP
				  );
	if($fid=C::t('corpus_class')->insert($setarr,0)){
		//处理文档文件
		switch($attach['filetype']){
			case 'docx':
				$filepath=$_G['setting']['attachdir'].'./cache/'.random(10).'.docx';
				@file_put_contents($filepath,IO::getFileContent('attach::'.$attach['aid']));
				require_once libfile('class/output');
				$message=convert::docxtohtml($filepath);
				
				break;
			case 'md':
				$message=IO::getFileContent('attach::'.$aid);
				$code=1;
				break;
			default:
				$message=IO::getFileContent('attach::'.$aid);
				 $message=dhtmlspecialchars($message);
				 $message=nl2br(str_replace(array("\t", '   ', '  '), array('&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'), $message));
				 $code=0;
		}
		
		include_once libfile('function/corpus');
		if(setClassContent($message,$fid,$_G['uid'],$_G['username'],$code)){
			$data=array(
							'id'=>$fid,
							'text'=>$setarr['fname'],
							'type'=>$type
							);
				C::t('attachment')->delete_by_aid($aid);
				exit(json_encode($data));
		}else{
			C::t('corpus_class')->delete_by_fid($fid,true);
			C::t('attachment')->delete_by_aid($aid);
			exit(json_encode(array('error'=>'保存文档错误，请检查您数据库是否正常')));
		}
	}else{
		exit(json_encode(array('error'=>'文档导入失败')));
	}
}elseif($operation=='search'){
	if(empty($_GET['str'])) exit(json_encode(array()));
	$str=trim($_GET['str']);
	$lstr='%'.$str.'%';
	$fulltext=new fulltext_core();
	if($searchon=$fulltext->parseQuery($str)){
		$fullsql=" OR MATCH (d.fullcontent) AGAINST ('$searchon' IN BOOLEAN MODE)";
	}else{
		$fullsql='';
	}
	//echo($fullsql);
	//先搜索分类
	$table=C::t('corpus_class')->getTableByCid($cid);
	$farr=DB::fetch_all("select c.fid from %t c  
						  LEFT JOIN %t d ON c.fid=d.fid 
						  where c.cid=%d and (c.fname LIKE %s $fullsql)",array($table,'corpus_fullcontent',$cid,$lstr));
	$data=array();
	//print_r($farr);
	foreach($farr as $value){
		foreach(C::t('corpus_class')->getParentByFid($value['fid']) as $fid){
			$data[]=$fid;
		}
	}
	
	exit(json_encode(array_values(array_unique(array_reverse($data)))));
}elseif($operation=='create_newdir'){
	$flag=intval($_GET['flag']);
	$pfid=intval($_GET['pfid']);
	include template('list/create_newdir');
	exit();
}elseif($operation=='getParentFid'){
	if(in_array($_GET['fid'],array('event','recycle','setting','user','metadata'))){
		$data=array($_GET['fid'],array('manage',$_GET['fid']));
	}else{
	$fid=intval($_GET['fid']);
		if($fids=C::t('corpus_class')->getParentByFid($fid)){
			$data=array($fid=>array_reverse($fids));
		}
	}
	exit(json_encode($data));
}elseif($operation=='getchildren'){
	$data=array();
	if($_GET['id']=='manage'){
		$data[]=array('id'=>'event',
							'text'=>'动态',
							'parent'=>'manage',
							'type'=>'event',
							'children'=>false
						);
			$data[]=array('id'=>'user',
							'text'=>'成员',
							'parent'=>'manage',
							'type'=>'user',
							'children'=>false
						);
			if($corpus['perm']>2){
			$data[]=array('id'=>'recycle',
							'text'=>'回收站',
							'parent'=>'manage',
							'type'=>'recycle',
							'children'=>false
						);
			}
	}else{
		$id=intval($_GET['id']);
		foreach(C::t('corpus_class')->fetch_all_by_pfid($cid,$id) as $value){
			$data[]=array(
			'id'=>$value['fid'],
			'text'=>getstr(strip_tags($value['fname']),255),
			"type"=>$value['type'],
			'children'=>DB::result_first("select COUNT(*) from %t where pfid=%d",array('corpus_class_1',$value['fid']))?true:false
			);

		}

		if($id<1 && $corpus['perm']>0 && !$ismobile){
			$data[]=array('id'=>'manage',
							'text'=>'书本管理',
							'children'=>true,
							'type'=>'manage',
						);
		}
	}	
	exit(json_encode($data));	
}else{
	   $data=array();
	
		foreach(C::t('corpus_class')->fetch_all_by_cid($cid) as $key => $value){
			$temp=array('id'=>$value['fid'],
						'text'=>$value['fname'],
						'parent'=>$value['pfid']?$value['pfid']:'#',
						'type'=>$value['type'],
						);
			//if($key==0) $temp['state']=array('selected'=>true);
			$data[]=$temp;
		}
		if($id<1 && $corpus['perm']>2){
			
			
		}
	
	echo (json_encode($data));
	exit();
}






?>
