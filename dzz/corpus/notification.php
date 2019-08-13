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
$filter=trim($_GET['filter']);

if($filter=='new'){//列出所有新通知
	$list=array();
	$nids=array();//new>0
	foreach(DB::fetch_all("select n.*,u.avatarstatus from %t n LEFT JOIN %t u ON n.authorid=u.uid where n.new>0 and n.uid=%d  order by dateline DESC",array('notification','user',$_G['uid'])) as $value){
		$value['dateline']=dgmdate($value['dateline'],'u');
		$nids[]=$value['id'];
		$list[]=$value;
	}
	if($nids){//去除新标志
		C::t('notification')->update($nids,array('new'=>0));
	}
}elseif($filter=='checknew'){//检查有没有新通知
	$num=DB::result_first("select COUNT(*) from %t where new>0 and uid=%d",array('notification',$_G['uid']));
	exit(json_encode(array('sum'=>$num,'timeout'=>60*5*1000)));
}else{
	$navtitle=lang('all_notice');
	$list=array();
	$page = empty($_GET['page'])?1:intval($_GET['page']);
	$perpage=20;
	$start=($page-1)*$perpage;
	$gets = array(
			'mod'=>'corpus',
			'op' =>'notification',
			'filter'=>'all'
		);
	$theurl = BASESCRIPT."?".url_implode($gets);
	$list=array();

	if($count=DB::result_first("select COUNT(*) from %t  where uid=%d",array('notification',$_G['uid']))){
		foreach(DB::fetch_all("select n.*,u.avatarstatus from %t n LEFT JOIN %t u ON n.authorid=u.uid where n.uid=%d  order by n.dateline DESC limit $start,$perpage",array('notification','user',$_G['uid'])) as $value){
			$value['dateline']=dgmdate($value['dateline'],'u');
			$list[]=$value;
		}
	}
	$next=false;
	if($count && $count>$start+count($list)) $next=true;
	if($_GET['do']=='list'){
		include template('notification_list_item');
	}else{
		include template('notification_list');
	}
	dexit();
}

include template('notification');
dexit();
?>
