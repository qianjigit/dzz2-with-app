<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
ignore_user_abort(true);
@set_time_limit(0);
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}

@ini_set("memory_limit","2048M");
require MOD_PATH.'/class/class_output.php';
require MOD_PATH.'/function/function_corpus.php';

define('MAXCRON',10);//同时转换的最大个数；
define('LIBPATH',"ebook-convert");

$endtime=TIMESTAMP-60*5;
if(DB::result_first("select COUNT(*) from %t where status='1' and dateline>%d",array('corpus_convert_cron',$endtime))>MAXCRON){
	exit(lang('exceeds_the_maximum_permissible_conversion_number'));
}

//转换限速；允许
$cronid=intval($_GET['cronid']);
if($uid=intval($_GET['uid'])){
	$user=getuserbyuid($uid);
	$username=$user['username'];
}
if($cron=C::t('corpus_convert_cron')->fetch($cronid)){
	if($cron['status']==1 && $cron['dateline']>(TIMESTAMP-60*5)) exit(lang('already_changing'));
	C::t('corpus_convert_cron')->update($cronid,array('status'=>1));
	if($cron['metadata']) $cron['metadata']=unserialize($cron['metadata']);
	switch($cron['format']){
		case 'itxt':
			//$source=IO::getStream('attach::'.$aid);
			//exit('dddd==='.IO::getFileUri($cron['spath']));
			try{
				if($ret=import_by_txt($cron['spath'],$cron['cid'],$cron['fid'],$cron['disp'],$uid,$username,$cron['metadata']['filename'])){
					
					if(is_array($ret) && $ret['error']){
						C::t('corpus_convert_cron')->update($cronid,array('error'=>$ret['error'],'status'=>-1));
					}else{
						C::t('corpus_convert_cron')->update($cronid,array('status'=>2,'metadata'=>serialize($ret)));
					}
				}else{
					C::t('corpus_convert_cron')->update($cronid,array('error'=>lang('import_failure'),'status'=>-1));
				}
			}catch (Exception $e) {
				C::t('corpus_convert_cron')->update($cronid,array('error'=>$e->getMessage(),'status'=>-1));						
			}
			break;
		case 'iepub': 
			
			try{
				if($ret=import_by_epub($cron['spath'],$cron['cid'],$cron['fid'],$cron['disp'],$uid,$username,$cron['metadata']['filename'])){
					if(is_array($ret) && $ret['error']){
						C::t('corpus_convert_cron')->update($cronid,array('error'=>$ret['error'],'status'=>-1));
					}else{
						C::t('corpus_convert_cron')->update($cronid,array('status'=>2,'metadata'=>serialize($ret)));
					}
				}else{
					C::t('corpus_convert_cron')->update($cronid,array('error'=>lang('import_failure'),'status'=>-1));
				}
			}catch (Exception $e) {
				C::t('corpus_convert_cron')->update($cronid,array('error'=>$e->getMessage(),'status'=>-1));						
			}
			break;
		case 'idocx':
			try{
				if($ret=import_by_docx($cron['spath'],$cron['cid'],$cron['fid'],$cron['disp'],$uid,$username,$cron['metadata']['filename'])){
					if(is_array($ret) && $ret['error']){
						C::t('corpus_convert_cron')->update($cronid,array('error'=>$ret['error'],'status'=>-1));
					}else{
						C::t('corpus_convert_cron')->update($cronid,array('status'=>2,'metadata'=>serialize($ret)));
					}
				}else{
					C::t('corpus_convert_cron')->update($cronid,array('error'=>lang('import_failure'),'status'=>-1));
				}
			}catch (Exception $e) {
				C::t('corpus_convert_cron')->update($cronid,array('error'=>$e->getMessage(),'status'=>-1));						
			}
			break;
		case 'zip_0':
        case 'html':
            try {
                if ($ret = convert::tozip($cron['cid'], $cron['fid'])) {
                    if (is_array($ret) && $ret['error']) {
                        C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => $ret['error'], 'status' => -1));
                    } else {
                        C::t('#corpus#corpus_convert_cron')->update($cronid, array('status' => 2,'tpath'=>$ret));
						
                    }
                } else {
                    C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => lang('export_failure'), 'status' => -1));
                }
            } catch (Exception $e) {
                C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => $e->getMessage(), 'status' => -1));
            }
            break;
        case 'epub_1':
        case 'epub_2':

            try {
            	
                $speat = explode('_',$cron['format']);
                $cmk = $speat[1];
                $start = microtime(true);
                if ($ret = convert::toEpub($cron['cid'], $cron['fid'],$cron['medata'],$cmk)) {
                    if (is_array($ret) && $ret['error']) {
                        C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => $ret['error'], 'status' => -1));
                    } else {
                        C::t('#corpus#corpus_convert_cron')->update($cronid, array('status' => 2));
                    }
                } else {
                    C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => lang('export_failure'), 'status' => -1));
                }
            } catch (Exception $e) {
                C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => $e->getMessage(), 'status' => -1));
            }
            break;
        case 'zip_2'://mobi azw3
            try {
                if ($ret = convert::tozip($cron['cid'], $cron['fid'], 2)) {
                    if (is_array($ret) && $ret['error']) {
                        C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => $ret['error'], 'status' => -1));
                    } else {
                        C::t('#corpus#corpus_convert_cron')->update($cronid, array('status' => 2));
                    }
                } else {
                    C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => lang('export_failure'), 'status' => -1));
                }
            } catch (Exception $e) {
                C::t('#corpus#corpus_convert_cron')->update($cronid, array('error' => $e->getMessage(), 'status' => -1));
            }
            break;
		case 'md':
			try{
				if($ret=convert::toMarkdown($cron['cid'],$cron['fid'])){
					if(is_array($ret) && $ret['error']){
						C::t('corpus_convert_cron')->update($cronid,array('error'=>$ret['error'],'status'=>-1));
					}else{
						C::t('corpus_convert_cron')->update($cronid,array('status'=>2));
					}
				}else{
					C::t('corpus_convert_cron')->update($cronid,array('error'=>lang('export_failure'),'status'=>-1));
				}
			}catch (Exception $e) {
				C::t('corpus_convert_cron')->update($cronid,array('error'=>$e->getMessage(),'status'=>-1));						
			}
			break;
		case 'txt':
			try{
				if($ret=convert::totxt($cron['cid'],$cron['fid'])){
				
				   if(is_array($ret) && $ret['error']){
						C::t('corpus_convert_cron')->update($cronid,array('error'=>$ret['error'],'status'=>-1));
					}else{
						C::t('corpus_convert_cron')->update($cronid,array('status'=>2));
					}
				}else{
					C::t('corpus_convert_cron')->update($cronid,array('error'=>lang('export_failure'),'status'=>-1));
				}
			}catch (Exception $e) {
				C::t('corpus_convert_cron')->update($cronid,array('error'=>$e->getMessage(),'status'=>-1));						
			}
			break;
		default:
			//print_r($cron);
			if($ret=execCommand($cron['spath'],$cron['tpath'],$cron['metadata'],$cron['format'])){
				
				if(is_array($ret) && $ret['error']){
					if(strpos($ret['error'],'Failed without error message')!==false){
						$ret['error']=lang('conversion_failure');
					}
					C::t('corpus_convert_cron')->update($cronid,array('error'=>$ret['error'],'status'=>-1));
				}else{
					if($cron['cid']){
						
						if($cron['format']=='epub' && !$cron['fid']){
							$target='cache/'.md5($cron['tpath']).random(5).'.epub';
							$filepath=$_G['setting']['attachdir'].$target;
							
							if(downfile(($cron['tpath']),$filepath)){
								//解压到临时目录
								$tmpfolder=$_G['setting']['attachdir'].'./cache/'.md5($filepath);
								if(!is_dir($tmpfolder)) dmkdir($tmpfolder,0777,false);
								
								try{
									require_once DZZ_ROOT.'./core/class/pclzip.lib.php';  
									$archive = new PclZip($filepath);  
									if ($archive->extract(PCLZIP_OPT_PATH, $tmpfolder)) {  
										
									
										//插入左侧树
										if($record_zip=C::t('corpus_convert')->fetch_by_format($cron['cid'],$cron['fid'],'zip',1)){
											
											$zippath=$_G['setting']['attachdir'].'cache/'.md5($record_zip['path']).random(5).'.zip';
											if(downfile(($record_zip['path']),$zippath)){
												
												$tmpfolder1=$_G['setting']['attachdir'].'./cache/'.md5($zippath);
												if(!is_dir($tmpfolder1)) dmkdir($tmpfolder1,0777,false);
												$archive1 = new PclZip($zippath);  
												if ($archive1->extract(PCLZIP_OPT_PATH, $tmpfolder1)) {  
													$ncx=create_ncx($tmpfolder1.'/index.html',$tmpfolder.'/toc.ncx',$tmpfolder.'/content.opf');
												}
												unset($archive1);
												//删除临时目录
												removedir($tmpfolder1);
												
												if($ncx){
													//重新打包
													@unlink($zippath);
													require_once libfile('class/zip1');
													$zip1=new Zip();
													$zip1->addDirectoryContent($tmpfolder,'');
													$zip1->finalize();
													$zip1->setZipFile($filepath);
													$zip1->closeZipFile();
													unset($zip1);
													$opath='dzz::'.$target;
													$tpath=$cron['tpath'];
													$pathinfo=pathinfo($tpath);
													if($opath!=$tpath){
														$ret=IO::multiUpload($opath,$pathinfo['dirname'].'/',$pathinfo['basename'],array(),"overwrite");
														@unlink($filepath);
													}
													
												}
											}
										}
									}
									removedir($tmpfolder);
									
								}catch (Exception $e) {
									
								}
							}
							@unlink($filepath);
						}
					}
					//sleep(1);
					
					C::t('corpus_convert_cron')->update($cronid,array('status'=>2));
					C::t('corpus_convert')->insert($cron['cid'],$cron['fid'],$cron['format'],$cron['tpath']);
				}
			}
			break;
	}
}

exit('success');
function execCommand($opath,$tpath,$metadata,$format){
	if(!$input=downfile($opath)){
		return array('error'=>lang('download_is_not_successful'));
	}
	if(strpos($tpath,'dzz::')===0){//目标位置也是本地
		$localtarget=true;
		$output=preg_replace("/^dzz::/i",DZZ_ROOT.'./data/attachment/',$tpath);
	}else{
		$output=DZZ_ROOT.'./data/attachment/cache/'.md5($tpath).'.'.$format;
	}
	$command=new command(LIBPATH);
	$command->addArg($input, null, true);   //增加input
	$command->addArg($output, null, true);   //增加output
	
	 if($metadata){
		 foreach($metadata as $key=>$value){
			 if(strpos($key,'--')!==false){
				    $output_charset='utf-8';
					$lang=strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
					///*if(strrpos($lang, 'zh-cn') !== false) {
						$output_charset='GBK';
						$value=diconv($value,CHARSET,$output_charset);
					//}*/
					
				  $command->addArg($key, $value, true);
			 }else  $command->addArg($value, null, false);
		 }
	 }
	//echo ($command->getExecCommand());exit('dfsdf');
	if (!$command->execute()) {
		if (!(file_exists($output) && filesize($output)!==0)) {
			return array('error'=>$command->getError());
		}
	}
	if($localtarget){//目标位置是本地的话
		@unlink($input);
		return array('msg'=>'success');
	}
	$pathinfo=pathinfo($tpath);
	$opath='dzz::cache/'.md5($tpath).'.'.$format;
	$ret=IO::multiUpload($opath,$pathinfo['dirname'].'/',$pathinfo['basename'],array(),"overwrite");
	@unlink($input);@unlink($output);
	return $ret;
}

function create_ncx($index,$toc,$opf){
		replace_opt($opf);
		$content=file_get_contents($index);
		if(preg_match("/<ul.*?>(.+?)<\/ul><\/body><\/html>/i",$content,$matches)){
			$navmap=$matches[1];
		}else return false;
		unset($content);
		$navmap=preg_replace("/<li class=\"file\" fid=\"(\d+)\"><a href=\"(.+?)\">(.+?)<\/a><\/li>/i",'<navPoint id="$1" playOrder="0"><navLabel><text>$3</text></navLabel><content src="$2"/></navPoint>',$navmap);
		
		$navmap=preg_replace("/<li\s+class=\"folder\"\s+fid=\"(.+?)\"><a href=\"(.+?)\">(.+?)<\/a><ul>/i",'<navPoint id="$1" playOrder="0"><navLabel><text>$3</text></navLabel><content src="$2"/>',$navmap);
		$navmap=preg_replace("/<\/ul><\/li>/i",'</navPoint>',$navmap);
		
		$navmap=preg_replace_callback("/playOrder=\"0\"/i",function(){
			static $i=0;
			$i++;
			return 'playOrder="'.$i.'"';
		},$navmap);
		$search=array();
		$replace=array();
		$contentopf=file_get_contents($opf);
		if(preg_match_all("/href=\"(\d+)_split_000\.(x?html)\"/i",$contentopf,$matches)){
			foreach($matches[1] as $key=>$fid){
				$search[]=$matches[1][$key].'.'.$matches[2][$key];
				$replace[]=$matches[1][$key].'_split_000.'.$matches[2][$key];
			}
		}
		//去除首页多余的首页文件
		// if(preg_match("/<item href=\"index\.html\" id=\"(.+?)\" media-type=\"application\/xhtml\+xml\"\/>/i",$contentopf,$matches)){
		// 	$contentopf=str_replace($matches[0],'',$contentopf);
		// 	$contentopf=preg_replace("/<itemref idref=\"".$matches[1]."\"\/>/i",'',$contentopf);
		// 	file_put_contents($opf,$contentopf);
		// }
		unset($contentopf);
		if($search) $navmap=str_replace($search,$replace,$navmap);
		$navmap='<navMap>'.$navmap.'</navMap>';
		$tcontent=file_get_contents($toc);
		$tcontent=preg_replace("/<navMap>([.\s\S]+?)<\/navMap>/i",$navmap,$tcontent);
		
		return file_put_contents($toc,$tcontent);
		
	}
 function replace_opt($file,$new_meta = 0){
    $file_contents = file_get_contents($file);
   //去除首页多余的首页文件
    if(preg_match("/<item href=\"index\.html\" id=\"(.+?)\" media-type=\"application\/xhtml\+xml\"\/>/i",$file_contents,$matches)){
			$file_contents=str_replace($matches[0],'',$file_contents);
			$file_contents=preg_replace("/<itemref idref=\"".$matches[1]."\"\/>/i",'',$file_contents);
	}
   /*设置图片宽高为原始宽高*/
    /*if(preg_match_all('/<item(.*?)href="(.+?)"(.*?)media-type="image\/(.+?)"\/>/',$file_contents,$match)){
        foreach($match[2] as $k=>$v){
            $href = $v;
            $img = format_url($href,$file);
            $size = getimagesize($img);
            $search[] = '<item'.$match[1][$k].'href="'.$v.'"'.$match[3][$k].'media-type="image/'.$match[4][$k].'"/>';
            $replace[] = '<item height="'.$size[1].'" href="'.$v.'"'.$match[3][$k].' media-type="image/'.$match[4][$k].'" width="'.$size[0].'"/>';
        }
    }*/
   $file_contents = preg_replace_callback('/<itemref(.*?)idref="titlepage"\/>/',function($mat){
       return  '<itemref'.$mat[1].'idref="titlepage" properties="duokan-page-fullscreen"/>';
   },$file_contents);
    $file_contents = str_replace($search, $replace, $file_contents);

    //kindle 整页图实现
    /*if($new_meta){
        $file_contents = preg_replace_callback('/<\/metadata>/',function(){
            return '<meta name="fixed-layout" content="true" /></metadata>';
        },$file_contents);
    }*/
    return file_put_contents($file, $file_contents);
}
 function removedir($dirname, $keepdir = FALSE ) {
		$dirname = str_replace(array( "\n", "\r", '..'), array('', '', ''), $dirname);
		if(!is_dir($dirname)) {
			return FALSE;
		}
		$handle = opendir($dirname);
		while(($file = readdir($handle)) !== FALSE) {
			if($file != '.' && $file != '..') {
				$dir = $dirname . DIRECTORY_SEPARATOR . $file;
				$mtime=filemtime($dir);
				is_dir($dir) ? removedir($dir) :  unlink($dir);
			}
		}
		closedir($handle);
		return !$keepdir ? (@rmdir($dirname) ? TRUE : FALSE) : TRUE;
}
?>
