<?php
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}

function build_cache_corpus_setting() {
	$data=array();
	$data=C::t('#corpus#corpus_setting')->fetch_all();
	savecache('corpus:setting', $data);
}

?>