<?php
/* @authorcode  codestrings
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if (!defined('IN_DZZ')) {
    exit('Access Denied');
}
$navtitle = lang('archive_of_the_corpus');

$list = array();
$page = empty($_GET['page']) ? 1 : intval($_GET['page']);
$ismobile=helper_browser::ismobile();
$perpage = 20;
$start = ($page - 1) * $perpage;
$keyword = trim($_GET['keyword']);
$orgid = intval($_GET['orgid']);
$gets = array(
    'mod' => 'corpus',
    'op' => 'archive',
    'cid' => $cid,
    'keyword' => $keyword,
);
$theurl = BASESCRIPT . "?" . url_implode($gets);
$param = array('corpus', 'corpus_user');
$sql = " c.archivetime>0 and c.deletetime<1";

$orgs = C::t('corpus_organization')->fetch_all_by_uid($_G['uid']);
$orgids = array_keys($orgs);
if ($orgid) {
    $sql .= " and (u.uid=%d or c.orgid IN (%n))  and c.orgid=%d";
    $param[] = $_G['uid'];
    $param[] = $orgids;
    $param[] = $orgid;
} else {
    if ($orgids) {
        $sql .= " and (u.uid=%d or c.orgid IN (%n))";
        $param[] = $_G['uid'];
        $param[] = $orgids;

    } else {
        $sql .= " and u.uid=%d";
        $param[] = $_G['uid'];
    }
}
if ($keyword) {
    $sql .= " and (c.name LIKE %s or u.username = %s)";
    $param[] = '%' . $keyword . '%';
    $param[] = $keyword;

}

if ($count = DB::result_first("select COUNT(*) from %t c LEFT JOIN %t u ON u.cid=c.cid and u.uid='{$_G[uid]}'  where $sql", $param)) {
    foreach (DB::fetch_all("select c.*,u.perm,u.lastvisit from %t c LEFT JOIN %t u ON u.cid=c.cid and u.uid='{$_G[uid]}'  where $sql order by c.archivetime DESC limit $start,$perpage", $param) as $value) {
        $list[$value['cid']] = $value;
    }
}
$next = false;
if ($count && $count > $start + count($list)) $next = true;
if ($_GET['do'] == 'list') {
	if($ismobile){
		include template('mobile/my_archive_item');
	}else{
		include template('corpus_archive_item');
	}
} else {


	if($ismobile){
		include template('mobile/my_archive');
		exit();
	}
	
    include template('corpus_archive');
}
