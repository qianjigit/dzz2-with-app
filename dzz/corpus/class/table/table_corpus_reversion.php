<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */

if(!defined('IN_DZZ')) {
	exit('Access Denied');
}

class table_corpus_reversion extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_reversion';
		$this->_pk    = 'revid';

		parent::__construct();
	}
	public function fetch_by_revid($revid){
		return parent::fetch($revid);
	}
	public function reversion($fid,$revid=0,$cid=0){ //使用此版本
		$vers=self::fetch_all_by_fid($fid);
		$vers=array_reverse($vers);	
		$newest=$vers[count($vers)-1];
		//if($revid==$newest['revid']) return $newest['version'];//已经是最新版了
		$i=0;
		foreach($vers as $key=> $value){
			if($value['revid']==$revid) continue;
			$i++;
			$value['version']=$i;
			parent::update($value['revid'],array('version'=>$i));
		}
		
		parent::update($revid,array('version'=>$i+1));
		C::t('corpus_class')->update($fid,array('revid'=>$revid,'version'=>$i+1));
		C::t('corpus')->increase($cid,array('updatetime'=>TIMESTAMP));
		return $i+1;
		
	}
	public function insert_by_parent($arr){
		return parent::insert($arr,1);
	}
	public function insert($arr,$new=0){
		//先获取最新版本,没有的话新插入
		$newest=array();
		if($newest=DB::fetch_first("select * from %t where fid=%d order by version DESC limit 1",array($this->_table,$arr['fid']))){
			if($new){
				$arr['version']=$newest['version']+1;
				
				$attachs=array();
				if($arr['attachs']) {
					$attachs=$arr['attachs'];
					$arr['attachs']=implode(',',$attachs);
				}else{
					$arr['attachs']='';
				}
				$content=$arr['content'];
				$arr['md5']=md5($content);
				unset($arr['content']);
				if($arr['revid']=parent::insert($arr,1)){
					$arr['edited']=1;
					$table=C::t('corpus_class')->getTableByFid($arr['fid']);
					$cid=DB::result_first("select cid from %t where fid=%d",array($table,$arr['fid']));
					C::t('corpus')->increase($cid,array('updatetime'=>array(TIMESTAMP)));
					if($ret=saveContent($content,$cid,$arr['fid'],$arr['revid'])){
						$content=$ret['path'];
						$remoteid=$ret['remoteid'];
						parent::update($arr['revid'],array('content'=>$content,'remoteid'=>$remoteid,'dateline'=>TIMESTAMP));
					}else{
						parent::update($arr['revid'],array('content'=>$content,'remoteid'=>0,'dateline'=>TIMESTAMP));
					}
					$attachs[]=$arr['aid'];
					C::t('attachment')->addcopy_by_aid($attachs);
					
				}
			}else{
				$oldattachs=$newest['attachs']?explode(',',$newest['attachs']):array();
				
				$attachs=array();
				if($arr['attachs']) {
					$attachs=$arr['attachs'];
					$arr['attachs']=implode(',',$arr['attachs']);
				}else{
					$arr['attachs']='';
				}
				$content=$arr['content'];
				$arr['md5']=md5($content);
				unset($arr['content']);
				if(parent::update($newest['revid'],$arr)){
					$arr['edited']=1;
					$table=C::t('corpus_class')->getTableByFid($arr['fid']);
					$cid=DB::result_first("select cid from %t where fid=%d",array($table,$arr['fid']));
					C::t('corpus')->increase($cid,array('updatetime'=>array(TIMESTAMP)));
					if($newest['remoteid']){
						if($ret=IO::setFileContent($newest['content'],$content)){
							if($ret['error']){
								parent::update($newest['revid'],array('content'=>$content,'remoteid'=>0,'dateline'=>TIMESTAMP));
							}
							parent::update($newest['revid'],array('dateline'=>TIMESTAMP));
						}else{
							parent::update($newest['revid'],array('content'=>$content,'remoteid'=>0,'dateline'=>TIMESTAMP));
						}
					}else{
						if($ret=saveContent($content,$cid,$arr['fid'],$newest['revid'])){
							$path=$ret['path'];
							$remoteid=$ret['remoteid'];
							parent::update($newest['revid'],array('content'=>$path,'remoteid'=>$remoteid,'dateline'=>TIMESTAMP));
						}else{
							parent::update($newest['revid'],array('content'=>$content,'remoteid'=>0,'dateline'=>TIMESTAMP));
						}
					}
					
					
					$delaids=array_diff($oldattachs,$attachs);
					C::t('attachment')->addcopy_by_aid($delaids,-1);
					$insertaids=array_diff($attachs,$oldattachs);
					C::t('attachment')->addcopy_by_aid($insertaids);
				}
				$arr['version']=$newest['version'];
				$arr['revid']=$newest['revid'];
			}
		}else{
			$arr['version']=1;
			$attachs=array();
			if($arr['attachs']) {
				$attachs=$arr['attachs'];
				$arr['attachs']=implode(',',$arr['attachs']);
			}else{
					$arr['attachs']='';
				}
			$content=$arr['content'];
			$arr['md5']=md5($content);
			unset($arr['content']);
			if($arr['revid']=parent::insert($arr,1)){
				$arr['edited']=1;
				if($attachs) C::t('attachment')->addcopy_by_aid($attachs);
				$table=C::t('corpus_class')->getTableByFid($arr['fid']);
				$cid=DB::result_first("select cid from %t where fid=%d",array($table,$arr['fid']));
				C::t('corpus')->increase($cid,array('updatetime'=>array(TIMESTAMP)));
				if($ret=saveContent($content,$cid,$arr['fid'],$arr['revid'])){
					$content=$ret['path'];
					$remoteid=$ret['remoteid'];
					parent::update($arr['revid'],array('content'=>$content,'remoteid'=>$remoteid,'dateline'=>TIMESTAMP));
				}else{
					parent::update($arr['revid'],array('content'=>$content,'remoteid'=>0,'dateline'=>TIMESTAMP));
				}
			}
		}
		unset($content);
		if($arr['revid']) return $arr;
		else return false;
	}
	public function delete_by_version($fid,$revid){
		$vers=self::fetch_all_by_fid($fid);
		$class=C::t('corpus_class')->fetch($fid);
		if(self::delete($revid)){
			
			$vers=array_reverse($vers);	
			$i=0;
			foreach($vers as $key=> $value){
				if($revid==$value['revid']) continue;
				$i++;
				$value['version']=$i;
				parent::update($value['revid'],array('version'=>$i));
			}
			if($class['verid']==$revid){
				$setarr=array('verid'=>$value['revid'],
							  'version'=>$value['version']
							 );
				C::t('corpus_class')->update($fid,$setarr);
				C::t('corpus')->increase($class['cid'],array('updatetime'=>array(TIMESTAMP)));
			}
			return $value['version'];
		}else{
			return false;
		}
	}
    public function delete($revid){
		$data=parent::fetch($revid);
		if($ret=parent::delete($revid)){
			if($data['attachs']){
				$attachs=explode(',',$data['attachs']);
				foreach($attachs as $aid){
					C::t('attachment')->delete_by_aid($aid);
				}
			}
			if($data['remoteid']){
				 try{IO::delete($data['content']);}catch(Exception $e){}
			}
		}
	  	return $ret;
	}
	 public function delete_by_fid($fids){
		if(!is_array($fids)) $fids=array($fids);
		$paths=$attachs=array();
		foreach(DB::fetch_all("select revid,attachs,remoteid,content from %t where fid IN (%n) ",array($this->_table,$fids)) as $value){
		   if($value['attachs']) $attachs=array_merge($attachs,explode(',',$value['attachs']));
		    if($value['remoteid']) $paths[]=$value['content'];
		    $revids[]=$value['revid'];
	   }
	   if($ret=parent::delete($revids)){
		   C::t('corpus_fullcontent')->delete($fids);//删除全文索引库
		   foreach($attachs as $aid){
				C::t('attachment')->delete_by_aid($aid);
			} 
			foreach($paths as $path){//删除文件
				 try{IO::delete($path);}catch(Exception $e){}
			}
	   }
	   return $ret;
	}
	public function fetch_all_by_fid($fid){
		$data=array();
		//return DB::fetch_all("select * from %t where fid= %d order by version DESC",array($this->_table,$fid));
		foreach(DB::fetch_all("select * from %t where fid= %d order by version DESC",array($this->_table,$fid)) as $value){
			//$attach=C::t('attachment')->fetch($value['aid']);
			$data[$value['version']]=$value;
		}
		return $data;
	}
}

?>
