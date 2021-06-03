<?php

namespace nogal;

/** CLASS {
	"name" : "nglSet",
	"object" : "set",
	"type" : "instanciable",
	"revision" : "20150930",
	"extends" : "nglBranch",
	"interfaces" : "inglBranch",
	"description" : "Operaciones con listas.",
	"arguments": {
		"data" : ["string", "Cadena de valores separados por <b>splitter</b>", ""],
		"splitter" : ["string", "Separador utilizado en <b>data</b>", ","],
		"item" : ["mixed", "Elemento activo del set"],
		"index" : ["int", "Indice del elemento activo", "null"],
		"referer" : ["int", "Posición de referencia en relación a <b>place</b> cuando este es: before ó after", "null"],
		"place" : ["string", "Nueva posicion para el item, (first|before|after|last)", "last"],
		"needle" : ["mixed", "Valor de un elemento a buscar utilizando nglSet::find", "null"],
		"regex" : ["boolean", "Indica si <b>needle</b> es ó no una expresión regular", "false"]
	}
	
} **/

/*
/*
$s = $ngl("set.")->load("anana,banana,coco,damasco");
// $s->startzero();
$s->startone();
echo "list: ".$s->text()."<br />";
echo "list: ".$s->get(3)."<br />";

// $s->insert("pera");
// echo "list: ".$s->text()."<br />";

$s->insert("frutilla","after",3);
echo "list: ".$s->text()."<br />";

/*
echo "<br />";
$s->update("BANANAS",2);
echo "list: ".$s->text()."<br />";

exit();

echo "list: ".$s->text()."<br />";
$n = $s->indexof("anana");
$m = $s->indexof("pera");
$s->swap($n,$m);
echo "list: ".$s->text()."<br />";


$s->jump($n);
while(true) {
	echo "borrando: ".$s->get("current")."<br />";
	if($s->delete()===false) { break; }
	echo "list: ".$s->text()."<br />";
}
echo "list: ".$s->text()."<br />";
*/

class nglSet extends nglBranch implements inglBranch {
	
	private $bZero;

	final protected function __declareArguments__() {
		$vArguments					= [];
		$vArguments["data"]			= ['(string)$mValue'];
		$vArguments["splitter"]		= ['(string)$mValue', ","];
		$vArguments["item"]			= ['$mValue'];
		$vArguments["index"]		= ['(int)$mValue', null];
		$vArguments["referer"]		= ['(int)$mValue', null];
		$vArguments["place"]		= ['$mValue', "last"];
		$vArguments["needle"]		= ['$mValue', null];
		$vArguments["regex"]		= ['self::call()->isTrue($mValue)', false];
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes 				= [];
		$vAttributes["text"]		= null;
		$vAttributes["array"]		= null;
		$vAttributes["length"]		= null;
		$vAttributes["current"]		= null;
		return $vAttributes;
	}
	
	final protected function __declareVariables__() {
	}

	final public function __init__() {
		$this->startone();
	}

	public function startzero(){
		$this->bZero = true;
	}

	public function startone(){
		$this->bZero = false;
	}

	private function Index(&$nIndex) {
		if(!$this->bZero) { $nIndex--; }
	}

	public function find() {
		list($sNeedle,$bReGex) = $this->getarguments("needle,regex", \func_get_args());
		$aSet = $this->attribute("array");
		$sNeedle = ($bReGex) ? $sNeedle : "/(.*)".\preg_quote($sNeedle)."(.*)/is";
		return \preg_grep($sNeedle, $aSet);
	}

	public function get($sWhich=null) {
		list($sWhich) = $this->getarguments("item", \func_get_args());
		$aSet = $this->attribute("array");

		if($sWhich===null) {
			return $aSet;
		} else if(!\is_numeric($sWhich)) {
			$nIndex = $this->attribute("current");
			$sWhich = \strtolower($sWhich);
			switch($sWhich) {
				case "first": $nIndex = 0; break;
				case "last": $nIndex = $this->attribute("length")-1; break;
				case "previus": $nIndex--; break; // no avanza el puntero
				case "next": $nIndex++; break; // no avanza el puntero
			}
		} else {
			$this->Index($sWhich);
			$nIndex = $sWhich;
		}

		return (isset($aSet[$nIndex])) ? $aSet[$nIndex] : false;
	}

	public function length() {
		return $this->attribute("length");
	}
	
	public function prev() {
		$nCurrent = $this->attribute("current");
		if($nCurrent==0) { return false; }
		$nCurrent--;
		$this->attribute("current", $nCurrent);
		return $this;
	}

	public function next() {
		$nCurrent = $this->attribute("current");
		$nLength = $this->attribute("length");
		if($nCurrent==$nLength) { return false; }
		$nCurrent++;
		$this->attribute("current", $nCurrent);
		return $this;
	}

	public function jump() {
		list($nCurrent) = $this->getarguments("index", \func_get_args());
		$this->Index($nCurrent);
		$nLength = $this->attribute("length");
		if($nCurrent>=$nLength || $nCurrent<0) { return false; }
		$this->attribute("current", $nCurrent);
		return $this;
	}

	public function shuffle() {
		$aSet = $this->attribute("array");
		\shuffle($aSet);
		$this->attribute("array", $aSet);
		return $this;
	}

	public function sort() {
		$aSet = $this->attribute("array");
		\natcasesort($aSet);
		$this->attribute("array", $aSet);
		return $this;
	}

	public function rsort() {
		$aSet = $this->attribute("array");
		\natcasesort($aSet);
		$aSet = \array_reverse($aSet);
		$this->attribute("array", $aSet);
		return $this;
	}

	public function swap() {
		list($nItem1,$nItem2) = $this->getarguments("index,referer", \func_get_args());
		$aSet = $this->attribute("array");
		$this->Index($nItem1);
		$this->Index($nItem2);
		if(isset($aSet[$nItem1], $aSet[$nItem2])) {
			$mTmp = $aSet[$nItem1];
			$aSet[$nItem1] = $aSet[$nItem2];
			$aSet[$nItem2] = $mTmp;
			$this->attribute("array", $aSet);
		}
		return $this;
	}

	/** FUNCTION {
	update: Reasigna la posicion de un item en un Set de datos.
	Input:
		$mSet = Set de datos;
		$sCurrent = item a ordenar;
		$sPlace = nueva posicion para el item, (first|before|after|last);
		$nReferer = posicion de referencia cuando $sPlace = (before|after);
	**/
	public function insert() {
		list($sCurrent, $sPlace, $nReferer) = $this->getarguments("item,place,referer", \func_get_args());

		$aSet = $this->attribute("array");
		$this->Index($nReferer);

		// ordenamiento
		$sPlace = \strtolower($sPlace);
		switch(1) {
			case ($sPlace=="first"):
				\array_unshift($aSet, $sCurrent);
				break;
		
			case ($sPlace=="before" && $nReferer!==null):
				$aSet = \array_reverse($aSet, true);

			case ($sPlace=="after" && $nReferer!==null):
				foreach($aSet as $nKey => $mItem) {
					$aNewSequence[] = $mItem;
					if($nKey==$nReferer) { $aNewSequence[] = $sCurrent; }
				}
				$aSet = ($sPlace=="before") ? \array_reverse($aNewSequence, true) : $aNewSequence;
				break;
			
			case ($sPlace=="last"):
			default:
				$aSet[] = $sCurrent;
				break;
		}
	
		$this->RebuildAttributes($aSet);
		return $this;
	}
	
	/*
	Elimina y conserva la posición del puntero
	Cuando la posicion del puntero sea igual al rango de la lista, el metodo reseteara el puntero y retornara false
	*/
	public function delete() {
		list($nIndex) = $this->getarguments("index", \func_get_args());
		if($nIndex==null) {
			$nIndex = $this->attribute("current");
		} else {
			$this->Index($nIndex);
		}

		$aSet = $this->attribute("array");
		if(isset($aSet[$nIndex])) {
			unset($aSet[$nIndex]);
			if($this->bZero) { $nIndex++; }
			$this->RebuildAttributes($aSet, $nIndex);
		} else {
			$this->RebuildAttributes($aSet);
			return false;
		}
		return $this;
	}

	public function update() {
		list($sItem,$nIndex) = $this->getarguments("item,index", \func_get_args());
		if($nIndex==null) {
			$nIndex = $this->attribute("current");
		} else {
			$this->Index($nIndex);
		}

		$aSet = $this->attribute("array");
		if(isset($aSet[$nIndex])) {
			$aSet[$nIndex] = $sItem;
			if($this->bZero) { $nIndex++; }
			$this->RebuildAttributes($aSet, $nIndex);
			$this->next();
			return $this;
		}
		return false;
	}

	public function indexOf() {
		list($sItem) = $this->getarguments("item", \func_get_args());

		$aSet = $this->attribute("array");
		if(\is_array($aSet) && \count($aSet)) {
			$nIndex = \array_search($sItem, $aSet);
			return ($nIndex!==false) ? ($this->bZero ? $nIndex : ++$nIndex) : false;
		} else {
			return false;
		}
	}

	private function RebuildAttributes($aSet, $nCurrent=0) {
		$nCurrent = $this->Index($nCurrent);
		$aSet = \array_values($aSet);
		$this->attribute("array", $aSet);
		$this->attribute("length", \count($aSet));
		$this->attribute("current", $nCurrent);
		$sSet = $this->text();
		$this->attribute("text", $sSet);
	}

	public function load() {
		list($mData, $sSplitter) = $this->getarguments("data,splitter", \func_get_args());

		$aSet = [];
		if($mData!==null) { $aSet = (!\is_array($mData)) ? \explode($sSplitter, $mData) : $mData; }
		$this->RebuildAttributes($aSet);

		return $this;
	}

	public function text() {
		list($sGlue) = $this->getarguments("splitter", \func_get_args());

		$sSet = "";
		$mData = $this->attribute("array");
		if($mData!==null) { $sSet = \implode($sGlue, $mData); }
		return $sSet;
	}
}

?>