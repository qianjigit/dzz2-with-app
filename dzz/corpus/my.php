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
//error_reporting(E_ALL);
//			print_r('111');
//	die;
Hook::listen('check_login');
include_once libfile('function/corpus');
$colorarr = require(DZZ_ROOT.'./dzz/corpus/config/config.php');
$colorarr=$colorarr['colorarr'];
$navtitle=lang('mine_corpus');
//判断创建小组权限
if(!$_G['cache']['corpus:setting']) loadcache('corpus:setting');
$setting=$_G['cache']['corpus:setting'];
$archiveview=$setting['archiveview'];
$org_create_perm=getCreatePerm($_G['uid'],'org');

    $wheresql = ' and 1';
	//error_reporting(E_ALL);
	$keyword=trim($_GET['keyword']);
    $param1 = array('corpus_user','corpus',$_G['uid']);
    $param2 = array('corpus','corpus_user',$_G['uid']);
    if($keyword){
        $wheresql.=" and (c.name LIKE %s or u.username LIKE %s)";
        $param1[]='%'.$keyword.'%';
        $param1[]='%'.$keyword.'%';
        $param2[]='%'.$keyword.'%';
        $param2[]='%'.$keyword.'%';
    }
	$my=array();
	$mylist=$list=array();
	$colorList = array();
	//获取我的文集

	foreach(DB::fetch_all("select c.*,u.perm as uperm,u.lastvisit from %t u LEFT JOIN %t c ON u.cid=c.cid where u.uid=%d and c.archivetime<1 and c.deletetime<1 $wheresql order by c.forceindex asc, c.dateline DESC" ,$param1) as $value){
		$value['viewperm']=$value['perm'];
		$value['perm']=$value['uperm'];
		if(!$value['aid'] && !$value['color']){
			$colorList[$value['cid']] = isset($colorList[$value['cid']]) ? $colorList[$value['cid']] : $colorarr[rand(0,count($colorarr)-1)];
		}
		if($value['extra']){
			$value['extra']=unserialize($value['extra']);
			$value['metadata']=array();
			$allowkeys=array('--isbn','--authors','--comments','--publisher');
			foreach($value['extra'] as $key => $val){
				if(in_array($key,$allowkeys) && $val) $value['metadata'][]=lang('template',$key).'：'.$val;
			}
			$value['metadata']=implode("\n",$value['metadata']);
		}
		
		if($value['forceindex'] || empty($value['orgid'])){
			$my[$value['cid']]=$value;		
		}else{
			$mylist[$value['orgid']][$value['cid']]=$value;
		}

		$list[$value['cid']]=$value;
	}

	foreach(DB::fetch_all("select c.*,u.perm as uperm,u.lastvisit from %t c LEFT JOIN %t u ON u.cid=c.cid and u.uid=%d where  c.forceindex>0 and c.archivetime<1 and c.deletetime<1  $wheresql order by c.dateline DESC" ,$param2) as $value){
		$value['viewperm']=$value['perm'];
		$value['perm']=$value['uperm'];
		if(!$value['aid'] && !$value['color']){
			$colorList[$value['cid']] = isset($colorList[$value['cid']]) ? $colorList[$value['cid']] : $colorarr[rand(0,count($colorarr)-1)];
		}
		if($value['extra']){
			$value['extra']=unserialize($value['extra']);
			$value['metadata']=array();
			$allowkeys=array('--isbn','--authors','--comments','--publisher');
			foreach($value['extra'] as $key => $val){
				if(in_array($key,$allowkeys) && $val) $value['metadata'][]=lang('template',$key).'：'.$val;
			}
			$value['metadata']=implode("\n",$value['metadata']);
		}
		$my[$value['cid']]=$value;		
	}
	//排序star
	$stared=array();
	if($cache=C::t('corpus_usercache')->fetch($_G['uid'])){
		$paixu_stared=explode(',',$cache['paixu_stared']);
		foreach($paixu_stared as $cid){
			if($list[$cid]){
				$stared[$cid]=$list[$cid];
			}
		}
	}

	$stared_cids=array_keys($stared);
	//获取用户的organization
	$orglist=array();
	foreach(DB::fetch_all("select o.*,u.perm from %t u LEFT JOIN %t o ON u.orgid=o.orgid where u.uid=%d and o.orgid>0  order by o.dateline" ,array('corpus_organization_user','corpus_organization',$_G['uid'])) as $value){
		if(!$value['aid'] && !$value['color']){
			$colorList[$value['cid']] = isset($colorList[$value['cid']]) ? $colorList[$value['cid']] : $colorarr[rand(0,count($colorarr)-1)];
		} 
		if($mylist[$value['orgid']]){
			 $value['list']=$mylist[$value['orgid']];
			 unset($mylist[$value['orgid']]);
		}else $value['list']=array();
		
		if($value['logo']){
			$value['logourl']='index.php?mod=io&op=thumbnail&original=1&path='.dzzencode('attach::'.$value['logo']);
		}
		$orglist[$value['orgid']]=$value;			
	}
	foreach($mylist as $olist){
		foreach($olist as $value){
			$my[$value['cid']]=$value;
		}
	}
	$ismobile=helper_browser::ismobile();
	if($ismobile){
		include template('mobile/my');
	}else{
		include template('corpus_my');
	}
	
	dexit();
	
?>