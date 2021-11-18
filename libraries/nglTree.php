<?php

namespace nogal;

/** 
Tree format
$b = array (
	1 => 
	array (
	  'id' => '1',
	  'parent' => '0',
	  'name' => 'caninos',
	  '_children' => 
	  array (
		3 => 
		array (
		  'id' => '3',
		  'parent' => '1',
		  'name' => 'perro',
		),
		4 => 
		array (
		  'id' => '4',
		  'parent' => '1',
		  'name' => 'lobo',
		),
		5 => 
		array (
		  'id' => '5',
		  'parent' => '1',
		  'name' => 'coyote',
		),
	  ),
	),
	2 => 
	array (
	  'id' => '2',
	  'parent' => '0',
	  'name' => 'felinos',
	  '_children' => 
	  array (
		6 => 
		array (
		  'id' => '6',
		  'parent' => '2',
		  'name' => 'gato',
		),
		7 => 
		array (
		  'id' => '7',
		  'parent' => '2',
		  'name' => 'león',
		),
		8 => 
		array (
		  'id' => '8',
		  'parent' => '2',
		  'name' => 'tigre',
		),
		9 => 
		array (
		  'id' => '9',
		  'parent' => '2',
		  'name' => 'pantera',
		),
	  ),
	),
);

Flat format
$a = [];
$a[] = array("id"=>"1", "parent"=>"0", "name"=>"caninos");
$a[] = array("id"=>"2", "parent"=>"0", "name"=>"felinos");
$a[] = array("id"=>"3", "parent"=>"1", "name"=>"perro");
$a[] = array("id"=>"4", "parent"=>"1", "name"=>"lobo");
$a[] = array("id"=>"5", "parent"=>"1", "name"=>"coyote");
$a[] = array("id"=>"6", "parent"=>"2", "name"=>"gato");
$a[] = array("id"=>"7", "parent"=>"2", "name"=>"león");
$a[] = array("id"=>"8", "parent"=>"2", "name"=>"tigre");

print_r($ngl("tree")->loadflat($a)->node(array("id"=>"9", "parent"=>"2", "name"=>"pantera"))->show("name"));

} **/
class nglTree extends nglBranch implements inglBranch {
	
	private $aFlat;
	private $aGrouped;

	final protected function __declareArguments__() {
		$vArguments							= [];
		$vArguments["source"]				= ['$aValue'];
		$vArguments["id"]					= ['$mValue'];
		$vArguments["colparent"]			= ['$mValue', "parent"];
		$vArguments["colid"]				= ['$mValue', "id"];
		$vArguments["children"]				= ['$mValue', "_children"];
		$vArguments["column"]				= ['$mValue', "id"];
		$vArguments["nodedata"]				= ['$mValue'];
		$vArguments["separator"]			= ['$mValue', "/"];

		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes 						= [];
		$vAttributes["children"]			= null;
		$vAttributes["flat"]				= null;
		$vAttributes["id_column"]			= null;
		$vAttributes["parent_column"]		= null;
		$vAttributes["tree"]				= null;
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
	}

	public function loadflat() {
		list($aSource,$mParentColumn,$mIdColumn,$mChildren) = $this->getarguments("source,colparent,colid,children", \func_get_args());
		
		$this->attribute("children", $mChildren);
		$this->attribute("id_column", $mIdColumn);
		$this->attribute("parent_column", $mParentColumn);
		$this->Prepare($aSource);
		$this->attribute("flat", $this->aFlat);
		$this->Build();

		return $this;
	}

	public function loadtree() {
		list($aSource,$mParentColumn,$mIdColumn,$mChildren) = $this->getarguments("source,colparent,colid,children", \func_get_args());

		if(is_array($aSource) && !\count($aSource)) { $aSource = []; }
		$fBuilder = function($aTree) use (&$fBuilder, &$aFlat, $mChildren) {
			if(is_array($aTree) && \count($aTree)) {
				foreach($aTree as $aBranch) {
					$aChildren = null;
					if(isset($aBranch[$mChildren])) {
						$aChildren = $aBranch[$mChildren];
						unset($aBranch[$mChildren]);
					}
					
					$aFlat[] = $aBranch;
					if($aChildren!==null) { $fBuilder($aChildren); }
				}
			}
		};
		$aFlat = [];		
		$fBuilder($aSource);

		$this->attribute("children", $mChildren);
		$this->attribute("id_column", $mIdColumn);
		$this->attribute("parent_column", $mParentColumn);
		$this->attribute("tree", $aSource);

		$this->Prepare($aFlat);
		$this->attribute("flat", $this->aFlat);

		return $this;
	}

	private function Prepare($aSource) {
		$mIdColumn =  $this->attribute("id_column");
		$mParentColumn =  $this->attribute("parent_column");
		$mChildren =  $this->attribute("children_column");
		
		$aFlat = $aGrouped = [];
		foreach($aSource as $aSubArray) {
			$aFlat[$aSubArray[$mIdColumn]] = $aSubArray;
			$aGrouped[$aSubArray[$mParentColumn]][$aSubArray[$mIdColumn]] = $aSubArray;
		}

		$this->aFlat = $aFlat;
		$this->aGrouped = $aGrouped;
	}

	private function Build() {
		$mIndex = $this->attribute("id_column");
		$mChildren =  $this->attribute("children");

		$aGrouped = $this->aGrouped;
		$fBuilder = function($aSiblings) use (&$fBuilder, $aGrouped, $mIndex, $mChildren) {
			if(\is_array($aSiblings) && \count($aSiblings)) {
				foreach($aSiblings as $mKey => $aSibling) {
					$mCurrent = $aSibling[$mIndex];
					if(isset($aGrouped[$mCurrent])) {
						$aSibling[$mChildren] = $fBuilder($aGrouped[$mCurrent]);
					}
					$aSiblings[$mKey] = $aSibling;
				}
			}

			return $aSiblings;
		};

		\reset($aGrouped);
		$aTree = (\count($aGrouped)) ? $fBuilder(\current($aGrouped)) : [];

		$this->attribute("tree", $aTree);
		return $aTree;
	}
	
	private function NextId() {
		$aIndex = \array_keys($this->aFlat);
		\sort($aIndex, SORT_NATURAL);
		$nLast = \count($aIndex)-1;
		if($nLast<0) { $nLast = 0; }
		$mLast = (!empty($aIndex[$nLast])) ? $aIndex[$nLast] : 0;
		return (\is_numeric($mLast)) ? $mLast+1 : $mLast."0";
	}

	public function tree() {
		return $this->attribute("tree");
	}

	public function flat() {
		return $this->attribute("flat");
	}

	public function get() {
		list($nId) = $this->getarguments("id", \func_get_args());
		if(isset($this->aFlat[$nId])) {
			return $this->aFlat[$nId];
		}
		return null;	
	}

	public function parent() {
		list($nId) = $this->getarguments("id", \func_get_args());

		if(isset($this->aFlat[$nId])) {
			$mParent = $this->aFlat[$nId][$this->attribute("parent_column")];
			return (isset($this->aFlat[$mParent])) ? $this->aFlat[$mParent] : 0;
		}
		
		return null;
	}
	
	public function trace() {
		list($nId) = $this->getarguments("id", \func_get_args());
		$mIndex =  $this->attribute("parent_column");
		$aTrace = [];
		while($aParent=$this->get($nId)) {
			$nId = $aParent[$mIndex];
			$aTrace[] = $aParent;
		}
		return \array_reverse($aTrace);
	}
	
	public function children() {
		list($nId) = $this->getarguments("id", \func_get_args());
		$aChildren = $this->attribute("tree");
		if(!$nId) { return $aChildren; }
		$mIndex =  $this->attribute("id_column");
		$mChildren =  $this->attribute("children");
		$aTrace = $this->trace($nId);

		if(\is_array($aTrace) && \count($aTrace)) {
			foreach($aTrace as $aItem) {
				if(empty($aChildren[$aItem[$mIndex]][$mChildren])) { return []; }
				$aChildren = $aChildren[$aItem[$mIndex]][$mChildren];
			}
		}
		return $aChildren;
	}

	public function childrenChain() {
		list($nId,$sSeparator) = $this->getarguments("id,separator", \func_get_args());
		$aChildren = $this->children($nId);
		if(\is_array($aChildren) && \count($aChildren)) {
			$mIndex =  $this->attribute("id_column");
			$mChildren =  $this->attribute("children");
			$aChain = [];
			$this->ChildrenChainer($aChain, $aChildren, $mIndex, $mChildren);
			return ($sSeparator===null) ? $aChain : \implode($sSeparator, $aChain);
		}
		return [];
	}

	private function ChildrenChainer(&$aChain, $aData, $mIndex, $mChildren) {
		foreach($aData as $aChild) {
			$aChain[] = $aChild[$mIndex];
			if(!empty($aChild[$mChildren])) {
				$this->ChildrenChainer($aChain, $aChild[$mChildren], $mIndex, $mChildren);
			}
		}
		return $aChain;
	}
	
	/* si existe lo modifica, sino lo agrega */
	public function node() {
		list($aNode) = $this->getarguments("nodedata", \func_get_args());
		
		$mIdColumn = $this->attribute("id_column");
		$mParentColumn = $this->attribute("parent_column");

		if(!isset($aNode[$mIdColumn])) { $aNode[$mIdColumn] = $this->NextId(); }
		if(!isset($aNode[$mParentColumn])) {
			$aNode[$mParentColumn] = 0;
		} else {
			$aParentTrace = $this->trace($aNode[$mParentColumn]);
			foreach($aParentTrace as $aTrace) {
				if($aTrace[$mIdColumn]==$aNode[$mIdColumn]) {
					$aNode[$mParentColumn] = $aTrace[$mParentColumn];
					break;
				}
			}
		}

		$this->aFlat[$aNode[$mIdColumn]] = $aNode;

		$this->Prepare($this->aFlat);
		$this->attribute("flat", $this->aFlat);
		$this->Build();
		
		return $this;
	}

	public function parentsChain() {
		list($nId,$sColumn,$sSeparator) = $this->getarguments("id,column,separator", \func_get_args());
		$aPaths = $aPath = [];
		foreach($this->trace($nId) as $aBranch) {
			$aPath[] = $aBranch[$sColumn];
		}
		return ($sSeparator===null) ? $aPath : \implode($sSeparator, $aPath);
	}

	public function paths() {
		list($sColumn,$sSeparator) = $this->getarguments("column,separator", \func_get_args());
		
		$aTree = $this->attribute("tree");
		$mIdColumn = $this->attribute("id_column");
		$mChildren = $this->attribute("children");

		$aPaths = $aPath = [];
		$fBuilder = function($aTree) use (&$fBuilder, &$aPaths, &$aPath, $mChildren, $sColumn, $mIdColumn, $sSeparator) {
			foreach($aTree as $aBranch) {
				\array_push($aPath, $aBranch[$sColumn]);
				$aPaths[$aBranch[$mIdColumn]] = \implode($sSeparator, $aPath);
				if(isset($aBranch[$mChildren])) {
					$fBuilder($aBranch[$mChildren]);
				} else {
					\array_pop($aPath);
				}
			}
			\array_pop($aPath);
		};
		$fBuilder($aTree);
		
		\natsort($aPaths);
		return $aPaths;
	}
	
	public function show() {
		list($sColumn) = $this->getarguments("column", \func_get_args());
		
		$aTree = $this->attribute("tree");
		$mChildren = $this->attribute("children");

		$aPrint = self::call()->treeWalk($aTree, function($aNode, $nLevel, $bFirst, $bLast) use ($sColumn, $mChildren) {
				$sOutput  = "";
				$sOutput .= ($nLevel) ? \str_repeat("│   ", $nLevel) : "";
				$sOutput .= ($bLast) ? "└─── " : "├─── ";
				$sOutput .= $aNode[$sColumn];
				$sOutput .= "\n";
				return $sOutput;
			}
		);

		return \implode($aPrint);
	}
}

?>