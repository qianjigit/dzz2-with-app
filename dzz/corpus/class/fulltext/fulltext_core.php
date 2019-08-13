<?php
class fulltext_core
{
 	protected $strictMatching = true;
	private static $mMinSearchLength;
	protected $UniqPrefix='\x7fUNIQdzzcorpus';
	
	function parseQuery( $filteredText ) {

		$lc = $this->legalSearchChars(); // Minus format chars
		$searchon = '';

		# @todo FIXME: This doesn't handle parenthetical expressions.
		$m = array();
		if ( preg_match_all( '/([-+<>~]?)(([' . $lc . ']+)(\*?)|"[^"]*")/',
				$filteredText, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $bits ) {
				list( /* all */, $modifier, $term, $nonQuoted, $wildcard ) = $bits;

				if ( $nonQuoted != '' ) {
					$term = $nonQuoted;
					$quote = '';
				} else {
					$term = str_replace( '"', '', $term );
					$quote = '"';
				}

				if ( $searchon !== '' ) {
					$searchon .= ' ';
				}
				if ( $this->strictMatching && ( $modifier == '' ) ) {
					// If we leave this out, boolean op defaults to OR which is rarely helpful.
					$modifier = '+';
				}
				
				// Some languages such as Serbian store the input form in the search index,
				// so we may need to search for matches in multiple writing system variants.
				//$convertedVariants = $wgContLang->autoConvertToAllVariants( $term );
				/*if ( is_array( $convertedVariants ) ) {
					$variants = array_unique( array_values( $convertedVariants ) );
				} else {*/
					$variants = array( $term );
				//}

				// The low-level search index does some processing on input to work
				// around problems with minimum lengths and encoding in MySQL's
				// fulltext engine.
				// For Chinese this also inserts spaces between adjacent Han characters.
				//$strippedVariants = array_map("self::convertDoubleWidth",$variants );
				$strippedVariants = array_map(
					array( $this, 'normalizeForSearch' ),
					$variants );
				
				// Some languages such as Chinese force all variants to a canonical
				// form when stripping to the low-level search index, so to be sure
				// let's check our variants list for unique items after stripping.
				$strippedVariants = array_unique( $strippedVariants );
				$searchon .= $modifier;
				if ( count( $strippedVariants ) > 1 ) {
					$searchon .= '(';
				}
				foreach ( $strippedVariants as $stripped ) {
					$stripped = $this->normalizeText( $stripped );
					if ( $nonQuoted && strpos( $stripped, ' ' ) !== false ) {
						// Hack for Chinese: we need to toss in quotes for
						// multiple-character phrases since normalizeForSearch()
						// added spaces between them to make word breaks.
						$stripped = '"' . trim( $stripped ) . '"';
					}
					$searchon .= "$quote$stripped$quote$wildcard ";
				}
				if ( count( $strippedVariants ) > 1 ) {
					$searchon .= ')';
				}

				// Match individual terms or quoted phrase in result highlighting...
				// Note that variants will be introduced in a later stage for highlighting!
				//$regexp = $this->regexTerm( $term, $wildcard );
				//$this->searchTerms[] = $regexp;
			}
		} 

		//$searchon = $this->db->addQuotes( $searchon );
		//$field = $this->getIndexField( $fulltext );
		return $searchon;
	}
	public function normalizeForSearch( $s ) {

		// always convert to zh-hans before indexing. it should be
		// better to use zh-hans for search, since conversion from
		// Traditional to Simplified is less ambiguous than the
		// other way around
		// LanguageZh_hans::normalizeForSearch
		$s = self::convertDoubleWidth( $s );
		$s = trim( $s );
		$s = $this->segmentByWord( $s );
		
		return $s;

	}
	public function normalizeText( $string ) {
		

		$out = $this->segmentByWord( $string );

		// MySQL fulltext index doesn't grok utf-8, so we
		// need to fold cases and convert to hex
		$out = preg_replace_callback(
			"/([\\xc0-\\xff][\\x80-\\xbf]*)/",
			array( $this, 'stripForSearchCallback' ),
			$this->lc( $out ) );

		// And to add insult to injury, the default indexing
		// ignores short words... Pad them so we can pass them
		// through without reconfiguring the server...
		$minLength = $this->minSearchLength();
		if ( $minLength > 1 ) {
			$n = $minLength - 1;
			$out = preg_replace(
				"/\b(\w{1,$n})\b/",
				"$1u800",
				$out );
		}

		// Periods within things like hostnames and IP addresses
		// are also important -- we want a search for "example.com"
		// or "192.168.1.1" to work sanely.
		//
		// MySQL's search seems to ignore them, so you'd match on
		// "example.wikipedia.com" and "192.168.83.1" as well.
		$out = preg_replace(
			"/(\w)\.(\w|\*)/u",
			"$1u82e$2",
			$out );

		return $out;
	}
	
	public function segmentByWord( $string ) {
		$reg = "/([\\xc0-\\xff][\\x80-\\xbf]*)/";
		$s = self::insertSpace( $string, $reg );
		return $s;
	}
	protected function stripForSearchCallback( $matches ) {
		return 'u8' . bin2hex( $matches[1] );
	}
	protected function lc( $str, $first = false ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			if ( $first ) {
				if ( $this->isMultibyte( $str ) ) {
					return mb_strtolower( mb_substr( $str, 0, 1 ) ) . mb_substr( $str, 1 );
				} else {
					return strtolower( substr( $str, 0, 1 ) ) . substr( $str, 1 );
				}
			} else {
				return $this->isMultibyte( $str ) ? mb_strtolower( $str ) : strtolower( $str );
			}
		} else {
			return $str;
		}
	}
	public function isMultibyte( $str ) {
		return (bool)preg_match( '/[\x80-\xff]/', $str );
	}
	protected static function insertSpace( $string, $pattern ) {
		$string = preg_replace( $pattern, " $1 ", $string );
		$string = preg_replace( '/ +/', ' ', $string );
		return $string;
	}
	protected function lcCallback( $matches ) {
		list( , $wikiLowerChars ) = self::getCaseMaps();
		return strtr( $matches[1], $wikiLowerChars );
	}
	public static function legalSearchChars() {
		return "A-Za-z_'.0-9\\x80-\\xFF\\-";
	}
	protected static function convertDoubleWidth( $string ) {
		static $full = null;
		static $half = null;

		if ( $full === null ) {
			$fullWidth = "０１２３４５６７８９ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ";
			$halfWidth = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
			$full = str_split( $fullWidth, 3 );
			$half = str_split( $halfWidth );
		}

		$string = str_replace( $full, $half, $string );
		return $string;
	}
	protected function minSearchLength() {
		if ( is_null( self::$mMinSearchLength ) ) {
			$sql = "SHOW GLOBAL VARIABLES LIKE 'ft\\_min\\_word\\_len'";
			$row = DB::fetch_all($sql);
			
			if ( $row && $row->Variable_name == 'ft_min_word_len' ) {
				self::$mMinSearchLength = intval( $row->Value );
			} else {
				self::$mMinSearchLength = 0;
			}
		}
		return self::$mMinSearchLength;
	}
	
	public function translate( $text, $variant='zh-cn' ) {
		// If $text is empty or only includes spaces, do nothing
		// Otherwise translate it
		if ( trim( $text ) ) {
			$this->loadTables();
			$text = $this->mTables[$variant]->replace( $text );
		}
		return $text;
	}
	public function loadTables( $fromCache = true ) {

		if ( $this->mTablesLoaded ) {
			return;
		}

		$this->mTablesLoaded = true;
		$this->mTables = false;
		
		if ( !$this->mTables ) {
			// not in cache, or we need a fresh reload.
			// We will first load the default tables
			// then update them using things in MediaWiki:Conversiontable/*
			$this->loadDefaultTables();
			$this->postLoadTables();
		}
	}
	public function postLoadTables() {
		$this->mTables['zh-cn']->setArray(
			$this->mTables['zh-cn']->getArray() + $this->mTables['zh-hans']->getArray()
		);
		$this->mTables['zh-hk']->setArray(
			$this->mTables['zh-hk']->getArray() + $this->mTables['zh-hant']->getArray()
		);
		$this->mTables['zh-mo']->setArray(
			$this->mTables['zh-mo']->getArray() + $this->mTables['zh-hant']->getArray()
		);
		$this->mTables['zh-my']->setArray(
			$this->mTables['zh-my']->getArray() + $this->mTables['zh-hans']->getArray()
		);
		$this->mTables['zh-sg']->setArray(
			$this->mTables['zh-sg']->getArray() + $this->mTables['zh-hans']->getArray()
		);
		$this->mTables['zh-tw']->setArray(
			$this->mTables['zh-tw']->getArray() + $this->mTables['zh-hant']->getArray()
		);
	}
	public function loadDefaultTables() {
		require __DIR__ . "./ZhConversion.php";
		$this->mTables = array(
			'zh-hans' => new fulltext_ReplacementArray( $zh2Hans ),
			'zh-hant' => new fulltext_ReplacementArray( $zh2Hant ),
			'zh-cn' => new fulltext_ReplacementArray( $zh2CN ),
			'zh-hk' => new fulltext_ReplacementArray( $zh2HK ),
			'zh-mo' => new fulltext_ReplacementArray( $zh2HK ),
			'zh-my' => new fulltext_ReplacementArray( $zh2CN ),
			'zh-sg' => new fulltext_ReplacementArray( $zh2CN ),
			'zh-tw' => new fulltext_ReplacementArray( $zh2TW ),
			'zh' => new fulltext_ReplacementArray
		);
	}
 
}
