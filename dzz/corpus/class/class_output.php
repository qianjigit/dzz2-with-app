<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */

define('CONVERTURL',$_G['siteurl']);
include_once(DZZ_ROOT.'./'.MOD_PATH . '/class/epub/EPub.inc.php');
include_once(DZZ_ROOT.'./'.MOD_PATH . '/class/epub/EPubChapterSplitter.inc.php');
include_once(DZZ_ROOT.'./'.MOD_PATH . '/class/epub/createCover.php');
include_once(DZZ_ROOT.'./'.MOD_PATH . '/class/class_html2markdown.php');
include_once(DZZ_ROOT.'./'.MOD_PATH . '/class/class_markdown2html.php');
class convert{
	public function output($cid,$fid,$format){
		global $_G;
		@set_time_limit(3600);
		$meta=array();
		$metadata=array(

						'--no-chapters-in-toc',
						'--toc-threshold'=>0,
						'--margin-bottom'=>15,
						'--margin-left'=>15,
						'--margin-top'=>15,
						'--margin-right'=>15,
						'--minimum-line-height'=>200
						);
		if($fid){
			$table=C::t('corpus_class')->getTableByFid($fid);
			$class=DB::fetch_first("select r.dateline,c.fname from %t c LEFT JOIN %t r ON c.revid=r.revid where c.fid=%d",array($table,'corpus_reversion',$fid));
			$updatetime=$class['dateline'];
			//$metadata['--title']=getstr($class['fname'],21);
		}else{
			$corpus=C::t('corpus')->fetch($cid);
			$corpus['extra']=$corpus['extra']?unserialize($corpus['extra']):array();
			$updatetime=$corpus['updatetime'];
			if($corpus['aid']) $metadata['--cover']=IO::getFileUri('attach::'.$corpus['aid']);
			
			//$metadata['--max-toc-links']=0;
			foreach($corpus['extra'] as $key => $val){
				$metadata[$key]=$val;
			}
			$metadata['--title']=getstr($corpus['name']);
			/*书的meta信息
			$metadata['--authors']='柯林斯著；耿芳译';//作者多个使用逗号或空格等分割符隔开
			$metadata['--publisher']=$_G['setting']['sitename'].'出版社';//出版发行
			$metadata['--isbn']='978-7-5063-5566-7';//isbn
			$metadata['--series']='嘲笑鸟'; //丛书系列
			$metadata['--series-index']=2;//系列号，无效
			$metadata['--book-producer']=$_G['setting']['sitename'].'生成';//发行商
			*/
			//$metadata['--tags']='wwww,rrrr';//标签，使用分隔符隔开（逗号、空格等）
			//$metadata['--author-sort']='';//排序作者

			//$metadata['--language']='中文';//语言,无效
			$metadata['--pubdate']=dgmdate(TIMESTAMP,'Y-m-d');    //发行时间；无效
			//$metadata['--comments']='劳动法吉林省地方都是浪费大师傅但是士大夫士大夫大师傅十分生动';					  //书籍描述
			$metadata['--timestamp']=dgmdate($updatetime,'Y-m-d');//日期
			//$metadata['--rating']=5;//评星，1~5,无效

		}
		$needconvert=1;
		$cmk = 0;
	

		switch($format){
			case 'txt':
				$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,'txt');
				if($record['dateline']>$updatetime){
					exit(json_encode(array('msg'=>'success')));
				}
				$needconvert=0;
				break;
			case 'md':
				if(!$fid) exit(json_encode(array('error'=>'仅支持单篇文档')));
				$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,'md');
				if($record['dateline']>$updatetime){
					exit(json_encode(array('msg'=>'success')));
				}

				$needconvert=0;
				break;
			case 'html':
				$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,'zip',0);
				if($record['dateline']>$updatetime){
					exit(json_encode(array('msg'=>'success')));
				}

				$needconvert=0;
				break;
			case 'zip':
				$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,'zip');
				if($record['dateline']>$updatetime){
					exit(json_encode(array('msg'=>'success')));
				}
				$needconvert=0;
				break;
			case 'pdf':
				$cmk = 1;
				$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,'pdf');
				if($record['dateline']>$updatetime){
					exit(json_encode(array('msg'=>'success')));
				}
				$meta['pdf']=array('--no-chapters-in-toc');
				$meta['pdf']['--margin-bottom']='50';
				$meta['pdf']['--margin-left']='50';
				$meta['pdf']['--margin-top']='50';
				$meta['pdf']['--margin-right']='50';
				$meta['pdf']['--paper-size']='a4';
				$meta['pdf']['--pdf-default-font-size']=14;
				$meta['pdf']['--toc-title']='目录';
				break;
			case 'epub':
				$cmk = 1;
				$needconvert = 0;
                $record = C::t('corpus_convert')->fetch_by_format($cid, $fid, 'epub', $cmk);
                if ($record['dateline'] >= $updatetime) {
                    exit(json_encode(array('msg' => 'success')));
                }
				$format='epub_1';
				$meta['epub'] = array('--no-default-epub-cover');
				break;

			case 'docx':
				$cmk = 1;
				$meta['docx']=array('--docx-no-cover','--docx-no-toc');
			 	$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,$format);
				if($record['dateline']>=$updatetime){
					exit(json_encode(array('msg'=>'success')));
				}
				$meta['docx']['--margin-bottom']='60';
				$meta['docx']['--margin-left']='60';
				$meta['docx']['--margin-top']='60';
				$meta['docx']['--margin-right']='60';
				$meta['docx']['--docx-page-size']='a4';
				break;
			case 'mobi':
				$cmk = 2;
				$meta['mobi']=array('--mobi-toc-at-start','--mobi-keep-original-images');
				$meta['mobi']['--toc-title']='目录';
				$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,$format);
				if($record['dateline']>=$updatetime){
					exit(json_encode(array('msg'=>'success')));
				}
				break;
			case 'azw3':
				$cmk = 2;
				$meta['azw3']=array('0'=>'--mobi-toc-at-start');
				$meta['azw3']['--toc-title']='目录';
				$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,$format);
				if($record['dateline']>=$updatetime){
					exit(json_encode(array('msg'=>'success')));
				}
				break;
		}
		$cronid=intval($_GET['cronid']);
		if($cronid && ($cron=C::t('corpus_convert_cron')->fetch($cronid))){
			switch($cron['status']){
				case '-1': //转换失败
					//C::t('corpus_convert_cron')->delete($cronid);
					return array('error'=>$cron['error']);
					exit(json_encode(array('error'=>$cron['error'])));
					//dfsockopen(CONVERTURL.'misc.php?mod=convert&cronid='.$cronid,0, '', '', false, '',1);

				case '0'://启动转换
					dfsockopen(CONVERTURL.'index.php?mod=corpus&op=convert&cronid='.$cronid,0, '', '', false, '',1);
					return array('msg'=>'converting','cronid'=>$cronid);
					exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
				case '1':
					exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
				case '2': //转换成功
					C::t('corpus_convert')->insert($cron['cid'],$cron['fid'],$cron['format'],$cron['tpath']);
					$cron_format = explode('_', $cron['format']);
					if($format==$cron_format[0]){
						return array('msg'=>'success');
						exit(json_encode(array('msg'=>'success')));
					}
					break;
			}
		}

		$remoteid=C::t('local_storage')->getRemoteId(); //未指定时根据路由获取；
		$bz=io_remote::getBzByRemoteid($remoteid);
		if($bz=='dzz'){
			$bz='dzz::';
		}else{
			$bz.='/';
		}
		if($needconvert){
			if (($epub = C::t('corpus_convert')->fetch_by_format($cid, $fid, 'epub', $cmk)) && $epub['dateline'] >= $updatetime) {
                $opath = $epub['path'];
                $tpath = $bz . self::getPath($cid, $fid, $format, $cmk);
            } else {
                $opath = $cid . '_' . $fid;
				$tpath = $bz . self::getPath($cid, $fid, 'epub', $cmk);
				$format = 'epub_' . $cmk;
				$metadata = array('--no-default-epub-cover');
            }
		}else{
			$opath = $cid . '_' . $fid;
            $tpath = $bz . self::getPath($cid, $fid, $format,$cmk);
		}

		foreach($meta[$format] as $key => $val){
			if(is_numeric($key)){
				$metadata[]=$val;
			}else{
				$metadata[$key]=$val;
			}
		}

		if($cron_old=DB::fetch_first("select cronid,dateline from %t where spath=%s and format=%s",array('corpus_convert_cron',$opath,$format))){
				 
			if($cron_old['dateline']<$updatetime){
				$updatearr=array('dateline'=>TIMESTAMP,'status'=>0,'metadata'=>$metadata?serialize($metadata):'');
				C::t('corpus_convert_cron')->update($cron_old['cronid'],$updatearr);
			}else{
				$updatearr=array('metadata'=>$metadata?serialize($metadata):'');
				C::t('corpus_convert_cron')->update($cron_old['cronid'],$updatearr);
			}
			return array('msg'=>'converting','cronid'=>$cron_old['cronid']);
			exit(json_encode(array('msg'=>'converting','cronid'=>$cron_old['cronid'])));
		}else{
			$setarr=array('spath'=>$opath,
						  'tpath'=>$tpath,
						  'dateline'=>TIMESTAMP,
						  'metadata'=>$metadata?serialize($metadata):'',
						  'format'=>$format,
						  'cid'=>$cid,
						  'fid'=>$fid
					  );

			if($cronid=C::t('corpus_convert_cron')->insert($setarr,1)){
				dfsockopen(CONVERTURL.'index.php?mod=corpus&op=convert&cronid='.$cronid,0, '', '', false, '',1);
				return array('msg'=>'converting','cronid'=>$cronid);
				exit(json_encode(array('msg'=>'converting','cronid'=>$cronid)));
			}
		}
	}
	public function toEpub($cid,$fid=0,$metadata=array(),$cmk = 1)
    {//转换epub
   
        $corpus = C::t('corpus')->fetch($cid);
        
        $filename = getglobal('setting/attachdir') . self::getPath($cid, $fid, 'epub', $cmk);
        $root_dir = dirname($filename);
        $book = new EPub($cmk);
        $book->setTitle($corpus['name']);
        $book->setLanguage("zh");
		
        if($metadata['--authors'])   $book->setAuthor($metadata['--authors']);
        if($metadata['--publisher']) $book->setPublisher($metadata['--publisher']);
        if($metadata['--rights'])    $book->setRights($metadata['--rights']);
        if($metadata['--subject'])   $book->setSubject($metadata['--subject']);
        if($metadata['--generator']) $book->setGenerator($metadata['--generator']);
        $book->setDate(time());
        $cssSaveUrl =DZZ_ROOT.'/data/attachment/cache/'.uniqid().time().'.css';
        $start = microtime(true);

        if (self::epubContent($book, $cid, $root_dir,$cssSaveUrl, 0, $cmk)) {
        	
            if ($corpus['aid']) {
                $coverurl = IO::getStream('attach::' . $corpus['aid']);
                $book->setCoverImage($coverurl, '', '', $cmk);
            }else{
                    $bgcolor = $corpus['color'] ? $corpus['color'] :'#fff';
                    $cover = new createCover($corpus['name'],$bgcolor);
                    $coverData = $cover->makCover();
                    $book->setCoverImage('cover.png', $coverData, 'image/png', $cmk);

            }
            $remoteid = C::t('local_storage')->getRemoteId(); //未指定时根据路由获取；
            $bz = io_remote::getBzByRemoteid($remoteid);
            $target = self::getPath($cid, $fid, 'epub', $cmk);
            $savePath = getglobal('setting/attachdir').$target;
            if($book->finalize($savePath)){         	
                @unlink($cssSaveUrl);
                if ($bz == 'dzz') {
                    $tpath = 'dzz::' . $target;
                } else {
                    $opath = 'dzz::' . $target;
                    $tpath = $bz . '/' . $target;
                    $pathinfo = pathinfo($tpath);
                    if ($opath != $tpath) {
                        if ($re = IO::multiUpload($opath, $pathinfo['dirname'] . '/', $pathinfo['basename'], array(), "overwrite")) {
                            if ($re['error']) {
                                C::t('corpus_convert')->insert($cid, $fid, 'epub_' . $cmk, $opath);
                                return $re;
                            }
                            @unlink(getglobal('setting/attachdir') . $target);
                        }
                    }
                }
                C::t('corpus_convert')->insert($cid, $fid, 'epub_' . $cmk, $tpath);
                return $tpath;
            }else {
                return false;
            }
        }else{
        	return false;
        }


    }

    public function epubContent($book, $cid,$root_dir, $cssSaveUrl,$pfid = 0, $cmk = 1)//原函数第二个参数为$corpus_info
    {
        //static $index = 0;
        foreach (C::t('corpus_class')->fetch_all_by_pfid($cid, $pfid) as $value) {
           
                $returns = array();
                if ($ret = self::savePageByFid($cid,$value['fid'], $root_dir, $cmk, 0, 'epub', $book,$cssSaveUrl)) {
                    $returns[$value['fid']] = $ret;
                    $returns['pfid'] = $pfid;
                    $returns['cmk'] = $cmk;
                   
                    if($book->addChapter($value['fid'], $value['fname'] . '.xhtml', $returns)){
                        self::epubContent($book, $cid, $root_dir, $cssSaveUrl,$value['fid'], $cmk);
                    }

                }
           
        }
        return true;
    }
	public function toZip($cid,$fid,$cmk=0){
		if($fid){
			$table=C::t('corpus_class')->getTableByFid($fid);
			$class=DB::fetch_first("select r.dateline,c.fname from %t c LEFT JOIN %t r ON c.revid=r.revid where c.fid=%d",array($table,'corpus_reversion',$fid));
			$updatetime=$class['dateline'];
			$name=$class['fname'];
		}else{
			$corpus=C::t('corpus')->fetch($cid);
			$updatetime=$corpus['updatetime'];
			$name=$corpus['name'];
		}
		$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,'zip',$cmk);
		$target=self::getPath($cid,$fid,'zip',$cmk);
		//$filepath=getglobal('setting/attachdir').;
		if($record['dateline']>=$updatetime){
			 return $record['path'];
		}else{
			if($filename=self::toHtml($cid,$fid,$cmk)){

				require_once libfile('class/zip1');
				$zip=new Zip();
				$folder=dirname($filename);
				$zip->addDirectoryContent($folder,'');
				$zip->finalize();
				$zip->setZipFile(getglobal('setting/attachdir').$target);
				$zip->closeZipFile();
				self::removedir($folder);
				//上传到云端
				$remoteid=C::t('local_storage')->getRemoteId(); //未指定时根据路由获取；
				$bz=io_remote::getBzByRemoteid($remoteid);
				if($bz=='dzz'){
					$tpath='dzz::'.$target;
				}else{
					$opath='dzz::'.$target;
					$tpath=$bz.'/'.$target;
					$pathinfo=pathinfo($tpath);
					if($opath!=$tpath){
						if($re=IO::multiUpload($opath,$pathinfo['dirname'].'/',$pathinfo['basename'],array(),"overwrite")){
							if($re['error']){
								C::t('corpus_convert')->insert($cid,$fid,'zip'.($cmk?'_nmk':''),$opath);
								return $re;
							}
							@unlink(getglobal('setting/attachdir').$target);
						}
					}
				}
				C::t('corpus_convert')->insert($cid,$fid,'zip'.($cmk?'_nmk':''),$tpath);
				
				return $tpath;
			}else{
				return false;
			}
		}
	}

	public function toHtml($cid,$fid,$cmk=0){

		if($fid){//导出文章
			$dir='';
			$filename=getglobal('setting/attachdir').self::getPath($cid,$fid,'html',$cmk);
			$root_dir = dirname($filename);
			if(!is_dir($targetPath = dirname($filename))){
				dmkdir($targetPath,0777,false);
			}
			self::savePageByFid($cid,$fid,$root_dir,$cmk,1);
			return $filename;

		}elseif($cid){//导出文集
			$corpus=C::t('corpus')->fetch($cid);
			$filename=getglobal('setting/attachdir').self::getPath($cid,0,'html',$cmk);
			$root_dir = dirname($filename);
			$filename=$root_dir.'/index.html';
			if(!is_dir($targetPath = dirname($filename))){
				dmkdir($targetPath,0777,false);
			}

			$toc=self::toHtml_sub($cid,0,'',$root_dir,$cmk);
			$index='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
				.'<html xmlns="http://www.w3.org/1999/xhtml">'
				.'<head>'
				.'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
				.'<title>'.$corpus['name'].'</title><style>ul{list-style:none;}ul li{line-height:26px;}a{text-decoration:none}</style></head><body>'
				.'<h1 style="text-align:center">'.$corpus['name'].'</h1>'
				.'<ul>'
				//.'<li class="cover"><a href="cover.html">封面</a></li>'
				.$toc
				.'</ul></body></html>';

			$filename=$root_dir.'/index.html';
			file_put_contents($filename,$index);
			if(!$cmk) self::savePageByFid($cid,'cover',$root_dir,$cmk);
			return $filename;

		}
		return false;
	}

	private function toHtml_sub($cid,$pfid,$dir,$root_dir,$cmk){
		static $toc;
		
		foreach(C::t('corpus_class')->fetch_all_by_pfid($cid,$pfid) as $value){
			self::savePageByFid($cid,$value['fid'],$root_dir.($dir?'/'.rtrim($dir,'/'):''),$cmk);
			if($value['type']=='folder'){
				$toc.='<li class="folder" fid="'.$value['fid'].'"><a href="'.$dir.$value['fid'].($cmk?'.xhtml':'.html').'">'.$value['fname'].'</a>';
				$toc.='<ul>';
				self::toHtml_sub($cid,$value['fid'],$dir,$root_dir,$cmk);
				$toc.='</ul></li>';
			}else{
				//$rpath=preg_replace("/\d+/",'..',$dir);
				$toc.='<li class="file" fid="'.$value['fid'].'"><a href="'.$dir.$value['fid'].($cmk?'.xhtml':'.html').'">'.$value['fname'].'</a></li>';
			}
		}
		return $toc;
	}
	private function savePageByFid($cid,$fid, $root_dir, $cmk = 0, $index = 0, $format = 'zip', $book = '',$cssSaveUrl=''){//保存一篇文章到本地目录；
		$filename=$root_dir.'/'.($index?'index':$fid).($cmk?'.xhtml':'.html');
		//如果已经存在，直接跳过
		if(is_file($filename)) return true;
		$targetPath = dirname($filename);
		if(!is_dir($targetPath)){
			dmkdir($targetPath,0777,false);
		}
		$url==$html='';
		if($fid=='cover'){
			$url = getglobal('siteurl') . DZZSCRIPT . '?mod=corpus&op=view&cid=' . $cid;
            $html = file_get_contents($url);
            if ($html) {
                $html = str_replace(getglobal('siteurl'), '', $html);
            } else {
				 $html = ' ';
			}
		}elseif($fid>0){
			$table=C::t('corpus_class')->getTableByFid($fid);
			$class=DB::fetch_first("select c.fname,v.content,v.code,v.remoteid from %t c LEFT JOIN %t v ON c.revid=v.revid where c.fid=%d",array($table,'corpus_reversion',$fid));
			if($class['remoteid']) $class['content']=IO::getFileContent($class['content']);
			$corpus=C::t('corpus')->fetch($cid);
			if($corpus['css']){
				$csslink='<link href="'.IO::getFileUri('attach::'.$corpus['css']).'" rel="stylesheet" type="text/css">';
			 }else{
				$csslink=''; 
			 }
			
			if($cmk){
				//$class=DB::fetch_first("select c.fname,v.content from %t c LEFT JOIN %t v ON c.revid=v.revid where c.fid=%d",array('corpus_class','corpus_reversion',$fid));
				if($class['code']==1){
					$Parsedown=new markdown2html();
					$class['content']=$Parsedown->text($class['content']);
				}
				$html='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
					.'<html xmlns="http://www.w3.org/1999/xhtml">'
					.'<head>'
					.'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
					.'	<title>'.$class['fname'].'</title>';
			   if($corpus['css']){
				 $html.=$csslink;
			   }
			   $html.='	</head>'
					 .'	<body>'
				 	 .'<h1 class="chapter">'.$class['fname'].'</h1>'
			     	 .$class['content'].'</body></html>';

			}else{
				$output_template=self::getTemplate();
				
				$html=str_replace(array('{csslink}','{fname}','{content}'),array($csslink,$class['fname'],$class['content']),$output_template['header']);
				if($class['code']){
					$html.=str_replace(array('{fname}','{content}'),array($class['fname'],$class['content']),$output_template['code_1']);
				}else{
					$html.=str_replace(array('{fname}','{content}'),array($class['fname'],$class['content']),$output_template['code_0']);
				}
				$html.=$output_template['footer'];
			}
			unset($class);
		}
		if(empty($html)) $html=' ';
			
		if ($format == 'epub') {
			self::setPosterPageMedia($html);
			return array('name' => basename($filename), 'content' => $html);
		} else {
			$html=str_replace(getglobal('siteurl'),'',$html);
			//$html=preg_replace("/[\r\n]/",'',$html);
			$html=preg_replace_callback("/index\.php\?mod=corpus(&|&amp;)op=list(&|&amp;)cid=\d+(&|&amp;)fid=(\d+)/i",function($matches){
				return $matches[4].'.html';
			},$html);
			if(!$cmk){
				
				self::savePageJs($html,'',$root_dir);
			}
			self::savePageCss($html,'',$root_dir);
			self::savePageImage($html,'',$root_dir);
			self::savePageMedia($html,'',$root_dir);
			$html=preg_replace("/\"index\.php\?/i",'"'.getglobal('siteurl').'index.php?',$html);
			file_put_contents($filename, $html);
			unset($html);
			return true;
		}
	}
	private function savePageCss(&$html,$path,$root_dir){

		$cssdir=$root_dir;//.'/Styles';
		if(preg_match_all("/<link.+?href=\"(.+?)\".*?>/i",$html,$matches)){
			
			$search=array();
			$replace=array();
			foreach($matches[1] as $value){
				if(strpos($cssurl,'static')===0){
					$cssurl=trim(preg_replace("/\?\w+$/",'',$value));
					$filename=$cssdir.'/'.$cssurl;
					$targetPath = dirname($filename);
					if(!is_dir($targetPath)){
						dmkdir($targetPath,0777,false);
					}
					//$replace[]=$path.$cssurl;
				}else{
					$cssurl=trim($value);
					$search[]=$value;
					$filename=$cssdir.'/'.md5($cssurl).'.css';
					$replace[]=($path?$path.'/':'').md5($cssurl).'.css';
				}
				if(!is_file($filename)){
					file_put_contents($filename,file_get_contents($cssurl));
				}
			}
			if($search) $html=str_replace($search,$replace,$html);
		}
	}
	private function savePageJs(&$html,$path,$root_dir){

		$jsdir=$root_dir;//.'/Scripts';

		if(preg_match_all("/<script.+?src=\"(.+?)\".*?>/i",$html,$matches)){
			//if(!is_dir($jsdir)) mkdir($jsdir,0777);
			$search=array();
			$replace=array();
			foreach($matches[1] as $value){

				
				if(strpos($jsurl,'static')===0){
					$jsurl=trim(preg_replace("/\?\w+$/",'',$value));
					$filename=$jsdir.'/'.$jsurl;
					$targetPath = dirname($filename);
					if(!is_dir($targetPath)){
						dmkdir($targetPath,0777,false);
					}
				}else{
					$jsurl=$value;
					$search[]=$value;
					$filename=$jsdir.'/'.md5($jsurl).'.js';
					$replace[]=($path?$path.'/':'').md5($jsurl).'.js';
				}

				if(!is_file($filename)){
					file_put_contents($filename,file_get_contents($jsurl));
				}
			}
			if($search) $html=str_replace($search,$replace,$html);
		}
	}
	private function savePageImage(&$html,$path,$root_dir){

		$imgdir=$root_dir;

		if(preg_match_all("/<img.+?src=\"(.+?)\".*?>/i",$html,$matches)){
			//if(!is_dir($imgdir)) mkdir($imgdir,0777);
			$search=array();
			$replace=array();
			foreach($matches[1] as $key => $value){
				$imgurl=str_replace('&amp;','&',$value);
				if(!preg_match("/^(http|ftp|https|mms)\:\/\//i", $imgurl)){
					$imgurl=getglobal('siteurl').$imgurl;
				}

				if($imginfo=@getimagesize($imgurl)){
					$ext=str_replace('image/','',$imginfo['mime']);
					$filename=md5($imgurl).'.'.$ext;
				}else{
					continue;
				}
				if(!is_file($imgdir.'/'.$filename)){
					$targetPath = dirname($imgdir.'/'.$filename);
					if(!is_dir($targetPath)){
						dmkdir($targetPath,0777,false);
					}
					file_put_contents($imgdir.'/'.$filename,file_get_contents($imgurl));
				}
				$search[]=$value;
				$replace[]=($path?$path.'/':'').$filename;
			}
			if($search) $html=str_replace($search,$replace,$html);
		}
	}
	private function savePageMedia(&$html,$path,$root_dir){
		@set_time_limit(0);
		$imgdir=$root_dir;
		
		if(preg_match_all("/<(video|audio)\s+class=\"(.+?)\"(\s+poster=\"(.+?)\"){0,1}.*?>[\s\S]*?<source.*?src=\"(.+?)\".*?>[\s\S]*?<\/(audio|video)>/i",$html,$matches)){
			//if(!is_dir($imgdir)) mkdir($imgdir,0777);
			$search=array();
			$replace=array();
			//print_r($matches);
			foreach($matches[5] as $key => $value){
				$imgurl=str_replace('&amp;','&',$value);

				if(!preg_match("/^(http|ftp|https|mms)\:\/\//i", $imgurl)){
					$imgurl=getglobal('siteurl').$imgurl;
				}
				$imgurl=str_replace('//index.php','/index.php',$imgurl);
				$ext=strtolower(substr(strrchr($imgurl, '.'), 1, 10));
				if(!in_array($ext,array('mp3','mp4'))) continue;
				$filename=md5($imgurl).'.'.$ext;
				
				if(!is_file($imgdir.'/'.$filename)){
					$targetPath = dirname($imgdir.'/'.$filename);
					if(!is_dir($targetPath)){
						dmkdir($targetPath,0777,false);
					}
					$rh = fopen($imgurl, 'rb');
					$wh = fopen($imgdir.'/'.$filename, 'wb');
					if ($rh===false || $wh===false) {
					   continue;
					}
					while (!feof($rh)) {
						if(fwrite($wh, fread($rh, 8192)) === FALSE) {
						  usleep(500);
						  if(fwrite($wh, fread($rh, 8192)) === FALSE) {
							 usleep(500);
							 if (fwrite($wh, fread($rh, 8192)) === FALSE) {
								  break;
							  }
						  }
						}
					}
					fclose($rh);
					fclose($wh);
					//file_put_contents($imgdir.'/'.$filename,file_get_contents($imgurl));
				}
				$search[]=$matches[0][$key];
				if($matches[1][$key]=='video'){
					if($matches[4][$key]){
						$posterurl=str_replace('&amp;','&',$matches[4][$key]);

						if(!preg_match("/^(http|ftp|https|mms)\:\/\//i", $posterurl)){
							$posterurl=getglobal('siteurl').$posterurl;
						}
						$ext=strtolower(substr(strrchr($posterurl, '.'), 1, 10));
						$poster=$root_dir.'/'.md5($posterurl).'.'.$ext;
						if(!is_file($poster)) file_put_contents($poster,file_get_contents($posterurl));
						$replace[]='<video class="duokan-video" controls poster="'.md5($posterurl).'.'.$ext.'" width="320"><source src="'.($path?$path.'/':'').$filename.'" type="video/mp4" /></video>';
					}else{
						$poster=$root_dir.'/video-poster.png';
						if(!is_file($poster)) file_put_contents($poster,file_get_contents(DZZ_ROOT.'./dzz/corpus/images/video-poster.png'));
						$replace[]='<video class="duokan-video" controls poster="video-poster.png" width="320"><source src="'.($path?$path.'/':'').$filename.'" type="video/mp4" /></video>';
					}
					
				}else{
					if($matches[4][$key]){
						$posterurl=str_replace('&amp;','&',$matches[4][$key]);

						if(!preg_match("/^(http|ftp|https|mms)\:\/\//i", $posterurl)){
							$posterurl=getglobal('siteurl').$posterurl;
						}
						$ext=strtolower(substr(strrchr($posterurl, '.'), 1, 10));
						$poster=$root_dir.'/'.md5($posterurl).'.'.$ext;
						if(!is_file($poster)) file_put_contents($poster,file_get_contents($posterurl));
						$replace[]='<video class="duokan-video" controls poster="'.md5($posterurl).'.'.$ext.'" width="320" height="30"><source src="'.($path?$path.'/':'').$filename.'" type="video/mp4" /></video>';
					}else{
						$placeholder=$root_dir.'/audio-placeholder.png';
						if(!is_file($placeholder)) file_put_contents($placeholder,file_get_contents(DZZ_ROOT.'./dzz/corpus/images/audio-placeholder.png'));
						$replace[]='<video class="duokan-video" controls poster="audio-placeholder.png" width="320" height="30"><source src="'.($path?$path.'/':'').$filename.'" type="audio/mpeg" /></video>';
					}
					
				}
			}

			if($search) $html=str_replace($search,$replace,$html);
		}
	}
	private function setPosterPageMedia(&$html){ //替换视频的大小和poster
		@set_time_limit(0);
		
		$html=preg_replace_callback("/<(video|audio)\s+class=\"(.+?)\"(\s+poster=\"(.+?)\"){0,1}.*?>[\s\S]*?<source.*?src=\"(.+?)\".*?>[\s\S]*?<\/(audio|video)>/i",function($matches){
			//if(!is_dir($imgdir)) mkdir($imgdir,0777);
			$search=array();
			$replace=array();
			//print_r($matches);
				$imgurl=str_replace('&amp;','&',$matches[5]);
				$ext=strtolower(substr(strrchr($imgurl, '.'), 1, 10));
				if(!in_array($ext,array('mp3','mp4'))) return '';
				
				if($matches[1]=='video'){
					if($matches[4]){
						$posterurl=str_replace('&amp;','&',$matches[4]);
						if(strpos($posterurl,getglobal('siteurl'))===false){
							if(!preg_match("/^(http|ftp|https|mms)\:\/\//i", $imgurl)){
								$posterurl=getglobal('siteurl').$posterurl;
							}
						}
						return '<video class="duokan-video" controls poster="'.($posterurl).'" width="100%"><source src="'.$matches[5].'" type="video/mp4" /></video>';
					}else{
						$posterurl=getglobal('siteurl').'/dzz/corpus/images/video-poster.png';
						return '<video class="duokan-video" controls poster="'.$posterurl.'" width="100%"><source src="'.$matches[5].'" type="video/mp4" /></video>';
					}
					
				}else{
					if($matches[4]){
						$posterurl=str_replace('&amp;','&',$matches[4]);

						
						if(strpos($posterurl,getglobal('siteurl'))===false){
							if(!preg_match("/^(http|ftp|https|mms)\:\/\//i", $imgurl)){
								$posterurl=getglobal('siteurl').$posterurl;
							}
						}
						
						return '<video class="duokan-video" controls poster="'.($posterurl).'" width="100%" height="30"><source src="'.($matches[5]).'" type="video/mp4" /></video>';
					}else{
						$placeholder=getglobal('siteurl').'/dzz/corpus/images/audio-placeholder.png';
						return '<video class="duokan-video" controls poster="'.$placeholder.'" width="100%" height="30"><source src="'.$matches[5].'" type="audio/mpeg" /></video>';
					}
				}

		},$html);
	}
	public function getPath($cid,$fid,$format,$cmk=0){

		$t = sprintf("%09d", $cid);
		$dir1 = substr($t, 0, 3);
		$dir2 = substr($t, 3, 2);
		$dir3 = substr($t, 5, 2);
		if($format=='html')	$target='convert/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.$cid.($fid?'_'.$fid:'').($cmk?'_nmk':'').'/'.($fid?$fid:'index').'.'.$format;
		elseif($format=='zip') $target='convert/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.($fid?$cid.'_'.$fid:$cid).($cmk?'_nmk':'').'.'.$format;
		elseif ($format == 'epub') $target = 'convert/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . ($fid ? $cid . '_' . $fid : $cid) . '_' . $cmk . '.' . $format;
		else $target='convert/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.($fid?$cid.'_'.$fid:$cid).'.'.$format;
		$targetPath = dirname(getglobal('setting/attachdir').$target);
		if(is_file(getglobal('setting/attachdir').$target)){

		}else{
			dmkdir($targetPath,0777,false);
		}
		return $target;
	}

	public function download($cid,$fid,$format,$cmk=0){
		if ($format == 'epub') {
            $cmk = 1;
        }
		if($format=='html'){
			$format='zip';
		}
		$record=C::t('corpus_convert')->fetch_by_format($cid,$fid,$format,$cmk);
		$path=$record['path'];
		$meta=IO::getMeta($path);
		$filepath=IO::getStream($path);
		
		if($fid){
			$table=C::t('corpus_class')->getTableByFid($fid);
			$name=DB::result_first("select fname from %t where fid=%d",array($table,$fid));
		}elseif($cid){
			$name=DB::result_first("select name from %t where cid=%d",array('corpus',$cid));
		}

		if($format=='html') $format='zip';
		$name.='.'.$format;
		$name = '"'.(strtolower(CHARSET) == 'utf-8' && (strexists($_SERVER['HTTP_USER_AGENT'], 'MSIE') || strexists($_SERVER['HTTP_USER_AGENT'], 'rv:11')) ? urlencode($name) : $name).'"';

		$chunk = 10 * 1024 * 1024;
		if(!$fp = @fopen($filepath, 'r')) {
			try{
				topshowmessage('文件不存在');
			}catch(Exception $e){
				exit('文件不存在');
			}
		}
		//增加下载统计；
		DB::query("update %t set downloads=downloads+1 where id=%d",array('corpus_convert',$record['id']));
		if($cid && !$fid) C::t('corpus')->increase($cid,array('downloads'=>1));
		$db = DB::object();
		$db->close();
		header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Transfer-Encoding: binary');
		header('Date: '.gmdate('D, d M Y H:i:s', TIMESTAMP).' GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', TIMESTAMP).' GMT');
		header('Content-Encoding: none');
		header('Content-Disposition: attachment; filename='.$name);
		header('Content-Type: application/octet-stream');
		header('Content-Length: '.($meta['size']));
		@ob_end_clean();if(getglobal('gzipcompress')) @ob_start('ob_gzhandler');
		while (!feof($fp)) {
			echo fread($fp, $chunk);
			@ob_flush();  // flush output
			@flush();
		}
		exit();
	}
	public function totxt($cid,$fid){
		$target=self::getPath($cid,$fid,'txt');
		$filepath=getglobal('setting/attachdir').$target;
		if($fid){//导出文章

			/*if(is_file($filepath) && filesize($filepath)>0 && filemtime($filepath)>=$ver['dateline']){

			}else{*/
				$class=C::t('corpus_class')->fetch($fid);
				$ver=C::t('corpus_reversion')->fetch($class['revid']);
				if($ver['remoteid']) $ver['content']=IO::getFileContent($ver['content']);
				//print_r($ver);
				if($ver['code']==1){
						$Parsedown=new markdown2html();
						$ver['content']=$Parsedown->text($ver['content']);
				}else{
					$ver['content']="\r\n\r\n".$class['fname']."\r\n\r\n".$ver['content'];
					$ver['content']=preg_replace("/<br.*?>/i","\r\n",$ver['content']);
					$ver['content']=preg_replace("/<\/p>/i","\r\n",$ver['content']);
					$ver['content']=preg_replace("/<p.*?>([\s\S]*?)<\/p>\s*([\r\n])*/i","$1\r\n",$ver['content']);
					$ver['content']=str_replace("&nbsp;"," ",$ver['content']);
					$ver['content']=htmlspecialchars_decode($ver['content']);
				}

				file_put_contents($filepath,strip_tags($ver['content']));
			//}
		}elseif($cid){//导出文集
			$corpus=C::t('corpus')->fetch($cid);
			if(is_file($filepath) && filemtime($filepath)>=$corpus['updatetime']){
			}else{
				file_put_contents($filepath,'');
				self::totxt_sub($filepath,$cid,0);
			}
		}
		//上传到云端

			$remoteid=C::t('local_storage')->getRemoteId(); //未指定时根据路由获取；
			$bz=io_remote::getBzByRemoteid($remoteid);
			if($bz=='dzz'){
				$tpath='dzz::'.$target;
			}else{
				$opath='dzz::'.$target;
				$tpath=$bz.'/'.$target;
				$pathinfo=pathinfo($tpath);
				if($re=IO::multiUpload($opath,$pathinfo['dirname'].'/',$pathinfo['basename'],array(),"overwrite")){
					if($re['error']){
						C::t('corpus_convert')->insert($cid,$fid,'txt',$Opath);
						return $re;
					}
					@unlink($filepath);
				}
			}
			C::t('corpus_convert')->insert($cid,$fid,'txt',$tpath);
		return true;
	}
	private function totxt_sub($filepath,$cid,$pfid){

		foreach(C::t('corpus_class')->fetch_all_by_pfid($cid,$pfid) as $value){
			if($value['type']=='folder'){
				file_put_contents($filepath,"\r\n".strip_tags($value['fname'])."\r\n\r\n",FILE_APPEND);
				self::totxt_sub($filepath,$cid,$value['fid']);
			}else{
				$ver=C::t('corpus_reversion')->fetch($value['revid']);
				if($ver['remoteid']) $ver['content']=IO::getFileContent($ver['content']);
				if($ver['code']==1){
					/*$Parsedown=new markdown2html();
					$ver['content']=$Parsedown->text($ver['content']);*/
				}else{

					//$ver['content'].="\r\n\r\n".$value['fname']."\r\n\r\n";
					$ver['content']=preg_replace("/<br.*?>/","\r\n",$ver['content']);
					$ver['content']=preg_replace("/<p.*?>([.\s\S]+?)<\/p>\s*([\r\n])*/i","$1\r\n",$ver['content']);
					$ver['content']=str_replace("&nbsp;"," ",$ver['content']);
					$ver['content']=strip_tags($ver['content']);
					$ver['content']=htmlspecialchars_decode($ver['content'],ENT_QUOTES);
				}
				if($ver['content']){
					 $ver['content']="\r\n\r\n".$value['fname']."\r\n\r\n".$ver['content'];
					file_put_contents($filepath,($ver['content']),FILE_APPEND);
				}
			}
		}
	}
	public function toMarkdown($cid,$fid){

		$target=self::getPath($cid,$fid,'md');
		$filepath=getglobal('setting/attachdir').$target;
		$class=C::t('corpus_class')->fetch($fid);
		$rev=C::t('corpus_reversion')->fetch($class['revid']);
		if($rev['remoteid']) $rev['content']=IO::getFileContent($rev['content']);
		$html=' ';
		if($rev['code']!=1){
			$converter = new html2markdown(array('strip_tags' => false));
			$rev['content'] = $converter->convert($rev['content']);
		}
		//图片替换为绝对地址
		$rev['content']=preg_replace("/\!\[(.*?)\]\(index\.php\?/i",'![$1]('.getglobal('siteurl').'index.php?',$rev['content']);
		file_put_contents($filepath,$rev['content']);

		//上传到云端
		$remoteid=C::t('local_storage')->getRemoteId(); //未指定时根据路由获取；
		$bz=io_remote::getBzByRemoteid($remoteid);
		if($bz=='dzz'){
			$tpath='dzz::'.$target;
		}else{
			$opath='dzz::'.$target;
			$tpath=$bz.'/'.$target;
			$pathinfo=pathinfo($tpath);
			if($re=IO::multiUpload($opath,$pathinfo['dirname'].'/',$pathinfo['basename'],array(),"overwrite")){
				if($re['error']){
					C::t('corpus_convert')->insert($cid,$fid,'md',$tpath);
					return $re;
				}
				@unlink($filepath);
			}
		}
		C::t('corpus_convert')->insert($cid,$fid,'md',$tpath);
		return true;
	}
	/*public function topdf($cid,$fid){

		$corpus=C::t('corpus')->fetch($cid);
		$filepath=getglobal('setting/attachdir').self::getPath($cid,$fid,'pdf');
		if(is_file($filepath) && filemtime($filepath)>=$corpus['updatetime']){
			self::download($cid,$fid,'pdf');
		}else{
			require_once DZZ_ROOT.'./core/class/wkhtmltopdf/pdf.php';
			$pdf = new Pdf();
			if($fid){//导出文章
				$class=C::t('corpus_class')->fetch($fid);
				$url=getglobal('siteurl').DZZSCRIPT.'?mod=corpus&op=view&cid='.$cid.'&fid='.$fid;
				$pdf->addPage($url);
				$pdf->saveAs($filepath);
				$pdf->send($class['fname'].'.pdf');
			}elseif($cid){//导出文集
				$coverurl=getglobal('siteurl').DZZSCRIPT.'?mod=corpus&op=view&cid='.$cid;
				$pdf->addCover($coverurl);
				self::topdf_sub($pdf,$cid,0);
				$pdf->saveAs($filepath);
				$pdf->send($corpus['name'].'.pdf');
			}

		}
	}
	private function topdf_sub($pdf,$cid,$pfid){
		foreach(C::t('corpus_class')->fetch_all_by_pfid($cid,$pfid) as $value){
			if($value['type']=='folder'){
				self::topdf_sub($pdf,$cid,$value['fid']);
			}else{
				$url=getglobal('siteurl').DZZSCRIPT.'?mod=corpus&op=view&cid='.$cid.'&fid='.$value['fid'];
				$pdf->addPage($url);
			}
		}
	}*/
	
	public function create_ncx($index,$toc,$opf){

		$content=file_get_contents($index);
		if(preg_match("/<ul.*?>(.+?)<\/ul><\/body><\/html>/i",$content,$matches)){
			$navmap=$matches[1];
		}else return false;
		unset($content);
		$navmap=preg_replace("/<li class=\"file\" fid=\"(\d+)\"><a href=\"(.+?)\">(.+?)<\/a><\/li>/i",'<navPoint id="$1" playOrder="0"><navLabel><text>$3</text></navLabel><content src="$2"/></navPoint>',$navmap);
		$navmap=preg_replace("/<li class=\"folder\" fid=\"(.+?)\">(.+?)<ul>/i",'<navPoint id="$1" playOrder="0"><navLabel><text>$2</text></navLabel><content src="$1.html"/>',$navmap);
		$navmap=str_replace("</ul></li>",'</navPoint>',$navmap);
		$navmap=preg_replace("/<li.+?<\/navPoint>/i",'',$navmap);
		$search=array();
		$replace=array();
		$contentopf=file_get_contents($opf);
		if(preg_match_all("/href=\"(\d+)_split_000\.(x?html)\"/i",$contentopf,$matches)){
			foreach($matches[1] as $key=>$fid){
				$search[]=$matches[1][$key].'.'.$matches[2][$key];
				$replace[]=$matches[1][$key].'_split_000.'.$matches[2][$key];
			}
		}
		unset($contentopf);
		if($search) $navmap=str_replace($search,$replace,$navmap);
		//print_r($search);print_r($replace);exit($navmap);
		$tcontent=file_get_contents($toc);
		$tcontent=preg_replace("/<navMap>([.\s\S]+?)<\/navMap>/i",'<navMap>'.$navmap.'</navMap>',$tcontent);
		return file_put_contents($toc,$tcontent);

	}
	public function replaceImgSrc($html){
		//转化图像到附件系统；
		$search=array();
		$replace=array();
		if(preg_match_all("/(\"|\()index\.php\?mod=io(&|&amp;)op=thumbnail(&|&amp;)width=\d+(&|&amp;)height=\d+(&|&amp;)original=\d(&|&amp;)path=(.+?)(\"|\))/i",$html,$matches)){
			//$paths=$matches[6];
			foreach($matches[7] as $key=> $value){
				if($path=dzzdecode(trim($value))){
					if(strpos($path,'attach::')!==false){
						$src=IO::getFileUri($path);
						$search[]=$matches[0][$key];
						$replace[]=$matches[1][$key].$src.$matches[8][$key];
					}
				}
			}
			$html=str_replace($search,$replace,$html);

		}
		return $html;
	}
	public function docxtohtml($src){ //转化docx到html
		require_once MOD_PATH.'/class/phpDocx/classes/TransformDoc.inc.php';
		$transformDoc= new TransformDoc;
		$transformDoc->setStrFile($src);
		$transformDoc->generateXHTML();
		if(extension_loaded('tidy')){
			$transformDoc->validatorXHTML();
			$html=$transformDoc->getStrXHTML();
		}else{
			$html=$transformDoc->getStrXHTML();
			if(preg_match("/<body.*?>([.\s\S]+?)<\/body>/is",$html,$matches)){
				$html=$matches[1];
			}else{
				$html='';
			}
		}
		//转化图像到附件系统；
		$html=preg_replace("/class=\".+?\"/i",'',$html);
		//转换图片
		$html=preg_replace_callback("/<img.*?src=\"(.+?)\"[.\s\S]+?>/i",function($matches){

			$filepath=str_replace(getglobal('setting/attachurl'),DZZ_ROOT.'./data/attachment/',$matches[1]);
			$filename=strrpos($matches[1],'/')?substr($matches[1],strrpos($matches[1],'/')+1):$matches[1];
			if($attach=_savetoattachment($filepath,$filename) ){
				return '<img class="dzz-image" src="'.$attach['url'].'" title="'.$attach['filename'].'" alt="'.$attach['filename'].'" dsize="'.$attach['dsize'].'" aid="'.$attach['aid'].'" apath="'.$attach['apath'].'" ext="'.$attach['filetype'].'">';
			}else{
				return $matches[0];
			}
		},$html);

		return $html;
	}


	public function savetoattachment($file_path,$filename) {
	 global $_G;
	 	if(!is_file($file_path)) return false;
        $md5=md5_file($file_path);
		$filesize=filesize($file_path);
		if($md5 && $attach=DB::fetch_first("select * from %t where md5=%s and filesize=%d",array('attachment',$md5,$filesize))){
			$attach['filename']=$filename;
			$pathinfo = pathinfo($filename);
			$ext = $pathinfo['extension']?$pathinfo['extension']:'';
			$attach['filetype']=$ext;
			/*if(in_array(strtolower($attach['filetype']),array('png','jpeg','jpg','gif','bmp'))){
				$attach['img']=C::t('attachment')->getThumbByAid($attach,$this->options['thumbnail']['max-width'],$this->options['thumbnail']['max-height']);
				$attach['isimage']=1;
			}else{
				$attach['img']=geticonfromext($ext);
				$attach['isimage']=0;
			}*/
			@unlink($file_path);
			return $attach;
		}else{
			$target=self::getAttachPath($filename);
			$pathinfo = pathinfo($filename);
			$ext = $pathinfo['extension']?$pathinfo['extension']:'';
			if($ext && in_array(strtolower($ext) ,getglobal('setting/unRunExts'))){
				$unrun=1;
			}else{
				$unrun=0;
			}
			$filepath=$_G['setting']['attachdir'].$target;
			$handle=fopen($file_path, 'r');
			$handle1=fopen($filepath,'w');
			while (!feof($handle)) {
			   fwrite($handle1,fread($handle, 8192));
			}
			fclose($handle);
			fclose($handle1);
			@unlink($file_path);

			$filesize=filesize($filepath);
			$remote=0;
			$remoteid=C::t('local_storage')->getRemoteId(); //未指定时根据路由获取；
			$bz=io_remote::getBzByRemoteid($remoteid);
			if($bz=='dzz'){
				$remote=0;
			}else{
				$opath='dzz::'.$target;
				$pathinfo=pathinfo($tpath);
				if($re=IO::multiUpload($opath,$pathinfo['dirname'].'/',$pathinfo['basename'],array(),"overwrite")){
					if($re['error']) $remote=0;;
					$remote=$remoteid;
				}
			}
        	$attach=array(

				'filesize'=>$filesize,
				'attachment'=>$target,
				'filetype'=>strtolower($ext),
				'filename' =>$filename,
				'remote'=>$remote,
				'copys' => 0,
				'md5'=>$md5,
				'unrun'=>$unrun,
				'dateline' => $_G['timestamp'],
			);

			if($attach['aid']=C::t('attachment')->insert($attach,1)){
				C::t('local_storage')->update_usesize_by_remoteid($attach['remote'],$attach['filesize']);
				 //dfsockopen($_G['siteurl'].'misc.php?mod=movetospace&aid='.$attach['aid'].'&remoteid=0',0, '', '', FALSE, '',1);
				/*if(in_array(strtolower($attach['filetype']),array('png','jpeg','jpg','gif','bmp'))){
					$attach['img']=C::t('attachment')->getThumbByAid($attach['aid'],$this->options['thumbnail']['max-width'],$this->options['thumbnail']['max-height']);
					$attach['isimage']=1;
				}else{
					$attach['img']=geticonfromext($ext);
					$attach['isimage']=0;
				}*/
				//$attach['ffilesize']=formatsize($tattach['filesize']);
				return $attach;
			}else{
				return false;
			}
		}
    }
	public function getAttachPath($filename,$dir='dzz'){
		global $_G;
			$pathinfo = pathinfo($filename);
			$ext = $pathinfo['extension']?($pathinfo['extension']):'';
			if($ext && in_array(strtolower($ext) ,getglobal('setting/unRunExts'))){
				$ext='dzz';
			}
		    $subdir = $subdir1 = $subdir2 = '';
			$subdir1 = date('Ym');
			$subdir2 = date('d');
			$subdir = $subdir1.'/'.$subdir2.'/';
			$target1=$dir.'/'.$subdir.'index.html';
			$target=$dir.'/'.$subdir;
			$target_attach=$_G['setting']['attachdir'].$target1;
			$targetpath = dirname($target_attach);
			dmkdir($targetpath,0777,false);
			return $target.date('His').''.strtolower(random(16)).'.'.$ext;
	 }
	static function removedir($dirname, $keepdir = FALSE ,$time=0) {
		$dirname = str_replace(array( "\n", "\r", '..'), array('', '', ''), $dirname);
		if(!is_dir($dirname)) {
			return FALSE;
		}
		$handle = opendir($dirname);
		while(($file = readdir($handle)) !== FALSE) {
			if($file != '.' && $file != '..') {
				$dir = $dirname . '/' . $file;
				$mtime=filemtime($dir);
				is_dir($dir) ? self::removedir($dir) :  unlink($dir);
			}
		}
		closedir($handle);
		return !$keepdir ? (@rmdir($dirname) ? TRUE : FALSE) : TRUE;
	}
	public function downfile($file_source, $file_target) {

        $rh = fopen($file_source, 'rb');
        $wh = fopen($file_target, 'wb');
        if ($rh===false || $wh===false) {
           return false;
        }
        while (!feof($rh)) {
            if (fwrite($wh, fread($rh, 8192)) === FALSE) {
                   // 'Download error: Cannot write to file ('.$file_target.')';
                   return false;
               }
        }
        fclose($rh);
        fclose($wh);
        return true;
    }
	public function getTemplate(){
		$output_template=array();
		$output_template['header']='<!DOCTYPE html>'
				.'<html>'
				.'<head>'
				.'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
				.'<meta name="viewport" content="width=device-width, initial-scale=1.0">'
				.'<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />'
				.'{csslink}'
				.'<title>{fname}</title>'
				.'<meta name="renderer" content="webkit">'
				.'<link rel="stylesheet" type="text/css" href="static/bootstrap/css/bootstrap.min.css">'
				.'<link href="dzz/corpus/images/document.css" rel="stylesheet" media="all">'
				.'<script src="static/jquery/jquery.min.js" type="text/javascript"></script>'
				.'<script src="static/jquery/jquery.json-2.4.min.js" type="text/javascript"></script>'
				.'<!--[if lt IE 9]>'
				.'  <script src="static/bootstrap/js/html5shiv.min.js" type="text/javascript"></script>'
				.'  <script src="static/bootstrap/js/respond.min.js" type="text/javascript"></script>'
				.'<![endif]-->'
				.'<link href="static/css/common.css" rel="stylesheet" media="all">'
				.'<link href="dzz/corpus/images/corpus.css" rel="stylesheet" media="all">'
				/*."<script type=\"text/javascript\">var DZZSCRIPT='index.php', STATICURL = 'static/', IMGDIR = 'static/image/common',SITEURL = 'http://127.0.0.1:99/';</script>"*/
				.'<script src="static/js/common.js" type="text/javascript"></script>'
				.'</head>'
				.'<body>';

		$output_template['code_1']='<link rel="stylesheet" href="dzz/corpus/scripts/editor.md/lib/katex/katex.min.css" />'
				.'<link rel="stylesheet" href="dzz/corpus/scripts/editor.md/css/editormd.preview.css" />'
				.'<div class="document-container  clearfix" >'
				.'	<div id="topic_container" class="topic-container" style="padding-top:0">'
				.'		 <h1 class="document-subject text-center chapter">{fname}</h1>'
				.'		<div id="document_body" class="document-body clearfix">'
				.'			<textarea style="display:none">{content}</textarea>'
				.'      </div>'
				.'   </div>'
				.'</div>'
				.'<script src="dzz/corpus/scripts/editor.md/lib/marked.min.js" type="text/javascript"></script>'
				.'<script src="dzz/corpus/scripts/editor.md/lib/prettify.min.js" type="text/javascript"></script>'
				.'<script src="dzz/corpus/scripts/editor.md/lib/raphael.min.js" type="text/javascript"></script>'
				.'<script src="dzz/corpus/scripts/editor.md/lib/underscore.min.js" type="text/javascript"></script>'
				.'<script src="dzz/corpus/scripts/editor.md/lib/sequence-diagram.min.js" type="text/javascript"></script>'
				.'<script src="dzz/corpus/scripts/editor.md/lib/flowchart.min.js" type="text/javascript"></script>'
				.'<script src="dzz/corpus/scripts/editor.md/lib/jquery.flowchart.min.js" type="text/javascript"></script>'
				.'<script src="dzz/corpus/scripts/editor.md/editormd.js" type="text/javascript"></script>'
				.'<script src="dzz/corpus/scripts/editor.md/lib/katex/katex.min.js" type="text/javascript"></script>'
				.'<script type="text/javascript" >'
				.'var testEditormdView2 = editormd.markdownToHTML("document_body", {'
				.'            htmlDecode      : "style,script,iframe",'
				.'            emoji           : true,'
				.'            taskList        : true,'
				.'            tex             : true,'
				.'            flowChart       : true,'
				.'            sequenceDiagram : true,'
				.'        });'
				.'</script>';

		$output_template['code_0']='<link href="dzz/system/ueditor/third-party/SyntaxHighlighter/shCoreDefault.css"  rel="stylesheet"  media="all">'
				.'<div class="document-container  clearfix">'
				.'	<div id="topic_container" class="topic-container" style="padding-top:0">'
				.'		 <h1 class="document-subject text-center chapter">{fname}</h1>'
				.'		<div id="document_body" class="document-body clearfix">{content}</div>'
				.'   </div>'
				.'</div>'
				.'<script src="dzz/system/ueditor/ueditor.parse.js" type="text/javascript"></script>'
				.'<script src="dzz/system/ueditor/third-party/SyntaxHighlighter/shCore.js" type="text/javascript" type="text/javascript"></script>'
				.'<script type="text/javascript" >'
				." jQuery('.document-body .dzz-attach-icon').css({'max-width':'1em','max-height':'1em'});"
				." uParse('.document-body',{'rootPath':'dzz/system/ueditor'});"
				//." dzzattach.init('.document-body');"
				.'</script>';

		$output_template['footer']='</body></html>';
		return $output_template;
	}

}

?>
