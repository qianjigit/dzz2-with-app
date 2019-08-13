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
Hook::listen('check_login');
if(empty($_G['uid'])){
	include template('common/header_reload');
	echo "<script type=\"text/javascript\">";
	echo "try{top._login.logging();win.Close();}catch(e){location.href='user.php?mod=logging'}";
	echo "</script>";	
	include template('common/footer_reload');
	exit();
}else{
	$op='my';
	
	require DZZ_ROOT.'dzz/corpus/my.php';
	dexit();

}
