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
class table_corpus_class extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_class';
		$this->_pk    = 'fid';
		
		if(is_file(DZZ_ROOT.'./dzz/corpus/class/table/table_corpus_class_index.php')){
			$this->_split=1;
			$this->createIndexTable();
		}else{
			$this->_split=0;
		}
		parent::__construct();
	}
	public function createIndexTable(){
		global $_G;
		if(!$this->_split) return false;
		$auto_increment=DB::result_first("select max(fid) from %t",array($this->_table));
		if($auto_increment<1) $auto_increment=1;
		$tablepre = $_G['config']['db'][1]['tablepre'];
		//如果表不存在，创建此表
		DB::query("CREATE TABLE IF NOT EXISTS ".$tablepre.$this->_table."_index ("
  					 ."fid int(10) NOT NULL AUTO_INCREMENT,"
					 ."cid int(10) NOT NULL DEFAULT '0',"
					 ."tableid smallint(6) NOT NULL DEFAULT '0',"
					 ."PRIMARY KEY (fid),"
					 ."KEY cid (cid)"
					 .") ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=".$auto_increment." ;"
					
				);
		 return true;
	}
	public function getTableByFid($fid){
		if(!$this->_split) return $this->_table;
		if($tableid=C::t($this->_table.'_index')->getTableidByFid($fid)) return $this->_table.'_'.$tableid;
		else return $this->_table;
	}
	public function getTableByCid($cid,$force=false){
		if(!$this->_split) return $this->_table;
		if($tableid=C::t($this->_table.'_index')->getTableidByCid($cid,$force)) return $this->_table.'_'.$tableid;
		else return $this->_table;
	}
	
	public function fetch($fid){
		$table=self::getTableByFid($fid);
		return DB::fetch_first("select * from %t where fid=%d",array($table,$fid));
	}
	public function update($fids,$arr){
		$fids=(array)$fids;
		$table=self::getTableByFid($fids[0]);
		return DB::update($table,$arr,"fid IN (".dimplode($fids).")");
	}
	public function delete($fids){
		$fids=(array)$fids;
		$table=self::getTableByFid($fids[0]);
		return DB::delete($table,"fid IN (".dimplode($fids).")");
	}
	public function rename_by_fid($fid,$name){
		$table=self::getTableByFid($fid);
		$data=self::fetch($fid);
		if($return=self::update($fid,array('fname'=>(strip_tags($name))))){
		//产生事件
			C::t('corpus')->increase($data['cid'],array('updatetime'=>array(TIMESTAMP)));
			$event =array('uid'=>getglobal('uid'),
						  'username'=>getglobal('username'),
						  'body_template'=>'corpus_rename_'.($data['type']=='file'?'doc':'dir'),
						  'body_data'=>serialize(array('cid'=>$data['cid'],'ofname'=>$data['fname'],'fid'=>$fid,'fname'=>$name)),
						  'dateline'=>TIMESTAMP,
						  'bz'=>'corpus_'.$data['cid'],
						  );
				C::t('corpus_event')->insert($event);
			return $return;
		}
	}
	public function insert_default_by_cid($cid){
		$setarr=array('fname'=>'新文档',
					  'cid'=>$cid,
					  'pfid'=>0,
					  'type'=>'file',
					  'uid'=>getglobal('uid'),
					  'username'=>getglobal('username'),
					  'disp'=>0,
					  'dateline'=>TIMESTAMP
					  );
		if($ret=self::insert($setarr)){
			C::t('corpus')->increase($data['cid'],array('updatetime'=>array(TIMESTAMP),'documents'=>1));
		}
		return $ret;
	}
	public function insert($setarr,$event=1){
		if($this->_split){
			if($tableid=C::t($this->_table.'_index')->getTableidByCid($setarr['cid'],true)){
				$table=$this->_table.'_'.$tableid;
				if(!$fid=C::t($this->_table.'_index')->insert(array('cid'=>$setarr['cid'],'tableid'=>$tableid))){
					return false;
				}else{
					$setarr['fid']=$fid;
				}
			}else{
				$maxfid=max(DB::result_first("SELECT max(fid) FROM %t ",array($this->_table)),DB::result_first("SELECT max(fid) FROM %t ",array($this->_table.'_index')));
				$fid=$setarr['fid']=$maxfid+1;
				$table=$this->_table;
			}
		}else{
			$table=$this->_table;
		}
		
		$setarr['fname']=htmlspecialchars(strip_tags($setarr['fname']));
		if(DB::insert($table,$setarr)){
			C::t('corpus')->increase($setarr['cid'],array('updatetime'=>array(TIMESTAMP),'documents'=>1));
			if(DB::query("update %t set disp=disp+1 where cid=%d and pfid=%d and deletetime<1 and disp>=%d and fid!=%d",array($table,$setarr['cid'],$setarr['pfid'],$setarr['disp'],$fid))){
				self::sortDisplayByPfid($setarr['cid'],$setarr['pfid']);
			}
			if($event){
				//产生事件
				$event =array(    'uid'=>getglobal('uid'),
								  'username'=>getglobal('username'),
								  'body_template'=>'corpus_create_'.($setarr['type']=='file'?'doc':'dir'),
								  'body_data'=>serialize(array('cid'=>$setarr['cid'],'fid'=>$fid,'fname'=>$setarr['fname'])),
								  'dateline'=>TIMESTAMP,
								  'bz'=>'corpus_'.$setarr['cid'],
							  );
				C::t('corpus_event')->insert($event);
			}
		}
		return $fid;
	}
	public function fetch_all_by_deletetime($cid,$limit,$keyword,$iscount){
		$limitsql='';
		if($limit){
			$limit=explode('-',$limit);
			if(count($limit)>1){
				$limitsql.=" limit ".intval($limit[0]).",".intval($limit[1]);
			}else{
				$limitsql.=" limit ".intval($limit[0]);
			}
		}
		$table=self::getTableByCid($cid);
		$parameter=array($table,$cid);
		$ssql='';
		if(!empty($keyword)) {
			$parameter[] = '%'.$keyword.'%';
			$ssql= " and fname LIKE %s";
		}
		if($iscount) return DB::result_first("select COUNT(*) from %t where cid=%d and deletetime>0 $ssql ",$parameter);
		$data=array();
		$deleteuids=array();
		foreach(DB::fetch_all("select * from %t where cid=%d and deletetime>0 $ssql order by deletetime DESC $limitsql ",$parameter) as $value){
			$deleteuids[$value['fid']]=$value['deleteuid'];
			$data[$value['fid']]=$value;
		}
		$user=C::t('user')->fetch_all($deleteuids);
		foreach($deleteuids as $key =>$uid){
			if($data[$key])$data[$key]['deleteusername']=$user[$uid]['username'];
		}
		return $data;
	}
	public function fetch_all_by_pfid($cid,$pfid){
		$table=self::getTableByCid($cid);
		return DB::fetch_all("select * from %t where cid=%d and pfid=%d and deletetime<1  order by disp ASC",array($table,$cid,$pfid));
	}
	private function getTreeData($cid,$fid=0){
		static $data=array();
		foreach(self::fetch_all_by_pfid($cid,$fid) as $value){
			$data[]=$value;
			self::getTreeData($cid,$value['fid']);
		}
		return $data;
	}
	public function fetch_all_by_cid($cid){
		return self::getTreeData($cid);
	}
	public function sortDisplayByPfid($cid,$pfid){
		$i=0;
		$table=self::getTableByCid($cid);
		foreach(DB::fetch_all("select fid from %t where cid=%d and pfid=%d  and deletetime<1 order by disp",array($table,$cid,$pfid)) as $key => $value){
			DB::update($table,array('disp'=>$i),"fid='{$value['fid']}'");
			$i++;
		};
		
	}
	public function setDispByFid($fid,$pfid,$position){
		$oclass=self::fetch($fid);
		$table=self::getTableByFid($fid);
		DB::query("update %t set disp=disp-1 where cid=%d and pfid=%d and deletetime<1 and disp>=%d",array($table,$oclass['cid'],$oclass['pfid'],$oclass['disp']));
		DB::query("update %t set disp=disp+1 where cid=%d and pfid=%d and deletetime<1 and disp>=%d",array($table,$oclass['cid'],$pfid,$position));
		
		if($ret=DB::update($table,array('pfid'=>$pfid,'disp'=>$position),"fid='{$fid}'")){
			self::sortDisplayByPfid($oclass['cid'],$pfid);
			C::t('corpus')->increase($oclass['cid'],array('updatetime'=>array(TIMESTAMP)));
		}
		return $ret;
	}
	public function delete_by_fid($fid,$force=false){
		$class=self::fetch($fid);
		$table=self::getTableByFid($fid);
		if($force){
			return self::delete_permanent_by_pfid($fid);
		}elseif($class['deletetime']>0){
			return self::delete_permanent_by_pfid($fid);
		}else{
			/*if($class['pfid']<1 && !DB::result_first("select COUNT(*) from %t where cid=%d and fid!=%d and deletetime<1",array($this->_table,$class['cid'],$class['fid']))){
				return false;
			}*/
			if($ret=DB::update($table,array('deletetime'=>TIMESTAMP,'deleteuid'=>getglobal('uid')),"fid='{$fid}'")){
				
				C::t('corpus')->increase($class['cid'],array('updatetime'=>array(TIMESTAMP),'documents'=>-1));
				DB::query("update %t set disp=disp-1 where cid=%d and pfid=%d and disp>%d  and deletetime<1",array($table,$class['cid'],$class['pfid'],$class['disp']));
			}
			//重新排序disp
			self::sortDisplayByPfid($class['cid'],$class['pfid']);
			//产生事件
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							 
							  'body_template'=>'corpus_delete_'.($class['type']=='file'?'doc':'dir'),
							  'body_data'=>serialize(array('cid'=>$class['cid'],'fid'=>$fid,'fname'=>$class['fname'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$class['cid'],
						  );
			C::t('corpus_event')->insert($event);
			//通知文档原作者
			$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
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
							);
				
					$action='corpus_doc_delete';
					$type='corpus_doc_delete_'.$class['cid'];
				
				dzz_notification::notification_add($class['uid'], $type, $action, $notevars, 0,'dzz/corpus');
			}
			return $ret;
		}
	}
	public function recylce_empty_by_cid($cid){
		foreach(self::fetch_all_by_deletetime($cid) as $value){
			self::delete_by_fid($value['fid']);
		}
		return true;
	}
	public function restore_by_fid($fid){
		$table=self::getTableByFid($fid);
		$fids=self::getParentByFid($fid);
		if($fids && $return=DB::update($table,array('deletetime'=>0,'deleteuid'=>0),"fid IN(".dimplode($fids).")")){
			
			//产生事件
			foreach($fids as $value){
				$class=self::fetch($value);
				C::t('corpus')->increase($class['cid'],array('updatetime'=>array(TIMESTAMP),'documents'=>1));
				$event=array(    
							  'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_restore_'.($class['type']=='file'?'doc':'dir'),
							  'body_data'=>serialize(array('cid'=>$class['cid'],'fid'=>$class['fid'],'fname'=>$class['fname'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$class['cid'],
							 );
					C::t('corpus_event')->insert($event);
			}
		}
		return $return;
	}
	public function delete_permanent_by_pfid($pfid,$flag=0){
		
		if(!$class=self::fetch($pfid)) return false;
		if($class['type']=='folder'){
			foreach(self::fetch_all_by_pfid($pfid,$class['cid']) as $value){
				self::delete_permanent_by_pfid($value['fid'],$class['cid'],1);
			}
		}
		print_r($class);
		if($class['fid']){
			C::t('corpus_reversion')->delete_by_fid($class['fid'],true);
		}
		//删除评论
		 C::t('comment')->delete_by_id_idtype($class['fid'],'corpus');
		 //删除相关下载资源
		 C::t('corpus_convert')->delete_by_fid($class['fid']);
		 if($flag==0){
			 $table=self::getTableByCid($class['cid']);
			
			DB::query("update %t set disp=disp-1 where cid=%d and pfid=%d and disp>%d and deletetime<1",array($table,$class['cid'],$class['pfid'],$class['disp']));
			//C::t('corpus')->increase($class['cid'],array('updatetime'=>array(TIMESTAMP)));
		}
		
		return self::delete($pfid);
	}
	public function getParentByFid($fid,$fids=array()){
		if($class=self::fetch($fid)){
			$fids[]=$fid;
			if($class['pfid']>0){
				$fids=self::getParentByFid($class['pfid'],$fids);
			}
		}
		return $fids;
	}
	public function fetch_files_by_sort($cid,$pfid=0){
		static $fids=array();
		foreach(self::fetch_all_by_pfid($cid,$pfid) as $value){
			$fids[]=$value['fid'];
			if($value['type']=='folder'){
				self::fetch_files_by_sort($cid,$value['fid']);
			}
		}
		return $fids;
	}
//获取下个兄弟节点
	public function get_nextfid_by_pfid($pfid){
		$table=self::getTableByFid($pfid);
		$class=self::fetch($pfid);
		if($nextfid=DB::result_first("select fid from %t where cid=%d and fid!=%d and pfid=%d and disp>=%d and deletetime<1 order by disp",array($table,$class['cid'],$class['fid'],$class['pfid'],$class['disp']))){
			return $nextfid;
		}elseif($class['pfid']>0){
			return self::get_nextfid_by_pfid($class['pfid']);
		}else{
			return 0;
		}
	}
	//获取下个页的fid
	public function get_pn_next($fid,$cid){
		$table=self::getTableByCid($cid);
		//有下级，直接返回第一个下级fid
		if($nextfid=DB::result_first("select fid from %t where cid=%d and pfid=%d order by disp",array($table,$cid,$fid))){
			return intval($nextfid);
		}
		//没有下级，获取它的兄弟节点	
		$class=self::fetch($fid);
		if($nextfid=DB::result_first("select fid from %t where cid=%d and fid!=%d and pfid=%d and disp>=%d and deletetime<1 order by disp",array($table,$class['cid'],$class['fid'],$class['pfid'],$class['disp']))){
			return $nextfid;
		}
		//没有兄弟节点，取父级节点的兄弟节点
		$nextfid=self::get_nextfid_by_pfid($class['pfid']);
		return intval($nextfid);
	}
	
	//获取下个页的fid
	public function get_pn_prev($fid,$cid){
		
		//有前面的兄弟节点	
		$class=self::fetch($fid);
		$table=self::getTableByCid($cid);
		if($prevfid=DB::result_first("select fid from %t where cid=%d and fid!=%d and pfid=%d and disp<=%d and deletetime<1 order by disp DESC",array($table,$class['cid'],$class['fid'],$class['pfid'],$class['disp']))){
			//返回此节点的最后一个fid
			return self::get_last_by_pfid($prevfid,$cid);
		}
		
		//如果没有前面的兄弟节点，直接返回父节点fid,如果到文章头，直接返回'cover';
		return $class['pfid']?$class['pfid']:'cover';
	}
	public function get_last_by_pfid($fid,$cid){
		$table=self::getTableByCid($cid);
		if($lastfid=DB::result_first("select fid from %t where cid=%d and pfid=%d and deletetime<1 order by disp DESC",array($table,$cid,$fid))){
			return self::get_last_by_pfid($lastfid,$cid);
		}else{
			return $fid;
		}
	}
	public function fetch_pn_by_fid($fid,$cid){
		$prevfid=self::get_pn_prev($fid,$cid);
		$nextfid=self::get_pn_next($fid,$cid);
		//print_r(array('prev'=>$prevfid?$prevfid:'cover','next'=>$nextfid));exit('ddd');
		return array('prev'=>$prevfid?$prevfid:'cover','next'=>$nextfid);
	}
	public function delete_by_cid($cid){
		$fids=array();
		$table=self::getTableByCid($cid);
		foreach(DB::fetch_all("select fid from %t where cid=%d ",array($table,$cid)) as $value){
			$fids[]=$value['fid'];
		}
		//删除评论
		C::t('comment')->delete_by_id_idtype($fids,'corpus');
		//删除reversion
		C::t('corpus_reversion')->delete_by_fid($fids);
		
		//删除相关下载资源
		C::t('corpus_convert')->delete_by_cid($cid);
		return DB::delete($table,"cid='{$cid}'");
	}
	
	public function fetch_count_by_pfid($pfid){
		$table=self::getTableByFid($pfid);
		return DB::result_first("select COUNT(*) from %t where pfid=%d and deletetime<1",array($table,$pfid));
	}
	public function fetch_allcount($condition){
		$tableids=C::t($this->_table.'_index')->getTableids();
		$count=DB::result_first("select COUNT(*) from %t where $condition",array($this->_table));
		foreach($tableids as $value){
			$count+=DB::result_first("select COUNT(*) from %t where $condition",array($this->_table.'_'.$value['tableid']));
		}
		return $count;
	}
	public function fetch_all($sql,$param){
		
	}
}

?>
