<?php
/* @authorcode  codestrings
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */

if(!defined('IN_DZZ') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}
@set_time_limit(0);
//卸载文集程序；
try{
 	
 	foreach(DB::fetch_all("select orgid from %t where 1",array('corpus_organization')) as $value){
		C::t('corpus_organization')->delete_by_orgidid($value['orgid']);
	}

	foreach(DB::fetch_all("select cid from %t where 1",array('corpus')) as $value){
		C::t('corpus')->delete_by_cid($value['cid']);
	}
}catch(Exception $e){
	
}
	
//删除文集所有评论
	foreach(DB::fetch_all("select cid from %t where  idtype='corpus'",array('comment')) as $value){
		$dels[]=$value['cid'];
	}
	C::t('comment')->delete($dels);
	
	C::t('comment_at')->delete_by_cid($dels); //删除@
	
	C::t('comment_attach')->delete_by_cid($dels);//删除附件
		
$sql = <<<EOF

DROP TABLE IF EXISTS `dzz_corpus_user`;
DROP TABLE IF EXISTS `dzz_corpus_setting`;
DROP TABLE IF EXISTS `dzz_corpus_event`;
DROP TABLE IF EXISTS `dzz_corpus_class`;
DROP TABLE IF EXISTS `dzz_corpus_class1`;
DROP TABLE IF EXISTS `dzz_corpus_class_index`;
DROP TABLE IF EXISTS `dzz_corpus_convert`;
DROP TABLE IF EXISTS `dzz_corpus_convert_cron`;
DROP TABLE IF EXISTS `dzz_corpus_download`;
DROP TABLE IF EXISTS `dzz_corpus_meta`;
DROP TABLE IF EXISTS `dzz_corpus_meta_setting`;
DROP TABLE IF EXISTS `dzz_corpus`;
DROP TABLE IF EXISTS `dzz_corpus_organization`;
DROP TABLE IF EXISTS `dzz_corpus_organization_user`;
DROP TABLE IF EXISTS `dzz_corpus_publish`;
DROP TABLE IF EXISTS `dzz_corpus_publish_platform`;
DROP TABLE IF EXISTS `dzz_corpus_publish_record`;
DROP TABLE IF EXISTS `dzz_corpus_report`;
DROP TABLE IF EXISTS `dzz_corpus_lock`;
DROP TABLE IF EXISTS `dzz_corpus_usercache`;
DROP TABLE IF EXISTS `dzz_corpus_reversion`;
DROP TABLE IF EXISTS `dzz_corpus_fullcontent`;

EOF;

try{
runquery($sql);
}catch(Exception $e){}

$finish = true;
