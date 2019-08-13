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
if(!$_G['uid']){
	@header("Location: $_G[siteurl]");
	//require DZZ_ROOT.'./{MOD_PATH}/my.php';
	exit();
}
	$navtitle=lang('archive_of_the_corpus');
	$ismobile=helper_browser::ismobile();
	$list=array();
	$page = empty($_GET['page'])?1:intval($_GET['page']);
	$perpage=20;
	$start=($page-1)*$perpage;
	$keyword=trim($_GET['keyword']);
	$orgid=intval($_GET['orgid']);
	$gets = array(
			'mod'=>'corpus',
			'op' =>'delete',
			'cid'=>$cid,
			'keyword'=>$keyword,
		);
	$theurl = BASESCRIPT."?".url_implode($gets);
	$param=array('corpus','corpus_user');
	$sql=" c.archivetime<1 and c.deletetime>0";
	
	$orgids=array_keys($orgs);
	if($orgid){
		$sql.=" and (u.uid=%d or c.orgid IN (%n))  and c.orgid=%d";
		$param[]=$_G['uid'];
		$param[]=$orgids;
		$param[]=$orgid;
	}else{
		if($orgids){
			$sql.=" and (u.uid=%d or c.orgid IN (%n))";
			$param[]=$_G['uid'];
			$param[]=$orgids;
			
		}else{
			$sql.=" and u.uid=%d";
			$param[]=$_G['uid'];
		}
	}
	if($keyword){
		/*$sql.=" and c.name LIKE %s";
		$param[]='%'.$keyword.'%';*/
		$sql.=" and (c.name LIKE %s or u.username LIKE %s)";
		$param[]='%'.$keyword.'%';
		$param[]='%'.$keyword.'%';
	}
	//print_r($param);
	//exit("select COUNT(*) from %t u LEFT JOIN %t c ON u.cid=c.cid where $sql");
	if($count=DB::result_first("select COUNT(*) from %t c LEFT JOIN %t u ON u.cid=c.cid and u.uid='{$_G[uid]}'  where $sql",$param)){
		foreach(DB::fetch_all("select c.*,u.perm,u.lastvisit from %t c LEFT JOIN %t u ON u.cid=c.cid and u.uid='{$_G[uid]}'  where $sql order by c.archivetime DESC limit $start,$perpage" ,$param) as $value){
			$list[$value['cid']]=$value;
		}
	}
	$next=false;
	if($count && $count>$start+count($list)) $next=true;
	if($_GET['do']=='list'){
		if($ismobile){
			include template('mobile/my_delete_item');
		}else{
			include template('corpus_delete_item');
		}
		
	}else{
		if($ismobile){
			include template('mobile/my_delete');
		}else{
			include template('corpus_delete');

		}
		exit();
		
	}

?>
