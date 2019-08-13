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
class table_corpus extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus';
		$this->_pk    = 'cid';

		parent::__construct();
	}
	public function checkmaxorganization($uid){
		$maxorganization=C::t('corpus_setting')->fetch('maxorganization');
		if($maxorganization==0){
			return '无限制';
		}else{
			$sum=DB::result_first("select COUNT(*) from %t where uid=%d ",array('corpus_organization',$uid));
			if($sum<$maxorganization) return $maxorganization-$sum;
		}
		 return false;
	}
	public function checkmaxCorpus($uid){
		$maxcorpus=C::t('corpus_setting')->fetch('maxcorpus');
		
		if($maxcorpus==0){
			return -1;//无限制
		}else{
			$sum=DB::result_first("select COUNT(*) from %t where uid=%d ",array('corpus',$uid));
			if($sum<$maxcorpus) return $maxcorpus-$sum;
			else return 0;
		}
		 return false;
	}
	public function fetch_by_cid($cid,$uid){
		global $_G;
		if(!$uid) $uid=getglobal('uid');
		$data=array();
		if($data=parent::fetch($cid)){
			
			$data['viewperm']=$data['perm'];//文集的查看权限使用viewperm；
			$data['extra']=$data['extra']?unserialize($data['extra']):array();
			if(!$_G['cache']['corpus:setting'])	loadcache('corpus:setting');
			if($top_allowdown=$_G['cache']['corpus:setting']['allowdown']) $data['allowdown']=$data['allowdown']>0?intval($data['allowdown'] & $top_allowdown):$top_allowdown;
			
			if($uid>0){
				 $userperm=C::t('corpus_user')->fetch_perm_by_uid($uid,$cid);
				 if($data['orgid']){
					$data['orgperm']=C::t('corpus_organization_user')->fetch_perm_by_uid($uid,$data['orgid']);
				}
			}
			$data['perm']=max($userperm,$data['orgperm'],0);
			if($_G['adminid']==1) $data['perm']=4;
		}
		return $data;
	}
	public function archive_by_cid($cid){
		
		$setarr=array('deletetime'=>0,
					  'archivetime'=>TIMESTAMP,
					  'archiveuid'=>getglobal('uid')
					  );
		if($return =parent::update($cid,$setarr)){
			
			//产生事件
			$corpus=parent::fetch($cid);
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_archive',
							  'body_data'=>serialize(array('cid'=>$corpus['cid'],'corpusname'=>$corpus['name'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
							  );
				C::t('corpus_event')->insert($event);
			
			//通知文集所有参与者
				$users=C::t('corpus_user')->fetch_all_by_perm($cid,array('2','3'));
				$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
				foreach($users as $value){
					if($value['uid']!=getglobal('uid')){
						//发送通知
						$notevars=array(
										'from_id'=>$appid,
										'from_idtype'=>'app',
										'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$corpus['cid'],
										'author'=>getglobal('username'),
										'authorid'=>getglobal('uid'),
										'dataline'=>dgmdate(TIMESTAMP),
										'corpusname'=>getstr($corpus['name'],30),
										
										);
						
							$action='corpus_archived';
							$type='corpus_archived_'.$cid;
						
						dzz_notification::notification_add($value['uid'], $type, $action, $notevars, 0,'dzz/corpus');
					}
				}
		}
		return $return;
	}
	public function restore_by_cid($cid){
		//删除的文集也可以归档，归档后删除属性清除
		$data=parent::fetch($cid);
		$setarr=array('deletetime'=>0,
					  'deleteuid'=>0,
					  'archivetime'=>0,
					  'archiveuid'=>0
					  );
		if($return=parent::update($cid,$setarr)){
			//产生事件
			
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							 
							  'body_template'=>'corpus_restore',
							  'body_data'=>serialize(array('cid'=>$data['cid'],'corpusname'=>$data['name'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
							  );
				C::t('corpus_event')->insert($event);
			
			//通知文集所有参与者
				$users=C::t('corpus_user')->fetch_all_by_perm($cid,array('2','3'));
				$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
				foreach($users as $value){
					if($value['uid']!=getglobal('uid')){
						//发送通知
						$notevars=array(
										'from_id'=>$appid,
										'from_idtype'=>'app',
										'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$data['cid'],
										'author'=>getglobal('username'),
										'authorid'=>getglobal('uid'),
										'dataline'=>dgmdate(TIMESTAMP),
										'corpusname'=>getstr($data['name'],30),
										
										);
						
							$action='corpus_restore';
							$type='corpus_restore_'.$cid;
						
						dzz_notification::notification_add($value['uid'], $type, $action, $notevars, 0,'dzz/corpus');
					}
				}
		}
		return $return;
	}
	public function delete_by_cid($cid,$force=false){//删除文集；
		//删除文集
		$data=parent::fetch($cid);
		if($force || $data['deletetime']>0){
			return self::delete_permanent_by_cid($cid);
		}else{
			$setarr=array('archivetime'=>0,
						  'deletetime'=>TIMESTAMP,
						  'deleteuid'=>getglobal('uid')
						  );
			if($return =parent::update($cid,$setarr)){
				//产生事件
				
				$event =array(    'uid'=>getglobal('uid'),
								  'username'=>getglobal('username'),
								  
								  'body_template'=>'corpus_delete',
								  'body_data'=>serialize(array('cid'=>$cid,'corpusname'=>$data['name'])),
								  'dateline'=>TIMESTAMP,
								  'bz'=>'corpus_'.$cid,
								  );
					C::t('corpus_event')->insert($event);
			}
			return $return;
		}
	}
	public function delete_permanent_by_cid($cid){
		$data=parent::fetch($cid);
		
		//删除文集用户
		DB::query("delete  from %t where cid=%d",array('corpus_user',$cid));
		//删除文集相关事件
		DB::query("delete  from %t where bz=%s",array('corpus_event','corpus_'.$cid));
		//删除文集内容
		C::t('corpus_class')->delete_by_cid($cid);
		
		if($return=parent::delete($cid)){
			if($data['aid']) C::t('attachment')->delete_by_aid($data['aid']);
			if($data['css']) C::t('attachment')->delete_by_aid($data['css']);
		}
		return $return;
	}
	public function insert_by_cid($arr){
		if($cid=parent::insert($arr,1)){
			if($arr['aid']){
				parent::update($cid,array('updatetime_cover'=>TIMESTAMP));
			    C::t('attachment')->addcopy_by_aid($arr['aid']);//封面copys+1
			}
			if($arr['css']) C::t('attachment')->addcopy_by_aid($arr['css']);//ccs文件copys+1
			//C::t('corpus_class')->insert_default_by_cid($cid);//创建默认分类
			$userarr=array('uid'=>$arr['uid'],
						   'username'=>$arr['username'],
						   'perm'=>3,//管理员
						   'dateline'=>TIMESTAMP,
						   'cid'=>$cid,
						   );
		    C::t('corpus_user')->insert($userarr);//创建用户
			//产生事件
			$event =array(    'uid'=>$arr['uid'],
						       'username'=>$arr['username'],
							  'body_template'=>'corpus_create',
							  'body_data'=>serialize(array('cid'=>$cid,'corpusname'=>$arr['name'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
						 );
				C::t('corpus_event')->insert($event);
		}
		return $cid;
	}
	public function update_by_cid($cid,$arr){
		$data=parent::fetch($cid);
		if($return=parent::update($cid,$arr)){
			if($arr['aid']!=$data['aid']){
				parent::update($cid,array('updatetime_cover'=>TIMESTAMP));
				C::t('attachment')->addcopy_by_aid($arr['aid']);
				$aids=C::t('corpus_setting')->getCoverAids();
				if(!in_array($data['aid'],$aids)){//用户自定义的封面删除
					C::t('attachment')->delete_by_aid($data['aid']);
				}
				
			}
		}
		return $return;
	}
	
	public function fetch_all_for_manage($limit,$keyword='',$delete,$archive,$perm=-1,$forceindex='',$orgid=0,$modreason='',$order='',$count=false){
		$param=array($this->_table,'corpus_organization');
		$searchsql='1';
		if(!empty($keyword)){
			$param[]='%'.$keyword.'%';
			$param[]=$keyword;
			$searchsql.=' and (c.name like %s or c.username=%s)';
		}
		if(!empty($forceindex)){
			$searchsql.=' and c.forceindex>0';
		}
		if($perm>-1){
			$param[]=$perm;
			$searchsql.=' and c.perm=%d';
		}
		if(!empty($orgid)){
			$param[]=$orgid;
			$searchsql.=' and c.orgid=%d';
		}
		if(!empty($modreason)){
			$searchsql.=" and c.modreasons!=''";
		}
		if(!empty($delete)){
			$searchsql.=' and c.deletetime>0';
		}else{
			$searchsql.=' and c.deletetime<1';
		}
		if(!empty($archive)){
			$searchsql.=' and c.archivetime>0';
		}else{
			$searchsql.=' and c.archivetime<1';
		}
		if($count){
			return DB::result_first("select COUNT(*) from %t c LEFT JOIN %t o ON c.orgid=o.orgid where $searchsql",$param);
		}
		$limitsql='';
		if($limit){
			$limit=explode('-',$limit);
			if(count($limit)>1){
				$limitsql.=" limit ".intval($limit[0]).",".intval($limit[1]);
			}else{
				$limitsql.=" limit ".intval($limit[0]);
			}
		}
		$orderby=" order by $order DESC";
		return DB::fetch_all("select c.*,o.name as orgname from %t c LEFT JOIN %t o ON c.orgid=o.orgid where  $searchsql $orderby $limitsql",$param,'cid');
	}
	
	 public function increase($cids, $fieldarr) {
		$cids = dintval((array)$cids, true);
		$sql = array();
		$num = 0;
		$allowkey = array('documents', 'comments', 'follows', 'members', 'viewnum','updatetime','downloads','lastviewtime');
		foreach($fieldarr as $key => $value) {
			if(in_array($key, $allowkey)) {
				if(is_array($value)) {
					$sql[] = DB::field($key, $value[0]);
				} else {
					$value = dintval($value);
					$sql[] = "`$key`=`$key`+'$value'";
				}
			} else {
				unset($fieldarr[$key]);
			}
		}
		if(!empty($sql)){
			$cmd = "UPDATE " ;
			$num = DB::query($cmd.DB::table($this->_table)." SET ".implode(',', $sql)." WHERE cid IN (".dimplode($cids).")", 'UNBUFFERED');
			
		}
		return $num;
	}
	public function remove_orgid($orgid){
		return DB::query("update %t set orgid='0' where orgid=%d",array($this->_table,$orgid));
	}
	
	 //评论回调函数
	 public function callback_by_comment($comment,$action='add',$ats=array()){
		 $fid=$comment['id'];
		 $table=C::t('#corpus#corpus_class_index')->getTableidByFid($fid);
		 $table='corpus_class_'.$table;
		 $class=DB::fetch_first("select * from %t where fid=%d",array($table,$fid));
		$replyaction='';
		$rpost=array();
			if($comment['rcid']>0){
				$rpost=C::t('comment')->fetch($comment['rcid']);
				$replyaction='_reply';
			}elseif($comment['pcid']>0){
				$rpost=C::t('comment')->fetch($comment['pcid']);
				$replyaction='_reply';
			}
		 //产生事件 
		 $event =array('uid'=>$comment['authorid'],
					  'username'=>$comment['author'],
					  'body_template'=>'corpus_commit_doc_'.$action.$replyaction,
					  'body_data'=>serialize(array('author'=>$rpost['author'],'cid'=>$class['cid'],'fid'=>$fid,'fname'=>$class['fname'],'comment'=>$comment['message'])),
					  'dateline'=>TIMESTAMP,
					  'bz'=>'corpus_'.$class['cid'],
					  );
					  
		C::t('#corpus#corpus_event')->insert($event);
		$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
		if($action=='add'&& $ats){//如果评论中@用户时，给用户发送通知
			foreach($ats as $uid){
				//发送通知
				if($uid!=getglobal('uid')){
					//发送通知
					$notevars=array(
									'from_id'=>$appid,
									'from_idtype'=>'app',
									'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$class['cid'].'&fid='.$class['fid'],
									'author'=>getglobal('username'),
									'authorid'=>getglobal('uid'),
									'dataline'=>dgmdate(TIMESTAMP),
									'fname'=>getstr($class['fname'],30),
									'comment'=>$comment['message'],
									);
					
					dzz_notification::notification_add($uid, 'corpus_comment_at_'.$class[$cid], 'corpus_comment_at', $notevars, 0,'dzz/corpus');
				}
			}
		}
		if($action=='add'){
			if($comment['pcid']==0){
				//发送通知,通知文档的作者；
				if($class['uid']!=getglobal('uid')){
						
						//发送通知
						$notevars=array(
										'from_id'=>$appid,
										'from_idtype'=>'app',
										'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$class['cid'].'&fid='.$class['fid'],
										'author'=>getglobal('username'),
										'authorid'=>getglobal('uid'),
										'dataline'=>dgmdate(TIMESTAMP),
										'fname'=>getstr($class['fname'],30),
										'comment'=>$comment['message'],
										);
						
						dzz_notification::notification_add($class['uid'], 'corpus_comment_mydoc_'.$class[$cid], 'corpus_comment_mydoc', $notevars, 0,'dzz/corpus');
				}
			}else{
				//通知原评论人	
				if($rpost['uid']!=getglobal('uid')){
						
						//发送通知
						$notevars=array(
										'from_id'=>$appid,
										'from_idtype'=>'app',
										'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$class['cid'].'&fid='.$class['fid'],
										'author'=>getglobal('username'),
										'authorid'=>getglobal('uid'),
										'dataline'=>dgmdate(TIMESTAMP),
										'fname'=>getstr($class['fname'],30),
										'comment'=>$comment['message'],
										);
						
						dzz_notification::notification_add($rpost['authorid'], 'corpus_comment_reply_'.$class[$cid], 'corpus_comment_reply', $notevars, 0,'dzz/corpus');
				}
			}
		}
	 }
}

?>
