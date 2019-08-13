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
	include template('common/header_reload');
	echo "<script type=\"text/javascript\">";
	echo "try{top._login.logging();win.Close();}catch(e){location.href='user.php?mod=logging'}";
	echo "</script>";	
	include template('common/footer_reload');
	exit();
}
$cid=trim($_GET['cid']);
if(!$corpus=C::t('corpus')->fetch($cid)){
	showmessage(lang('the_corpus_you_want_to_report_does_not_exist_or_has_been_deleted'));
}

if(submitcheck('reportsubmit')){
	$setarr=array('cid'=>$cid,
				  'detail'=>trim($_GET['detail']),
				  'reasons'=>$_GET['modreasons']?implode(',',$_GET['modreasons']):'',
				  'dateline'=>TIMESTAMP,
				  'aids'=>$_GET['aid']?implode(',',$_GET['aid']):'',
				  'uid'=>$_G['uid'],
				  'username'=>$_G['username']
				  );
	if(C::t('corpus_report')->insert($setarr)){
		showmessage(lang('the_report_will_be_submitted_successfully'),$_G['siteurl'],array(),array('alert'=>'right'));
	}else{
		showmessage(lang('failure_to_report'));
	}
}else{
	if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
	$modreasons=$_G['cache']['corpus:setting']['modreasons']?explode("\n",stripslashes($_G['cache']['corpus:setting']['modreasons'])):array();
	include template('report');
	dexit();
}
?>
