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

$sql = <<<EOF
DROP TABLE IF EXISTS `dzz_corpus`;
CREATE TABLE IF NOT EXISTS `dzz_corpus` (
  cid int(10) NOT NULL AUTO_INCREMENT,
  `name` char(80) NOT NULL DEFAULT '',
  uid int(10) NOT NULL DEFAULT '0' COMMENT '创建人',
  username char(30) NOT NULL DEFAULT '',
  perm tinyint(1) NOT NULL DEFAULT '0' COMMENT '权限：0:私有;1:小组内可见；2：公开',
  aid smallint(6) NOT NULL DEFAULT '0' COMMENT '封面背景图aid',
  documents smallint(10) NOT NULL DEFAULT '0' COMMENT '文档数',
  follows smallint(6) NOT NULL DEFAULT '0' COMMENT '关注数',
  members smallint(6) NOT NULL DEFAULT '0',
  hot smallint(6) NOT NULL COMMENT '热度',
  viewnum int(10) unsigned NOT NULL DEFAULT '0',
  titlehide tinyint(1) NOT NULL DEFAULT '0' COMMENT '背景图是否显示标题',
  forbidcommit tinyint(1) NOT NULL DEFAULT '1',
  archiveuid int(10) NOT NULL DEFAULT '0',
  archivetime int(10) NOT NULL DEFAULT '0',
  deleteuid int(10) NOT NULL DEFAULT '0',
  deletetime int(10) NOT NULL DEFAULT '0',
  dateline int(10) NOT NULL DEFAULT '0',
  forceindex tinyint(1) NOT NULL DEFAULT '0',
  pos smallint(6) NOT NULL DEFAULT '0',
  stared tinyint(1) NOT NULL DEFAULT '0',
  color char(7) NOT NULL DEFAULT '',
  orgid int(11) NOT NULL,
  theme tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:百度编辑器；1：markdown',
  extra text NOT NULL COMMENT '额外的一些数据',
  updatetime int(10) NOT NULL DEFAULT '0' COMMENT '内容变更时间包括目录结构的变化',
  downloads smallint(6) unsigned NOT NULL DEFAULT '0',
  allowdown smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '0:全部允许；1：按设定；其他的按二进制',
  allowdown_m smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '成员下载权限',
  modreasons varchar(255) NOT NULL DEFAULT '',
  isbn varchar(20) NOT NULL DEFAULT '',
  updatetime_meta int(10) unsigned NOT NULL DEFAULT '0' COMMENT '元数据更新时间',
  updatetime_cover tinyint(1) NOT NULL DEFAULT '0',
  css int(10) unsigned NOT NULL DEFAULT '0',
  lastviewtime INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0',
  PRIMARY KEY (cid),
  KEY uid (uid),
  KEY dateline (dateline),
  KEY hot (hot),
  KEY archivetime (archivetime),
  KEY deletetime (deletetime)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_class`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_class` (
  fid int(10) unsigned NOT NULL AUTO_INCREMENT,
  fname varchar(255) NOT NULL DEFAULT '',
  `type` enum('folder','file') NOT NULL DEFAULT 'folder',
  revid int(10) NOT NULL DEFAULT '0' COMMENT '是文档时，记录此文档的revid',
  version smallint(6) unsigned NOT NULL DEFAULT '0',
  cid int(10) unsigned NOT NULL DEFAULT '0',
  pfid int(10) unsigned NOT NULL DEFAULT '0',
  uid int(10) NOT NULL DEFAULT '0',
  username char(30) NOT NULL DEFAULT '',
  disp smallint(6) NOT NULL DEFAULT '0',
  dateline int(10) NOT NULL DEFAULT '0',
  deletetime int(10) NOT NULL DEFAULT '0',
  deleteuid int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (fid),
  KEY cid (cid),
  KEY disp (disp),
  KEY pfid (pfid,deletetime)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_class_index`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_class_index` (
  fid int(10) NOT NULL AUTO_INCREMENT,
  cid int(10) NOT NULL DEFAULT '0',
  tableid smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (fid),
  KEY cid (cid)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_convert`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_convert` (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  cid int(10) unsigned NOT NULL DEFAULT '0',
  fid int(10) unsigned NOT NULL DEFAULT '0',
  path varchar(255) NOT NULL DEFAULT '',
  format varchar(30) NOT NULL DEFAULT '',
  dateline int(10) unsigned NOT NULL DEFAULT '0',
  downloads smallint(6) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  UNIQUE KEY cid (cid,fid,format)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_convert_cron`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_convert_cron` (
  cronid int(10) unsigned NOT NULL AUTO_INCREMENT,
  spath varchar(255) NOT NULL DEFAULT '',
  tpath varchar(255) NOT NULL DEFAULT '',
  dateline int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态码：0：待转换;1:正在转换；2:成功;-1：出错',
  `error` varchar(255) NOT NULL DEFAULT '',
  metadata text NOT NULL,
  format varchar(30) NOT NULL DEFAULT '',
  cid int(10) unsigned NOT NULL DEFAULT '0',
  fid int(10) unsigned NOT NULL DEFAULT '0',
  disp smallint(6) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (cronid),
  KEY `status` (`status`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_download`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_download` (
  id int(11) NOT NULL AUTO_INCREMENT,
  cid int(10) unsigned NOT NULL DEFAULT '0',
  fid int(10) unsigned NOT NULL DEFAULT '0',
  lasttime int(10) unsigned NOT NULL DEFAULT '0' COMMENT '生成时间',
  downloads smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '下载次数',
  pdf varchar(255) NOT NULL DEFAULT '',
  doc varchar(255) NOT NULL DEFAULT '',
  html varchar(255) NOT NULL DEFAULT '',
  epub varchar(255) NOT NULL DEFAULT '',
  txt varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY cid (cid,fid)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_event`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_event` (
  eid int(10) NOT NULL AUTO_INCREMENT,
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username char(30) NOT NULL DEFAULT '',
  body_template varchar(30) NOT NULL DEFAULT '',
  body_data text NOT NULL,
  bz varchar(80) NOT NULL DEFAULT '',
  dateline int(11) NOT NULL,
  PRIMARY KEY (eid),
  KEY uid (uid),
  KEY dateline (dateline),
  KEY bz (bz)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_fullcontent`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_fullcontent` (
  fid int(10) NOT NULL,
  fullcontent mediumtext NOT NULL,
  UNIQUE KEY fid (fid),
  FULLTEXT KEY fullcontent (fullcontent),
  FULLTEXT KEY fullcontent_2 (fullcontent)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_lock`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_lock` (
  fid int(10) unsigned NOT NULL DEFAULT '0',
  uid int(10) unsigned NOT NULL DEFAULT '0' COMMENT '锁定用户',
  locktime int(10) unsigned NOT NULL DEFAULT '0' COMMENT '锁定时间',
  PRIMARY KEY (fid)
) ENGINE=MyISAM;



DROP TABLE IF EXISTS `dzz_corpus_organization`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_organization` (
  orgid int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  cover int(10) unsigned NOT NULL DEFAULT '0' COMMENT '群组封面',
  logo int(10) unsigned NOT NULL DEFAULT '0',
  `desc` text NOT NULL,
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username varchar(30) NOT NULL DEFAULT '',
  privacy tinyint(1) NOT NULL DEFAULT '1' COMMENT '隐私设置：1：隐私；0：公开',
  mperm_c tinyint(1) NOT NULL DEFAULT '7' COMMENT '成员创建版面权限：0：不能创建版，1：隐私；2：小组内可见，4：公开；二进制组合',
  inviteperm tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1:仅可邀请小组内成员；0：任意成员',
  emaildomain text NOT NULL COMMENT '允许添加到小组的成员的email域名',
  dateline int(10) unsigned NOT NULL DEFAULT '0',
  color char(7) NOT NULL DEFAULT '',
  website varchar(255) NOT NULL DEFAULT '',
  removeperm tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (orgid),
  KEY uid (uid),
  KEY dateline (dateline)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_organization_user`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_organization_user` (
  orgid int(10) NOT NULL DEFAULT '0',
  uid int(11) NOT NULL,
  perm tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '1:观察员：2：普通成员；3：管理员',
  dateline int(10) unsigned NOT NULL DEFAULT '0',
  id int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id),
  UNIQUE KEY uid (uid,orgid),
  KEY dateline (dateline)
) ENGINE=MyISAM;



DROP TABLE IF EXISTS `dzz_corpus_report`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_report` (
  id int(11) NOT NULL AUTO_INCREMENT,
  aids text NOT NULL COMMENT '{lang to_report_the_picture}',
  detail text NOT NULL COMMENT '{lang detailed_description}',
  reasons varchar(255) NOT NULL DEFAULT '' COMMENT '{lang the_reason_of_report}',
  uid int(10) NOT NULL DEFAULT '0' COMMENT '{lang report_of_the_user}',
  username varchar(30) NOT NULL DEFAULT '',
  dateline int(10) NOT NULL DEFAULT '0' COMMENT '{lang feedback_time}',
  cid int(10) NOT NULL DEFAULT '0' COMMENT '书cid',
  result tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:{lang pending}；1：{lang ignore}；2：{lang banned};',
  result_time int(10) unsigned NOT NULL DEFAULT '0' COMMENT '{lang processing_time}',
  PRIMARY KEY (id),
  KEY dateline (dateline)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_reversion`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_reversion` (
  revid int(10) unsigned NOT NULL AUTO_INCREMENT,
  fid int(10) unsigned NOT NULL DEFAULT '0',
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username char(30) NOT NULL DEFAULT '',
  dateline int(10) NOT NULL DEFAULT '0',
  `code` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:html;1:markdown;2:bbcode',
  content mediumtext NOT NULL,
  attachs text NOT NULL,
  version smallint(6) NOT NULL DEFAULT '1',
  remoteid smallint(6) unsigned NOT NULL DEFAULT '0',
  md5 varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (revid),
  KEY dateline (dateline,fid),
  KEY fid (fid,version)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_setting`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_setting` (
  skey varchar(255) NOT NULL DEFAULT '',
  svalue text NOT NULL,
  PRIMARY KEY (skey)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_user`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_user` (
  id int(10) NOT NULL AUTO_INCREMENT,
  cid int(10) unsigned NOT NULL DEFAULT '0',
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username char(30) NOT NULL DEFAULT '',
  perm tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:{lang attention}；2：:{lang rank_and_file};3：{lang manager}',
  hot int(10) unsigned NOT NULL DEFAULT '0' COMMENT '{lang user_activation}',
  dnum int(10) unsigned NOT NULL DEFAULT '0' COMMENT '{lang create_a_document_number}',
  dateline int(10) NOT NULL DEFAULT '0',
  lastvisit int(10) unsigned NOT NULL DEFAULT '0' COMMENT '{lang last_update}',
  PRIMARY KEY (id),
  UNIQUE KEY cid_uid (cid,uid),
  KEY uid (uid),
  KEY cid (cid),
  KEY hot (hot),
  KEY dateline (dateline),
  KEY perm (perm)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `dzz_corpus_usercache`;
CREATE TABLE IF NOT EXISTS `dzz_corpus_usercache` (
  uid int(10) NOT NULL DEFAULT '0',
  paixu_stared text NOT NULL,
  usesize bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户空间使用情况',
  UNIQUE KEY uid (uid)
) ENGINE=MyISAM;
EOF;
runquery($sql);
$finish = true;