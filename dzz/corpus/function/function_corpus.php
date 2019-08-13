<?php
/* @authorcode  codestrings
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
require_once DZZ_ROOT.'./dzz/class/class_encode.php';
require_once libfile('function/organization');
function getAidsByMessage($message){
	$paths=array();
	//处理图片
	if(preg_match_all("/mod=io(&|&amp;)op=thumbnail(&|&amp;)width=\d+(&|&amp;)height=\d+(&|&amp;)original=\d(&|&amp;)path=(.+?)(\"|\))/i",$message,$matches)){
		$paths=$matches[6];
	}
	
	//处理音视频
	if(preg_match_all("/<(video|audio).*?>[\s\S]*?<source.*?src=\"(.+?)\".*?>[\s\S]*?<\/(audio|video)>/i",$message,$matches)){
		foreach($matches[2] as $key => $value){
			if(preg_match("/path=(.+?)(&|&amp;)n=/i",$value,$matchs)){
				$paths[]=$matchs[1];
			}
		}
	}
	$aids=array();
	foreach($paths as $value){
		if($path=dzzdecode(trim($value))){
			if(strpos($path,'attach::')!==false){
				$aids[]=intval(str_replace('attach::','',$path));
			}
		}
	}
	if(preg_match_all("/".rawurlencode('attach::')."(\d+)/i",$message,$matches)){
		$aids=$matches[1];
	}
	return array_unique($aids);
}

function checkDownPerm($cid,$uid){//判断用户是否有下载权限
	global $_G;
	$corpus=C::t('corpus')->fetch_by_cid($cid,$uid);
	if(getAdminPerm()) return 1;
	if($corpus['modreasons']) return 0;
	if($corpus['perm']<1 && $corpus['viewperm']<1){ //私有的文件只有成员才能查看
		return 0;
	}
	if($corpus['orgid']>0 && $corpus['viewperm']==1 && $corpus['perm']<1 && !C::t('corpus_organization_user')->fetch_perm_by_uid($uid,$corpus['orgid'])){ //组织内可见组织成员和书成员才能查看
		return 0;
	}
	
	if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
	if(($top_allowdown=$_G['cache']['corpus:setting']['allowdown'])){
		if($corpus['allowdown']>0) $corpus['allowdown']=$top_allowdown & $corpus['allowdown'];
		else $corpus['allowdown']=$top_allowdown;
	}
	if(($top_allowdown_m=$_G['cache']['corpus:setting']['allowdown_m'])){
		if($corpus['allowdown_m']>0) $corpus['allowdown_m']=$top_allowdown_m & $corpus['allowdown_m'];
		else $corpus['allowdown_m']=$top_allowdown_m;
	}
	if($corpus['allowdown']==1) return 0;
	if($corpus['allowdown_m']==1) return 0;
	elseif($corpus['allowdown_m']==0) return 1;
	
	//游客时
	if($uid<1){//是游客时
		if($corpus['allowdown_m'] & 128) return 1;
	//文集成员
	}elseif($perm=DB::result_first("select `perm` from %t where cid=%d and uid=%d",array('corpus_user',$cid,$uid))){//是成员
		if($perm==1){
			if($corpus['allowdown_m'] & 8) return 1;
		}elseif($perm==2){
			if($corpus['allowdown_m'] & 4) return 1;
		}elseif($perm==3){
			if($corpus['allowdown_m'] & 2) return 1;
		}
	//组织成员
	}elseif($corpus['orgid'] && ($operm=DB::result_first("select `perm` from %t where orgid=%d and uid=%d",array('corpus_organization_user',$orgid,$uid)))){
		if($operm==1){
			if($corpus['allowdown_m'] & 64) return 1;
		}elseif($operm==2){
			if($corpus['allowdown_m'] & 32) return 1;
		}elseif($operm==3){
			if($corpus['allowdown_m'] & 16) return 1;
		}
	}else{
		if($corpus['allowdown_m'] & 128) return 1;
	}
	return 0;
}
function getAdminPerm(){//检查用户是否是应用管理员
	global $_G;
	if($_G['uid']<1) return 0;
	if($_G['adminid']==1) return 3;
	if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
	$muids=$_G['cache']['corpus:setting']['moderators']?explode(',',$_G['cache']['corpus:setting']['moderators']):array();
	if(!$muids) return 0;
	//转换为数组
	$orgids=array();
	$uids=array();
	foreach($muids as $value){
		if(strpos($value,'uid_')!==false){
			$uids[]=str_replace('uid_','',$value);
		}else{
			$orgids[]=$value;
		}
	}
	if(in_array($_G['uid'],$uids)) return 1;
	
	//当未加入机构和部门在部门列表中时，单独判断;
	if(in_array('other',$orgids) && !DB::result_first("SELECT COUNT(*) from %t where uid=%d and orgid>0",array('organization_user',$_G['uid']))){ 
		 return 1;		
	}
	//获取用户所在的机构或部门
	$uorgids=C::t('organization_user')->fetch_orgids_by_uid($_G['uid']);
	
	if(array_intersect($uorgids,$orgids)) return 1;
	
	//检查每个部门的上级

	foreach($uorgids as $orgid){
		$upids= C::t('organization')->fetch_parent_by_orgid($orgid,true);
		if($upids && array_intersect($upids,$orgids)) return 1;
	}
	return 0;
}
function getCreatePerm($uid,$flag='corpus'){
	global $_G;
	if(!$_G['cache']['corpus:setting']) loadcache('corpus:setting');
	$setting=$_G['cache']['corpus:setting'];
	
	$new=0;
	if($_G['adminid']==1){
		$new=1;
		$max=0;//无限制
	}else{
		if($flag=='corpus'){

			$max=$setting['maxcorpus'];
			$sum=DB::result_first("select COUNT(*) from %t where uid=%d ",array('corpus',$uid));
			if($max && $max<$sum){
				$new=0;
			}else{
				$new=1;
			}
		}else{
			$sum=DB::result_first("select COUNT(*) from %t where uid=%d ",array('corpus_organization',$uid));
			$max=$setting['maxorganization'];
			if($setting['neworganization']>0){
				$new=getAdminPerm($setting['moderators']);
			}elseif($_G['uid']>0){
				$new=1;
			}
			if($max && $max<$sum){
				$new=0;
			}
		}
	}
	return array('new'=>$new,'max'=>$max,'sum'=>$sum);
	
}
function import_by_txt($opath,$cid=0,$orgid=0,$disp=-1,$uid,$username,$filename=''){//cid=0时 创建整书,//$cid>0 时，$orgid为pfid
    $aid=intval(str_replace('attach::','',$opath));
	if(!$attach=C::t('attachment')->fetch($aid)){
		return array('error'=>'没有找到上传的文件');
	}
	if($filename) $attach['filename']=$filename;
	if(!$cid){
		if($orgid){
			if($org=C::t('corpus_organization')->fetch($orgid)){
				 if($org['mperm_c'] & 1){
					$perm=0;
				}elseif($org['mperm_c'] & 2){
					$perm=1;
				}elseif($org['mperm_c'] & 4){
					$perm=2;
				}else{
					$perm=0;
				}
			}else{
				$orgid=0;
				$perm=0;
			}
		}
		 
		//创建书本
		$setarr=array(
					  'name'=>preg_replace("/\.txt$/i",'',getstr($attach['filename'],255)),
					  'orgid'=>intval($orgid),
					  'perm'=>$perm
					 );
		
			$setarr['dateline']=TIMESTAMP;
			$setarr['uid']=$uid?$uid:getglobal('uid');
			$setarr['username']=$username?$username:getglobal('username');
			if($cid=C::t('corpus')->insert_by_cid($setarr)){
				$corpus=$setarr;
				$corpus['cid']=$cid;
			}
	}else{
		$corpus['cid']=$cid;
		$pfid=$orgid;
	}
	//获取对应表名称
	$table=C::t('corpus_class')->getTableByCid($cid);
	//逐行读取文件;
	

	//检测文件的编码，由于文件可能很大，只读取前8K来获取编码
	$ofile=IO::getStream($opath);
	$content=file_get_contents($ofile , NULL, NULL, 0, 8192);
	$p=new Encode_Core();
	$charset=$p->get_encoding($content);
	if($charset=='UTF-16LE'){
		//@ini_set("memory_limit","512M");
		$content=file_get_contents($ofile);
		$ofile=getglobal('setting/attachdir').'./cache/'.random(6).'.txt';
		$content=diconv(($content),$charset, CHARSET);
		$charset='UTF-8';
		@file_put_contents($ofile,$content);
	}
	unset($content);
	//按行读取文件;
	$i=0;
	$handle = @fopen($ofile, "r");
	if ($handle) {
		$data='';
		$fname='前言';
		$chapter_sum=0;
		$split=0;
		$split_fids=array();
		while (($line = fgets($handle)) !== false) {
			$line=trim(($line));
			if(empty($line)) continue;
			if(!$charset){
				$charset=$p->get_encoding($line);
			}
			if($charset) $line=diconv($line,$charset, CHARSET); 
			$line=str_replace(array('&nbsp;','　'),' ',$line);
			$line=preg_replace("/^\s+/i",'',$line);
			if(isChapter($line)){
				if(!empty($data) && strlen($data)>500){
					$chapter_sum++;
					
					$setarr=array('fname'=>trim(str_replace('　','',$fname)).($split?($split+1):''),
								  'type'=>'file',
								  'cid'=>$cid,
								  'pfid'=>$pfid,
								  'uid'=>$uid?$uid:getglobal('uid'),
								  'username'=>$username?$username:getglobal('username'),
								  'disp'=>$disp>-1?$disp:($disp=DB::result_first("select COUNT(*) from %t where cid=%d and pfid=%d",array($table,$cid,$pfid))),
								  'dateline'=>TIMESTAMP
								  );
					  
					if($fid=C::t('corpus_class')->insert($setarr,0)){
						$disp++;
						$split=0;
						if(setClassContent($data,$fid,$uid,$username)) $data='';
					}
				}
				$fname=trim($line);
				
			}else{
				if(strlen($data)>=65535){
					$split++;
					
					$setarr=array('fname'=>trim(str_replace('　','',$fname)).$split,
								  'type'=>'file',
								  'cid'=>$cid,
								  'pfid'=>$pfid,
								   'uid'=>$uid?$uid:getglobal('uid'),
								  'username'=>$username?$username:getglobal('username'),
								  'disp'=>$disp>-1?$disp:($disp=DB::result_first("select COUNT(*) from %t where cid=%d and pfid=%d",array($table,$cid,$pfid))),
								  'dateline'=>TIMESTAMP
								  );
					  
					if($split_fid=C::t('corpus_class')->insert($setarr,0)){
						$split_fids[]=$split_fid;
						$disp++;
						setClassContent($data,$split_fid,$uid,$username);
						$data='';
					}
				}
				$data.='<p>'.trim($line).'</p>';
			
			}
		}
		if(!empty($data)){
			$setarr=array('fname'=>trim(str_replace('　','',$fname)).($split?($split+1):''),
						  'type'=>'file',
						  'cid'=>$cid,
						  'pfid'=>$pfid,
						  'uid'=>$uid?$uid:getglobal('uid'),
						  'username'=>$username?$username:getglobal('username'),
						  'disp'=>$disp>-1?$disp:($disp=DB::result_first("select COUNT(*) from %t where cid=%d and pfid=%d",array($table,$cid,$pfid))),
						  'dateline'=>TIMESTAMP
						  );
	
			if($fid=C::t('corpus_class')->insert($setarr,0)){
				$disp++;
				$split_fids[]=$fid;
				setClassContent($data,$fid,$uid,$username);
				$data='';
			}
		}
		fclose($handle);
		/*if(is_file($ofile)){
			@unlink($ofile);
		}*/
		if($chapter_sum<2 && $fid>0){
			$fname=preg_replace("/\.txt$/i",'',getstr($attach['filename'],255));
			C::t('corpus_class')->update($fid,array('fname'=>$fname.($split?$split:'')));
			if(count($split_fids)>1){
				foreach($split_fids as $key=> $value){
					C::t('corpus_class')->update($value,array('fname'=>$fname.($key+1)));
				}	
			}
			
			if(!$split){
				$corpus['id']=$fid;
				$corpus['text']=preg_replace("/\.txt$/i",'',getstr($attach['filename'],255));
				$corpus['type']='file';
			}
		}
	}
	return $corpus;
}

function isChapter($line){
	static $creg='';
	if(strlen(str_replace(' ','',$line))>100) return false;
	
	$regxs=array(
	 			
				"/^第\s*(\d|一|二|三|四|五|六|七|八|九|十|零|壹|贰|叁|肆|伍|陆|柒|捌|玖|拾|仟|千|百|佰|０|１|２|３|４|５|６|７|８|９)+\s*(章|篇|回|节|卷|部)\s*$/i", //第一章 // 002章
				"/^第\s*(\d|一|二|三|四|五|六|七|八|九|十|零|壹|贰|叁|肆|伍|陆|柒|捌|玖|拾|仟|千|百|佰|０|１|２|３|４|５|６|７|８|９)+\s*(章|篇|回|节|卷|部)\s+/i", //第一章 开天辟地 //002章 开天辟地
				"/^(第)*\s*(\d|一|二|三|四|五|六|七|八|九|十|零|壹|贰|叁|肆|伍|陆|柒|捌|玖|拾|仟|千|百|佰|０|１|２|３|４|５|６|７|８|９)+\s*(章|篇|回|节|卷|部)\s*[\.:：、]/i", //第一章:开天辟地 //002章:开天辟地
				"/^第\s*(\d|一|二|三|四|五|六|七|八|九|十|零|壹|贰|叁|肆|伍|陆|柒|捌|玖|拾|仟|千|百|佰|０|１|２|３|４|５|６|７|８|９)+\s*(章|篇|回|节|卷|部|\.|:|：|、)/i", //一章开天辟地 //002章开天辟地
				
				"/^\s*chapter\s*(\d|I|II|III|IV|V|VI|VII|VIII|IX|X)+/i",
				"/^☆+[\.、]/i",
				"/第\s*(\d|一|二|三|四|五|六|七|八|九|十|零|壹|贰|叁|肆|伍|陆|柒|捌|玖|拾|仟|千|百|佰|０|１|２|３|４|５|６|７|８|９|☆)+\s*(章|篇|回|节|卷|部)[\s、:：]/i", //ssss 第一章 开天辟地 //ssss 002章 开天辟地
				//"/第\s*(\d|一|二|三|四|五|六|七|八|九|十|零|壹|贰|叁|肆|伍|陆|柒|捌|玖|拾|仟|千|百|佰|０|１|２|３|４|５|６|７|８|９|☆)+\s*(章|篇|回|节|卷|部)\s*/i", //ssss第一章 开天辟地 //ssss002章 开天辟地
				
				//"/^\s*《.+?》\s*$/",  //《》包裹的段落
				//"/^\s*(\(|（){0,1}(\d|一|二|三|四|五|六|七|八|九|十|零|壹|贰|叁|肆|伍|陆|柒|捌|玖|拾|仟|千|百|佰|０|１|２|３|４|５|６|７|８|９)+(\)|）){0,1}\s*$/i"   //纯数字的行
	);
	
	if($creg && $creg<6 && preg_match($regxs[$creg],$line)){
		return true;
	}
	foreach($regxs as $key => $reg){
		if(preg_match($reg,$line)){
			$creg=$key;
			return true;
		}
	}
	return false;
}
function import_by_epub($opath,$cid=0,$orgid=0,$disp=-1,$uid,$username){//cid=0时 创建整书
	if(!extension_loaded('tidy')){
		return array('error'=>'缺少php tidy扩展，请安装后重试');
	}
	$epub=getglobal('setting/attachdir').'./cache/'.md5($opath).'.epub';
	if(!downfile($opath,$epub)){
		return array('error'=>'待导入文件不存在');
	}
	
	@set_time_limit(0);
	
	if(!is_file($epub)) return false;
	$tmpfolder=getglobal('setting/attachdir').'./cache/epub_'.md5($epub);
	require_once DZZ_ROOT.'./dzz/corpus/class/pclzip.lib.php';  
	$archive = new PclZip($epub);  
	
	if ($archive->extract(PCLZIP_OPT_PATH, $tmpfolder) == 0) {  
		//return false;
		@unlink($epub);
		return array('error'=>"Error : ".$archive->errorInfo(true));
		die("Error : ".$archive->errorInfo(true));  
	}
	@unlink($epub);
	//判断是否加密
	if(is_file($tmpfolder.'/META-INF/encryption.xml')){
		return array('error'=>'不支持导入DRM保护的电子书');
	}
	if(!$content=findEPub($tmpfolder,'ncx')){
		return false;
	}
	
	//读取目录中的xml文档
	
	if(!$xml = simplexml_load_file($content)){
		return array('error'=>'解析目录失败');
	}
	
	$xml=(array)$xml;
	if(($opf=findEPub($tmpfolder,'opf')) && ($contentopf=file_get_contents($opf))){
		    $parser = xml_parser_create();                        //创建解析器
			xml_parse_into_struct($parser, $contentopf, $opfarr, $opfindex);    //解析到数组
			xml_parser_free($parser);
	}else{
		return array('error'=>'解析opf失败');
	}
	
	
	$manifest=array();
	$css=array();
	foreach($opfindex['ITEM'] as $val){
		$manifest[$opfarr[$val]['attributes']['ID']]=$opfarr[$val]['attributes'];
		if($opfarr[$val]['attributes']['MEDIA-TYPE']=='text/css'){
			$css[]=$opfarr[$val]['attributes']['HREF'];
		}
	}
	$css=array_unique($css);
	$items=array();
	foreach($opfindex['ITEMREF'] as $val){
		$idref=$opfarr[$val]['attributes']['IDREF'];
		if($idref=='coverpage') continue;
		if($idref=='titlepage') continue;
		$items[]=$manifest[$idref]['HREF'];
	}
	
	//print_r($manifest);
	
	$tmpfolder1=dirname($opf);
	if($css) $cssattach=saveCss($css,$tmpfolder1);
	foreach($items  as $val){
		$data.='<p filesrc="'.$val.'"></p>'._parseHTML($val,$tmpfolder1);
	}
	if(empty($data)){
		return array('error'=>'没有获取到书本内容');
	}
	//处理css
	
	
	$class=array();
	$i=0;
	foreach($xml['navMap'] as $value){
		$name=(array)$value->navLabel;
		
		$class[$i]['fname']=$name['text'];
		$src=(array)$value->content->attributes()->src;
		$class[$i]['src']=$src[0];
		
		if($value->navPoint){	
			$value=(array)$value;
			$class[$i]['sub']=_parseNav($value['navPoint']);
		}
		$i++;
	}
	
	
	if(!$cid){
		if($orgid){
			if($org=C::t('corpus_organization')->fetch($orgid)){
				 if($org['mperm_c'] & 1){
					$perm=0;
				}elseif($org['mperm_c'] & 2){
					$perm=1;
				}elseif($org['mperm_c'] & 4){
					$perm=2;
				}else{
					$perm=0;
				}
			}else{
				$orgid=0;
				$perm=0;
			}
		}
		//获取书名
		$book=array();
		$name=(array)$xml['docTitle'];
		if($name['text']) $book['name']=$name['text'];
		else return array('error'=>'没有获取到书名');
		$metadata=$meta=array();
		$cover=NULL;
		//获取封面和meta信息
		
		foreach($opfarr as $value){
			switch(strtoupper($value['tag'])){
				case 'DC:DESCRIPTION':
					if($value['value']) $meta['description']=$metadate['--comments']=$value['value'];
					break;
				case 'DC:CREATOR':
					if($value['value']) $meta['authors']=$metadata['--authors']=$value['value'];
					break;
					
				case 'DC:PUBLISHER':
					if($value['value']) $metadata['--publisher']=$value['value'];
					break;
				/*case 'DC:RIGHTS'://版权声明
					if($value['value']) $metadata['--rights']=$value['value'];
					break;*/
				case 'DC:TITLE':
					if($value['value']) $metadata['--title']=$value['value'];
					break;
						
				case 'DC:DATE': //日期
					if($value['value']) $metadata['--pubdate']=strtotime($value['value']);
					break;
				case 'DC:LANGUAGE': //日期
					if($value['value']) $metadata['--language']=($value['value']);
					break;
				case 'DC:IDENTIFIER': //ISBN
					$attr=$value['attributes'];
					foreach($attr as $val){
						if(strtoupper($val)=='ISBN'){
							if($value['value']){
								 $metadata['--isbn']=($value['value']);
								$isbn=str_replace('-','',$value['value']);
								if(preg_match("/(^\d{13})|(^\d{10})/",$isbn)){
									$meta['print_book_isbn']=$isbn;
								}
							}
							break;
						}
					}
					break;
				case 'ITEM':
					$attr=$value['attributes'];
					if((strpos($attr['ID'],'cover')!==false) && (strpos($attr['MEDIA-TYPE'],'image/')!==false)){
						$cover=$attr['HREF'];
					}
					break;
				case 'ITEMREF':
					
					break;			
			}
			
			
		}
		if($cover){
			$tmpfolder1=dirname($opf);
			$pathinfo=pathinfo($cover);
			$attach=_savetoattachment($tmpfolder1.'/'.$cover,$pathinfo['filename'].'.'.$pathinfo['extension']);
		}
		//
		//创建书本
		$setarr=array(
					  'name'=>$metadata['--title']?$metadata['--title']:str_replace('...','',getstr($book['name'],255)),
					  'orgid'=>intval($orgid),
					  'aid'=>intval($attach['aid']),
					  'perm'=>$perm,
					  'extra'=>$metadata?serialize($metadata):'',
					  'isbn'=>$metadata['--isbn']?$metadata['--isbn']:'',
					  'css'=>intval($cssattach['aid'])
					  );
		
			$setarr['dateline']=TIMESTAMP;
			$setarr['uid']=$uid?$uid:getglobal('uid');
			$setarr['username']=$username?$username:getglobal('username');
			if($cid=C::t('corpus')->insert_by_cid($setarr)){
				$corpus=$setarr;
				$corpus['cid']=$cid;
				if($setarr['aid']) $corpus['path']=dzzencode('attach::'.$setarr['aid']);
				//if($meta) C::t('corpus_meta')->insert_by_cid($cid,$meta);
			}
	}else{
		$corpus['cid']=$cid;
		$pfid=$orgid;
	}
	
	//添加目录
	if($class){
		$src_fid=_import_nav($class,intval($pfid),$cid,$data,0,$disp,$uid,$username);
		
		$replace=array();
		foreach($src_fid as $key =>$value){
			if(empty($key)) continue;
			$arr=explode('/',$key);
			$pop=array_pop($arr);
			$search[]='/\".*?'.str_replace(array('/','.'),array("\/",'\.'),$pop).'\"/i';
			$pop=str_replace("/#.+?$/i",'',$pop);
			$search1[]='/\".*?'.str_replace(array('/','.'),array("\/",'\.'),$pop).'#.+?\"/i';
			$replace[]='"'.DZZSCRIPT.'?mod=corpus&op=list&cid='.$cid.'&fid='.$value.'"';
		}		
	}
	$reg=array();
	foreach($search as $key => $val){
		$reg['search'][]=$val;
		$reg['search1'][]=$search1[$key];
		$reg['replace'][]=$replace[$key];
	}
	
	
	//过滤链接
	if($reg) replace_href($cid,$pfid,$reg);
	_removedir($tmpfolder);
	return $corpus;
}

function replace_href($cid,$pfid,$reg){
	$table=C::t('corpus_class')->getTableByCid($cid);
	foreach(DB::fetch_all("select c.fid,c.fname,c.type,v.revid,v.content,v.remoteid from %t c LEFT JOIN %t v ON c.revid=v.revid where cid=%d and pfid=%d ",array($table,'corpus_reversion',$cid,$pfid)) as $value){
		if($value['remoteid']){
			$content=IO::getFileContent($value['content']);
		}else{
			$content=$value['content'];
		}
		
		
		$content1=preg_replace($reg['search'],$reg['replace'],$content);
		$content1=preg_replace($reg['search1'],$reg['replace'],$content1);
		
		$content1=preg_replace_callback("/href=\"(.+?)\"/i",function($matches){
			if(preg_match("/^(http|ftp|https|mms)\:\/\//i",$matches[1])){
				return $matches[0];
			}elseif(preg_match("/^".DZZSCRIPT."/i",$matches[1])){
				return $matches[0];
			}else{
				return 'href="javascript:;"';
			}
		},$content1);
		if($content && $content!=$content1){
			if($value['remoteid']){
				if($ret=IO::setFileContent($value['content'],$content1)){
					if($ret['error']){
						C::t('corpus_reversion')->update($value['revid'],array('content'=>$content1,'remoteid'=>0));
					}
				}
			}else{
				C::t('corpus_reversion')->update($value['revid'],array('content'=>$content1));
			}
		}
		
		if($value['type']=='folder'){
			replace_href($cid,$value['fid'],$reg);
		}
	}
}
function _import_nav($class,$pfid,$cid,&$data,$issub=0,$disp,$uid,$username){
	static $srcs=array();
	static $prev=array('fname'=>'前言','src'=>'');
	static $prevfid=NULL;
	static $lpfid=NULL;
	static $rpfid=NULL;
	static $src_fid=array();
	$table=C::t('corpus_class')->getTableByCid($cid);
	if($disp<0) $disp=DB::result_first("select COUNT(*) from %t where cid=%d and pfid=%d",array($table,$cid,$pfid));
	if(!isset($prev['disp'])) $prev['disp']=$disp;
	if(!isset($prev['pfid'])) $prev['pfid']=$pfid;
	foreach($class as $key => $nav){
		$code=0;
		if($prev['src']==$nav['src'] && $nav['fname']==$prev['fname']) continue;
			$sarr=explode('#',$nav['src']);
			$src=$nav['src'];
			if(in_array($src,$srcs)){
				//continue;
			}else{
				$srcs[]=$src;
			}
			
			if($prev['src'] && preg_match("/<b\s*>".addslashes($nav['fname'])."<\/b>/i",$data)){
				$arr=preg_split("/<b\s*>".addslashes($nav['fname'])."<\/b>/i",$data);
				if(count($arr)>1) {
					$message=trim($arr[0]).'</p>';
					unset($arr[0]);
					$data='<p>'.implode('',$arr);
				}else{
					$message='';
				}
			}else{
				
				if($sarr[1] && preg_match("/id=\"".str_replace('/','\/',$sarr[1])."\".*?>/i",$data)){//如果是带#结尾的
					$arr=preg_split("/id=\"".str_replace('/','\/',$sarr[1])."\".*?>/i",$data);
					if(count($arr)>1) {
						$message=trim($arr[0]);
						unset($arr[0]);
						$data='<p>'.implode('',$arr);
					}else{
						$message='';
					}
				
				}else{
					
					$arr=preg_split("/<p filesrc=\"".str_replace('/','\/',$sarr[0])."\"><\/p>/i",$data);
					if(count($arr)>1) {
						$message=trim($arr[0]);
						unset($arr[0]);
						$data=implode('',$arr);
					}else{
						$message='';
					}
				}
			}
			if($message){
				$config = array(
				   'show-body-only'=>true,
				   'wrap'=>0,
				   'new-inline-tags'=>'video,audio,source'
				  );
				
				$tidy = new tidy;
				$message=$tidy->repairString($message, $config, 'utf8');
				
			}
			
			if($prev['sub']){
				/*$message=preg_replace("/^\s*<h\d.*?>\s*".addslashes($prev['fname'])."\s*<\/h\d>/i",'',$message);*/
				if(preg_match("/^(<[\s\S]+?>[\s\S])*(<.*?>[\s\S]+?<\/.+?>)/i",$message,$matches)){
						if(trim(str_replace(array(' ','　'),'',preg_replace("/[\r\n]/",'',strip_tags($matches[2]))))==trim(str_replace(array(' ','　'),'',$prev['fname']))){
							$message=str_replace($matches[2],'',$message);
						}
					}
				setClassContent($message,$prev['pfid'],$uid,$username,$code);
			}else{
				if(trim($message)){
					$split=0;
					$splitdata='';
					/*$message=preg_replace("/^\s*<h\d.*?>\s*".addslashes($prev['fname'])."\s*<\/h\d>/i",'',$message);*/
					if(preg_match("/^(<[\s\S]+?>[\s\S])*(<.*?>[\s\S]+?<\/.+?>)/i",$message,$matches)){
						if(trim(str_replace(array(' ','　'),'',preg_replace("/[\r\n]/",'',strip_tags($matches[2]))))==trim(str_replace(array(' ','　'),'',$prev['fname']))){
							$message=str_replace($matches[2],'',$message);
						}
					}
					if(preg_match("/<p filesrc=\".+?\"><\/p>/i",$message)){
						$splitarr=preg_split("/<p filesrc=\".+?\"><\/p>/i",$message);
					}else{
						$splitarr=array($message);
					}
					
					foreach($splitarr as $value){
						  $splitdata.=trim($value);
						  if(strlen($splitdata)>260*1024){
						  	 $setarr=array('fname'=>$prev['fname'].($split>0?$split:''),
									  'type'=>'file',
									  'cid'=>$cid,
									  'pfid'=>$prev['pfid']?$prev['pfid']:$pfid,
									  'uid'=>$uid?$uid:getglobal('uid'),
									  'username'=>$username?$username:getglobal('username'),
									  'disp'=>intval($prev['disp']),
									  'dateline'=>TIMESTAMP
									);
							if($fid=C::t('corpus_class')->insert($setarr,0)){
								$prev['disp']++;
								$split++;
								if($split==1) $src_fid[$prev['src']]=$fid;
								setClassContent($splitdata,$fid,$uid,$username,$code);
								$splitdata='';
							}
						}
					}
					if($splitdata){
						$setarr=array('fname'=>$prev['fname'].($split>0?$split:''),
									  'type'=>$prev['sub']?'folder':'file',
									  'cid'=>$cid,
									  'pfid'=>$prev['pfid']?$prev['pfid']:$pfid,
									  'uid'=>$uid?$uid:getglobal('uid'),
									   'username'=>$username?$username:getglobal('username'),
									   'disp'=>intval($prev['disp']),
									  'dateline'=>TIMESTAMP
								);
						if($fid=C::t('corpus_class')->insert($setarr,0)){
							$prev['disp']++;
							$split++;
							if($split==1) $src_fid[$prev['src']]=$fid;
							setClassContent($splitdata,$fid,$uid,$username,$code);
						}
					}
				}elseif($prev['src']){
					$setarr=array('fname'=>$prev['fname'],
								  'type'=>$prev['sub']?'folder':'file',
								  'cid'=>$cid,
								  'pfid'=>$prev['pfid']?$prev['pfid']:$pfid,
								  'uid'=>$uid?$uid:getglobal('uid'),
								   'username'=>$username?$username:getglobal('username'),
								   'disp'=>intval($prev['disp']),
								  'dateline'=>TIMESTAMP
							);
					
					if($fid=C::t('corpus_class')->insert($setarr,0)){
						$prev['disp']++;
						$src_fid[$prev['src']]=$fid;
					}
				}
			}
			
			$prev['fname']=$nav['fname'];
			$prev['src']=$nav['src'];
			$prev['sub']=$nav['sub'];
			$prev['pfid']=$pfid;
			
			
			if($nav['sub']){
				$setarr=array('fname'=>$nav['fname'],
							  'type'=>'folder',
							  'cid'=>$cid,
							  'pfid'=>$pfid,
							  'uid'=>$uid?$uid:getglobal('uid'),
							  'username'=>$username?$username:getglobal('username'),
							  'disp'=>$prev['disp'],
							  'dateline'=>TIMESTAMP
							);
			
					if($fid=C::t('corpus_class')->insert($setarr,0)){
						$src_fid[$nav['src']]=$fid;
						$prev['disp']++;
						$prev['pfid']=$fid;
						
						_import_nav($nav['sub'],$fid,$cid,$data,1,-1,$uid,$username);
					}
			}
			
		}
	
	
	if($data && !$issub){
		$message=$data;
		if($prev['nav']){
			setClassContent($message,$prev['pfid']?$prev['pfid']:$pfid,$uid,$username,$code);
		}else{
			if($message){
				$split=0;
				$splitdata='';
				foreach(preg_split("/<p filesrc=\".+?\"><\/p>/i",$message) as  $message){
					  $splitdata.=trim($message);
					  if(strlen($splitdata)>260*1024){
						   $setarr=array('fname'=>$prev['fname'].($split>0?$split:''),
									  'type'=>'file',
									  'cid'=>$cid,
									  'pfid'=>$prev['pfid']?$prev['pfid']:$pfid,
									   'uid'=>$uid?$uid:getglobal('uid'),
									  'username'=>$username?$username:getglobal('username'),
									  'disp'=>intval($prev['disp']),
									  'dateline'=>TIMESTAMP
									);
						
							if($fid=C::t('corpus_class')->insert($setarr,0)){
								$prev['disp']++;
								$split++;
								if($split==1) $src_fid[$prev['src']]=$fid;
								setClassContent($splitdata,$fid,$uid,$username,$code);
								$splitdata='';
							}
						}
					}
				if($splitdata){
						$setarr=array('fname'=>$prev['fname'].($split>0?$split:''),
								  'type'=>$prev['sub']?'folder':'file',
								  'cid'=>$cid,
								   'pfid'=>$prev['pfid']?$prev['pfid']:$pfid,
								   'uid'=>$uid?$uid:getglobal('uid'),
									'username'=>$username?$username:getglobal('username'),
									'disp'=>intval($prev['disp']),
								  'dateline'=>TIMESTAMP
								);
					
						if($fid=C::t('corpus_class')->insert($setarr,0)){
							$prev['disp']++;
							$split++;
							if($split==1) $src_fid[$prev['src']]=$fid;
							setClassContent($splitdata,$fid,$uid,$username,$code);
						}
				}
			}
		}
	}
	return $src_fid;
	
}
function setClassContent($message,$fid,$uid,$username,$code=0){
				
	$message=preg_replace("/<p filesrc=\".+?\"><\/p>/i",'',$message);
	//去除标题和class
	$html = preg_replace(array(
							   "/<h\d\s+id=\"title\".*?>[.\s\S]+?<\/h\d>/i",
							    "/^[\s\S]*<h\d.*?>[.\s\S]+?<\/h\d>/i",
								"/<ul\s+.*?><li\s+class=\"file\".+?$/i",
							   //"/class=\".+?\"/i"
						       )
						  ,''
						  ,$message
						);
	//去除段落前面的空格
	$message = preg_replace_callback( "/<p(.*?)>(\s|&nbsp;|　)+/i",function($matches){
		return '<p'.$matches[1].'>';
	},$message);
	$p=new Encode_Core();
	$charset=$p->get_encoding($message);
	if($charset) $message=diconv($message,$charset, CHARSET); 
	
	//获取文档内附件
	$attachs=getAidsByMessage($message);
	$setarr=array('fid'=>$fid,
				  'uid'=>$uid?$uid:getglobal('uid'),
				  'username'=>$username?$username:getglobal('username'),
				  'code'=>$code,
				  'attachs'=>$attachs,
				  'content'=>$message
				);
	
	if($arr=C::t('corpus_reversion')->insert($setarr)){
		if(C::t('corpus_class')->update($fid,array('revid'=>$arr['revid'],'version'=>$arr['version']))){
			//暂时取消全文搜索功能（导入的文档
			/*$fulltext=new fulltext_core();
			$fullcontent=$fulltext->normalizeText(strip_tags($message));
			C::t('corpus_fullcontent')->insert(array('fid'=>$fid,'fullcontent'=>$fullcontent),0,1);*/
		}
		return true;
	}
	return false;
}
function _parseHTML($src,$tmpfolder){
	global $_G;
	
	static $i=0;
	$filename=rawurldecode($tmpfolder.'/'.$src);
	
	$_G['parseHTML_tmpfolder']=dirname($filename);
	if(!is_file($filename)){
		return '';
	}
	$tmpfolder=dirname($filename);
	$html=file_get_contents($filename);
	$config = array(
          // 'output-html'   => true,
		   'show-body-only'=>true,
		   'wrap'=>0,
		   'new-inline-tags'=>'video,audio,source'
          );

	// Tidy
	$tidy = new tidy;
	$html=$tidy->repairString($html, $config, 'utf8');
	/*if(preg_match("/<body.*?>([.\s\S]+)?<\/body>/i",$html,$matches)){
		$html=$matches[1];
	}else{
		$html=preg_replace(array("/<html.*?>/i","/<body.*?>/i","/<head>[.\s\S]+?<\/head>/i",'/<\/html>/i','/<\/body>/i'),'',$html);
	}*/
	
	
	
	//检测所有图片，转换为附件

	$replace=array();
	$search=array();
	//去除所有换行
	/*$html=preg_replace_callback("/<pre.+?>[.\s\S]+?<\/pre>/i",function($matches){
		return preg_replace("/\n/i",'__pre__',$matches[0]);
	},$html);*/
	//$html=preg_replace("/[\r\n]/i",'',$html);
	//$html=preg_replace("/__pre__/i","\n",$html);
	//转换图片
	$html=preg_replace_callback("/<img.*?src=\"(.+?)\".*?>/i",function($matches){
		global $_G;
		$tmpfolder=$_G['parseHTML_tmpfolder'];
		$matches[1]=rawurldecode($matches[1]);
		$filename=strrpos($matches[1],'/')?substr($matches[1],strrpos($matches[1],'/')+1):$matches[1];
		if($attach=_savetoattachment($tmpfolder.'/'.$matches[1],$filename) ){
			return '<img class="dzz-image" src="'.$attach['url'].'&n='.urlencode($filename).'" title="'.$attach['filename'].'" alt="'.$attach['filename'].'" dsize="'.$attach['dsize'].'" aid="'.$attach['aid'].'" apath="'.$attach['apath'].'" ext="'.$attach['filetype'].'">';
		}else{
			return $matches[0];
		}
	},$html);
	//转换视频或音频封面图片
	$html=preg_replace_callback("/<[video|audio]+.*?poster=\"(.+?)\".*?>/i",function($matches){
		global $_G;
		$matches[1]=urldecode($matches[1]);
		$tmpfolder=$_G['parseHTML_tmpfolder'];
		$filename=strrpos($matches[1],'/')?substr($matches[1],strrpos($matches[1],'/')+1):$matches[1];
		if($attach=_savetoattachment($tmpfolder.'/'.$matches[1],$filename) ){
			return str_replace($matches[1],$attach['url'].'&n='.urlencode($filename),$matches[0]);
		}else{
			return $matches[0];
		}
	},$html);
	//转换视频；
	$html=preg_replace_callback("/<video.{0,}?(poster=\"(.+?)\")[\s\S]*?<source.*?src=\"(.+?)\".*?>[\s\S]*?<\/video>/i",function($matches){
		global $_G;
		$matches[3]=urldecode($matches[3]);
		$tmpfolder=$_G['parseHTML_tmpfolder'];
		$filename=strrpos($matches[3],'/')!==false?substr($matches[3],strrpos($matches[3],'/')+1):$matches[3];
		if($attach=_savetoattachment($tmpfolder.'/'.$matches[3],$filename) ){
			if($attach['filetype']=='mp3'){
				return '<audio class="edui-upload-video" width="320" height="30" controls="" poster="'.$matches[2].'" src="'.$attach['url'].'&n='.urlencode($filename).'"><source src="'.$attach['url'].'&n='.urlencode($filename).'" type="audio/mpeg"></audio>';
			}else{
				return '<video class="edui-upload-video" width="320" height="240" poster="'.$matches[2].'" controls="" src="'.$attach['url'].'&n='.$attach['filename'].'"><source src="'.$attach['url'].'&n='.urlencode($filename).'" type="video/'.$attach['filetype'].'"/></video>';
			}	
		}else{
			return $matches[0];
		}
	},$html);
	//转换音频；
	$html=preg_replace_callback("/<audio.{0,}?(poster=\"(.+?)\")*.*?>[\s\S]*?<source src=\"(.+?)\".*?>[\s\S]*?<\/audio>/i",function($matches){
		global $_G;
		$matches[3]=urldecode($matches[3]);
		$tmpfolder=$_G['parseHTML_tmpfolder'];
		$filename=strrpos($matches[2],'/')?substr($matches[3],strrpos($matches[3],'/')+1):$matches[3];
		if($attach=_savetoattachment($tmpfolder.'/'.$matches[3],$filename) ){
			return '<audio class="edui-upload-video" width="320" height="30" controls="" poster="'.$matches[2].'" src="'.$attach['url'].'&n='.urlencode($filename).'"><source src="'.$attach['url'].'&n='.urlencode($filename).'" type="audio/mpeg"></audio>';
		}else{
			return $matches[0];
		}
	},$html);
	unset($_G['parseHTML_tmpfolder']);
	return $html;
}
function _parseNav($xml){
	$class=array();
		$i=0;
	if(is_object($xml)){
		$value=$xml;
		$name=(array)$value->navLabel;
		$class[$i]['fname']=$name['text'];
		$src=(array)$value->content->attributes()->src;
		$class[$i]['src']=$src[0];
		if($value->navPoint){	
			$value=(array)$value;
			$class[$i]['sub']=_parseNav($value['navPoint']);
		}
	}else{
	
		foreach($xml as $key => $value){
			
			$name=(array)$value->navLabel;
			$class[$i]['fname']=$name['text'];
			$src=(array)$value->content->attributes()->src;
			$class[$i]['src']=$src[0];
			
			if($value->navPoint){	
				$value=(array)$value;
				$class[$i]['sub']=_parseNav($value['navPoint']);
			}
			$i++;
		}
	}
	return $class;
}
function _savetoattachment($file_path,$filename,$iscontent=false) {
	 global $_G;
	 if($iscontent){
		if(strlen($file_path)<1) return false;
        $md5=md5($file_path);
		$filesize=strlen($file_path);
	 }else{
		 if(!is_file($file_path)) return false;
        $md5=md5_file($file_path);
		$filesize=filesize($file_path);
	 }
		
		if($md5 && $attach=DB::fetch_first("select * from %t where md5=%s and filesize=%d",array('attachment',$md5,$filesize))){
			$attach['filename']=$filename;
			$pathinfo = pathinfo($filename);
			$ext = $pathinfo['extension']?$pathinfo['extension']:'';
			$attach['filetype']=$ext;
			if(in_array(strtolower($attach['filetype']),array('png','jpeg','jpg','gif','bmp'))){
				$attach['img']=C::t('attachment')->getThumbByAid($attach,512,512,0);
				$attach['url']=C::t('attachment')->getThumbByAid($attach,0,0,1);
				$attach['isimage']=1;
			}else{
				$attach['img']=geticonfromext($ext);
				$attach['isimage']=0;
				$attach['url']=DZZSCRIPT.'?mod=io&op=getStream&path='.dzzencode('attach::'.$attach['aid']);
			}
			$attach['dsize']=formatsize($attach['filesize']);
			$attach['apath']=dzzencode('attach::'.$attach['aid']);
			return $attach;
		}else{
			$target=_getAttachPath($filename);
			$pathinfo = pathinfo($filename);
			$ext = $pathinfo['extension']?$pathinfo['extension']:'';
			if($ext && in_array(strtolower($ext) ,getglobal('setting/unRunExts'))){
				$unrun=1;
			}else{
				$unrun=0;
			}
			$filepath=$_G['setting']['attachdir'].$target;
			if($iscontent){
				file_put_contents($filepath,$file_path);
			}else{
				$handle=fopen($file_path, 'r');
				$handle1=fopen($filepath,'w');
				while (!feof($handle)) {
				   fwrite($handle1,fread($handle, 8192));
				}
				fclose($handle);
				fclose($handle1);
			}
			
			$filesize=filesize($filepath);
			$remote=0;
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
					if($re['error']) $remoteid=0;
					else @unlink($filepath);
				}else{
					$remoteid=0;
				}
			}
			$remote=$remoteid;	
			
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
				// dfsockopen($_G['siteurl'].'misc.php?mod=movetospace&aid='.$attach['aid'].'&remoteid=0',0, '', '', FALSE, '',1);
				if(in_array(strtolower($attach['filetype']),array('png','jpeg','jpg','gif','bmp'))){
					$attach['img']=C::t('attachment')->getThumbByAid($attach,512,512,0);
					$attach['url']=C::t('attachment')->getThumbByAid($attach,0,0,1);
					$attach['isimage']=1;
				}else{
					$attach['img']=geticonfromext($ext);
					$attach['url']=DZZSCRIPT.'?mod=io&op=getStream&path='.dzzencode('attach::'.$attach['aid']);
					$attach['isimage']=0;
				}
				$attach['dsize']=formatsize($attach['filesize']);
				$attach['apath']=dzzencode('attach::'.$attach['aid']);
				return $attach;
			}else{
				return false;
			}
		}
    }
 function _getAttachPath($filename,$dir='dzz'){
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
 function _removedir($dirname, $keepdir = FALSE ) {
		$dirname = str_replace(array( "\n", "\r", '..'), array('', '', ''), $dirname);
		if(!is_dir($dirname)) {
			return FALSE;
		}
		$handle = opendir($dirname);
		while(($file = readdir($handle)) !== FALSE) {
			if($file != '.' && $file != '..') {
				$dir = $dirname . DIRECTORY_SEPARATOR . $file;
				$mtime=filemtime($dir);
				is_dir($dir) ? _removedir($dir) :  unlink($dir);
			}
		}
		closedir($handle);
		return !$keepdir ? (@rmdir($dirname) ? TRUE : FALSE) : TRUE;
}
function findEPub($dirname,$ext){
	static $ret='';
	    $dirname = str_replace(array( "\n", "\r", '..'), array('', '', ''), $dirname);
		if(!is_dir($dirname)) {
			return FALSE;
		}
	
		$handle = opendir($dirname);
		while(($file = readdir($handle)) !== FALSE) {
			if($file != '.' && $file != '..') {
				$dir = $dirname .'/' . $file;
				if(is_dir($dir)){
					findEPub($dir,$ext);
				}else{
					$pathinfo=pathinfo($dir);
					if($pathinfo['extension']==$ext){
					  $ret=$dir;
					}
				}
			}
		}
		closedir($handle);
		return $ret;
}
function import_by_docx($path,$cid,$pfid,$disp=-1,$uid,$username,$filename=''){
	$aid=str_replace('attach::','',$path);
	if(!$attach=C::t('attachment')->fetch($aid)){
		return array('error'=>'带转换的文件不存在');
	}
	
	$table=C::t('corpus_class')->getTableByCid($cid);
	if(empty($filename)) $filename=$attach['filename'];
	//其他类型文件
	$setarr=array(    'fname'=>substr($filename,0,strrpos($filename,'.')),
					  'type'=>'file',
					  'cid'=>$cid,
					  'pfid'=>$pfid,
					  'uid'=>$uid?$uid:getglobal('uid'),
					  'username'=>$username?$username:getglobal('username'),
					  'disp'=>$disp>-1?$disp:(DB::result_first("select COUNT(*) from %t where cid=%d and pfid=%d",array($table,$cid,$pfid))),
					  'dateline'=>TIMESTAMP
				  );
	
	if($fid=C::t('corpus_class')->insert($setarr,0)){
		
		//处理文档文件
		$filepath=getglobal('setting/attachdir').'./cache/'.md5($attach['attachment']).'.docx';
		file_put_contents($filepath,file_get_contents(IO::getStream($path)));	
		$message=convert::docxtohtml($filepath);
		if(setClassContent($message,$fid,$uid,$username,0)){
			$data=array(
						'id'=>$fid,
						'text'=>$setarr['fname'],
						'type'=>'file'
						);
			return $data;
		}else{
			C::t('corpus_class')->delete_by_fid($fid,true);
			@unlink($filepath);
			return array('error'=>'保存文档错误，请检查您数据库是否正常');
		}
	}
	return array('error'=>'导入失败');
	
}
function downfile($opath,$file=NULL) {
	$file_source=IO::getStream($opath);
	if(!$file){
		if(strpos($opath,'.')!==false) $pathinfo=pathinfo($opath);
		else $pathinfo=pathinfo(preg_replace("/\?.+$/i",'',$file_source));
		$file=DZZ_ROOT.'./data/attachment/cache/'.md5($opath.random(4)).'.'.$pathinfo['extension'];
	}
	$rh = fopen($file_source, 'rb');
	$wh = fopen($file, 'wb');
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
	return $file;
}
function saveContent($content,$cid,$fid,$revid) {//保存文档内容到附件系统
    if(empty($content)) return 0;  
	$target='book/'.$cid.'/'.$fid.'_'.$revid.'.dzzdoc';
	$remoteid=C::t('local_storage')->getRemoteId(); //未指定时根据路由获取；
	$bz=io_remote::getBzByRemoteid($remoteid);
	if($bz=='dzz'){
		$path='dzz::'.$target;
	}else{
		$path=$bz.'/'.$target;
	}
	if($ret=IO::setFileContent($path,$content)){
		if($ret['error']) return 0;
		return array('path'=>$path,'remoteid'=>$remoteid);
	}
	return 0;
}
function evernote_replace_by_attach($message,$attachs){
	if(!$attachs) return $message;
	$message=str_replace("<html><body>",'',$message);
	$message=str_replace("</body></html>",'',$message);
	$message=preg_replace_callback("/<img class=\"attach_img\" src=\"(.+?)\">/i",function($matches){
		global $attachs;
		if($attach=$attachs[$matches[1]]){
			return '<img class="dzz-image" src="'.$attach['url'].'" title="'.$attach['filename'].'" alt="'.$attach['filename'].'" dsize="'.$attach['dsize'].'" aid="'.$attach['aid'].'" apath="'.$attach['apath'].'" ext="'.$attach['filetype'].'">';
			
		}else{
			return '';
		}
	},$message);
	$message=preg_replace_callback("/<attach class=\"dzz_attach\" hash=\"(.+?)\"><\/attach>/i",function($matches){
		global $attachs;
		if($attach=$attachs[$matches[1]]){
			return '<span class="dzz-attach"><img class="dzz-attach-icon" src="'.$attach['img'].'" style="max-width: 1em; max-height: 1em;"><a class="dzz-attach-title" href="'.$attach['url'].'" title="'.$attach['filename'].'" aid="'.$attach['aid'].'" dsize="'.$attach['dsize'].'" apath="'.$attach['apath'].'" ext="'.$attach['filetype'].'" target="_blank" >'.$attach['filename'].'</a></span>';
		}else{
			return '';
		}
	},$message);
	return $message;
}
function rss_findContent($arr){
	$get=0;
	foreach($arr as $key =>$value){
		if(is_array($value)){
			foreach($value as $key1=>$value1){
				if($key1=='title') $get++;
				elseif($key1=='description') $get++;
				elseif(strpos($key1,'content')!==false) $get++;
			}
			if($get>1) return $arr;
			foreach($value as $key1=>$value1){
				
				if(is_array($value1)){
					if($ret=rss_findContent($value1)){
						return $ret;
					}
				}
			}
		}else{
			if($key=='title') $get++;
			elseif($key=='description') $get++;
			elseif(strpos($key,'content')!==false) $get++;
		}
	}
	if($get>1) return array($arr);
	return array();
}
function create_meta_table($cid,$filename,$meta){
	require_once DZZ_ROOT.'./core/class/class_PHPExcel.php';
	$template=getglobal('setting/attachdir').'RMHCN.xls';
	
	$objReader = PHPExcel_IOFactory::createReader('Excel5');
	$objPHPExcel = $objReader->load ($template);
	$objActSheet = $objPHPExcel->getActiveSheet();
	//读取第三行的skey
	for($i=0;$i<37;$i++){
		$col=getColIndex($i);
		$skey=trim($objActSheet->getCell($col.'3')->getValue());
		if(isset($meta[$skey])){
			$objActSheet->setCellValue($col.'6',$meta[$skey]);
		}
	}
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save($filename);
	
	return true;
	
}
function getColIndex($index){//获取excel的
	$string="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$ret='';
	if($index>255) return '';
	for($i=0;$i<floor($index/strlen($string));$i++){
		$ret=$string[$i];
	}
	$ret.=$string[($index%(strlen($string)))];
	return $ret;
}
function publish_getPath($cid,$filename){
		$t = sprintf("%09d", $cid);
		$dir1 = substr($t, 0, 3);
		$dir2 = substr($t, 3, 2);
		$dir3 = substr($t, 5, 2);
		$target='publish/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.$filename;
		$targetPath = dirname(getglobal('setting/attachdir').$target);
		if(is_file(getglobal('setting/attachdir').$target)){
			
		}else{
			dmkdir($targetPath,0777,false);
		}
		return $target;
	}
function publish_downfile($opath,$file) {
	$file_source=IO::getStream($opath);
	
	$rh = fopen($file_source, 'rb');
	$wh = fopen($file, 'wb');
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
function saveCss($css,$tmpfolder){
	$csscontent='';
	
	foreach($css as $val){
		$filename=rawurldecode($tmpfolder.'/'.$val);
	
		if(!is_file($filename)){
			continue;
		}
		
		$csscontent.=file_get_contents($filename);
	}
	
	//过滤掉一些css
	//preg_match_all("/(.+?)\s*\{[\s\S].+?[\s\S]\}/i",$csscontent,$matches);
	//print_r($matches);
	$csscontent=preg_replace("/@namespace.+?;/i",'',$csscontent);
	$content=preg_replace_callback("/(.+?)\{[.\s\S]+?\}/i",function($matches){
		if(strpos($matches[1],'.')===false){
			return '';
		}else{
			return $matches[0];
		}
	},$csscontent);
	if($content) $content.='img{'."\n"
						.'		max-width:100%;'."\n"
						.'	}'."\n"
						.'	body.view{'."\n"
						.'		font-weight: 100;'."\n"
						.'		line-height: 2;'."\n"
						.'		padding:42px;'."\n"
						.'	}'."\n"
						.'	.dzz-attach,.dzz-link,.dzz-dzzdoc {'."\n"
						.'		padding:0 10px;'."\n"
						.'		display:inline-block;'."\n"
						.'	}'
						.'	.dzz-attach-icon,.dzz-dzzdoc-icon,.dzz-link-icon{'."\n"
						.'		width:1em;'."\n"
						.'		height:1em;'."\n"
						.'		vertical-align: middle;'."\n"
						.'		margin-top:-5px;'."\n"
						.'		margin-right:5px;'."\n"
						.'	}'
						.'  p{text-indent:2em}'
						.'	.dzz-attach-title,.dzz-dzzdoc-title,.dzz-link-title{'."\n"
						.'		text-decoration:none;'."\n"
						.'	}'."\n"
						.'	.at-item{'."\n"
						.'		text-decoration:none;'."\n"
						/*.'	}'."\n"
						.'	video{'."\n"
						.'		    width: 100%;'."\n"*/
						.'	}'."\n";

	return _savetoattachment($content,'style.css',true);
		
}