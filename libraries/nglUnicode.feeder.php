<?php

namespace nogal;

/** CLASS {
	"name" : "nglUnicode",
	"object" : "unicode",
	"type" : "main",
	"revision" : "20140316",
	"extends" : "nglTrunk",
	"description" : "
		Este objeto sustituye algunos de los metodos nativos de PHP vinculados a las operaciones con cadenas de caracteres multibytes
		
		nglUnicode construye el objeto $unicode dentro del framework, el cual es accedido a través de: <b>$ngl("unicode")->NOMBRE_DE_METODO(...)</b>
	",
	"variables" : {
		"$bDisable" : ["private", "
			Cuando el valor es true, los metodos retornan los valores de los metodos nativos de PHP.
			Esto último ocurre cuando la constante NGL_CHARSET es diferente a UTF-8
		", "false"]
	}
} **/
class nglUnicode extends nglTrunk {

	protected $class		= "nglUnicode";
	protected $me			= "unicode";
	protected $object		= "unicode";
	private $bDisable 		= false;
	private $vUTF8Groups 	= [];

	public function __builder__() {
		$this->bDisable = (\strtoupper(NGL_CHARSET)=="UTF-8") ? false : true;

		$vUTF8Groups = [
			["SYM", "CONTROL", 0, 31],
			["SYM", "LATIN_BASIC_SYMBOLS", 32, 47],
			["NUM", "LATIN_BASIC_NUMBERS", 48, 57],
			["SYM", "LATIN_BASIC_SYMBOLS", 58, 64],
			["ABC", "LATIN_BASIC_UPPERCASE", 65, 90],
			["SYM", "LATIN_BASIC_SYMBOLS", 91, 96],
			["ABC", "LATIN_BASIC_LOWERCASE", 97, 122],
			["SYM", "LATIN_BASIC_SYMBOLS", 123, 127],
			["SYM", "LATIN1_CONTROL", 128, 159],
			["SYM", "LATIN1_SYMBOLS", 160, 191],
			["ABC", "LATIN1_SUPP", 192, 255],
			["ABC", "LATIN_EXT_A", 256, 383],
			["ABC", "LATIN_EXT_B", 384, 591],
			["ABC", "IPA", 592, 687],
			["SYM", "SPACING", 688, 767],
			["SYM", "DIACRITICAL_MARKS", 768, 879],
			["ABC", "GREEK", 880, 1023],
			["ABC", "CYRILLIC", 1024, 1279],
			["ABC", "CYRILLIC", 1280, 1327],
			["ABC", "ARMENIAN", 1328, 1423],
			["ABC", "HEBREW", 1424, 1535],
			["ABC", "ARABIC", 1536, 1791],
			["ABC", "SYRIAC", 1792, 1871],
			["ABC", "ARABIC", 1872, 1919],
			["ABC", "THAANA", 1920, 1983],
			["ABC", "NKO", 1984, 2047],
			["ABC", "SAMARITAN", 2048, 2111],
			["ABC", "MANDAIC", 2112, 2143],
			["ABC", "ARABIC", 2208, 2303],
			["ABU", "DEVANAGARI", 2304, 2431],
			["ABU", "BENGALI", 2432, 2559],
			["ABU", "GURMUKHI", 2560, 2687],
			["ABU", "GUJARATI", 2688, 2815],
			["ABU", "ORIYA", 2816, 2943],
			["ABU", "TAMIL", 2944, 3071],
			["ABU", "TELUGU", 3072, 3199],
			["ABU", "KANNADA", 3200, 3327],
			["ABU", "MALAYALAM", 3328, 3455],
			["ABU", "SINHALA", 3456, 3583],
			["ABU", "THAI", 3584, 3711],
			["ABU", "LAO", 3712, 3839],
			["ABU", "TIBETAN", 3840, 4095],
			["ABU", "MYANMAR", 4096, 4255],
			["ABU", "GEORGIAN", 4256, 4351],
			["ABU", "HANGUL_JAMO", 4352, 4607],
			["ABU", "ETHIOPIC", 4608, 4991],
			["ABU", "ETHIOPIC", 4992, 5023],
			["SYL", "CHEROKEE", 5024, 5119],
			["ABU", "ABORIGINAL", 5120, 5759],
			["ABC", "OGHAM", 5760, 5791],
			["ABC", "RUNIC", 5792, 5887],
			["ABU", "TAGALOG", 5888, 5919],
			["ABU", "HANUNOO", 5920, 5951],
			["ABU", "BUHID", 5952, 5983],
			["ABU", "TAGBANWA", 5984, 6015],
			["ABU", "KHMER", 6016, 6143],
			["ABU", "MONGOLIAN", 6144, 6319],
			["ABU", "ABORIGINAL", 6320, 6399],
			["ABU", "LIMBU", 6400, 6479],
			["ABU", "TAI", 6480, 6527],
			["ABC", "TAI", 6528, 6623],
			["SYM", "KHMER", 6624, 6655],
			["ABU", "BUGINESE", 6656, 6687],
			["ABU", "TAI", 6688, 6831],
			["SYM", "DIACRITICAL_MARKS", 6832, 6911],
			["ABU", "BALINESE", 6912, 7039],
			["ABU", "SUNDANESE", 7040, 7103],
			["ABU", "BATAK", 7104, 7167],
			["ABU", "LEPCHA", 7168, 7247],
			["ABC", "OL_CHIKI", 7248, 7295],
			["ABU", "SUNDANESE", 7360, 7375],
			["SYM", "VEDIC", 7376, 7423],
			["SYM", "PHONETIC", 7424, 7551],
			["SYM", "PHONETIC", 7552, 7615],
			["SYM", "DIACRITICAL_MARKS", 7616, 7679],
			["SYM", "LATIN_EXT", 7680, 7935],
			["SYM", "GREEK", 7936, 8191],
			["SYM", "PUNCTUATION", 8192, 8303],
			["SYM", "SUP_SUB_SCRIPTS", 8304, 8351],
			["SYM", "CURRENCY", 8352, 8399],
			["SYM", "DIACRITICAL_MARKS", 8400, 8447],
			["SYM", "LETTERLIKE", 8448, 8527],
			["SYM", "NUMBER", 8528, 8591],
			["SYM", "ARROWS", 8592, 8703],
			["SYM", "MATHEMATICAL", 8704, 8959],
			["SYM", "TECHNICAL", 8960, 9215],
			["SYM", "CONTROL_PICTURES", 9216, 9279],
			["SYM", "OPTICAL", 9280, 9311],
			["SYM", "ALPHANUMERICS", 9312, 9471],
			["SYM", "BOX_DRAWINGS", 9472, 9599],
			["SYM", "BLOCK_ELEMENTS", 9600, 9631],
			["SYM", "GEOMETRIC_SHAPES", 9632, 9727],
			["SYM", "MISCELLANEOUS", 9728, 9983],
			["SYM", "DINGBATS", 9984, 10175],
			["SYM", "MATHEMATICAL", 10176, 10223],
			["SYM", "ARROWS", 10224, 10239],
			["SYM", "BRAILLE", 10240, 10495],
			["SYM", "ARROWS", 10496, 10623],
			["SYM", "MATHEMATICAL", 10624, 10751],
			["SYM", "MATHEMATICAL", 10752, 11007],
			["SYM", "MISCELLANEOUS", 11008, 11263],
			["ABC", "GLAGOLITIC", 11264, 11359],
			["ABC", "LATIN_EXT_C", 11360, 11391],
			["ABC", "COPTIC", 11392, 11519],
			["ABC", "GEORGIAN", 11520, 11567],
			["ABY", "TIFINAGH", 11568, 11647],
			["ABU", "ETHIOPIC", 11648, 11743],
			["ABC", "CYRILLIC", 11744, 11775],
			["SYM", "PUNCTUATION", 11776, 11903],
			["SYM", "CJK", 11904, 12031],
			["SYM", "KANGXI", 12032, 12255],
			["SYM", "IDEOGRAPHIC", 12272, 12287],
			["SYM", "CJK", 12288, 12351],
			["SYL", "HIRAGANA", 12352, 12447],
			["SYL", "KATAKANA", 12448, 12543],
			["SYL", "BOPOMOFO", 12544, 12591],
			["SYL", "HANGUL", 12592, 12687],
			["SYL", "KANBUN", 12688, 12703],
			["SYL", "BOPOMOFO", 12704, 12735],
			["SYL", "CJK", 12736, 12783],
			["SYL", "KATAKANA", 12784, 12799],
			["SYL", "CJK", 12800, 13055],
			["SYL", "CJK", 13056, 13311],
			["SYL", "IDEOGRAPHIC", 13312, 19893],
			["SYL", "YIJING", 19904, 19967],
			["SYL", "IDEOGRAPHIC", 19968, 40908],
			["SYL", "YI", 40960, 42127],
			["SYL", "YI", 42128, 42191],
			["SYM", "LISU", 42192, 42239],
			["SYL", "VAI", 42240, 42559],
			["ABC", "CYRILLIC", 42560, 42655],
			["SYL", "BAMUM", 42656, 42751],
			["SYM", "TONE_LETTERS", 42752, 42783],
			["ABC", "LATIN_EXT_D", 42784, 43007],
			["ABU", "SYLOTI", 43008, 43055],
			["NUM", "INDIC_NUMBER", 43056, 43071],
			["ABU", "PHAGS_PA", 43072, 43135],
			["ABC", "SAURASHTRA", 43136, 43231],
			["ABU", "DEVANAGARI", 43232, 43263],
			["ABU", "KAYAH", 43264, 43311],
			["ABU", "REJANG", 43312, 43359],
			["ABC", "HANGUL", 43360, 43391],
			["ABU", "JAVANESE", 43392, 43487],
			["SYM", "MYANMAR", 43488, 43519],
			["ABU", "CHAM", 43520, 43615],
			["ABU", "MYANMAR", 43616, 43647],
			["ABU", "TAI", 43648, 43743],
			["ABU", "MEETEI", 43744, 43775],
			["ABU", "ETHIOPIC", 43776, 43823],
			["SYM", "LATIN_EXT", 43824, 43887],
			["SYM", "MEETEI", 43968, 44031],
			["ABC", "HANGUL", 44032, 55203],
			["SYM", "HANGUL", 55216, 55295],
			["SYM", "SURROGATES", 55296, 56191],
			["SYM", "SURROGATES", 56192, 56319],
			["SYM", "SURROGATES", 56320, 57343],
			["SYM", "USE_AREA", 57344, 63743],
			["SYM", "IDEOGRAPHIC", 63744, 64255],
			["SYM", "ALPHABETIC_FORMS", 64256, 64335],
			["SYM", "ARABIC", 64336, 65023],
			["SYM", "SELECTORS", 65024, 65039],
			["SYM", "VERTICAL_FORMS", 65040, 65055],
			["SYM", "HALF_MARKS", 65056, 65071],
			["SYM", "CJK", 65072, 65103],
			["SYM", "VARIANTS", 65104, 65135],
			["SYM", "ARABIC", 65136, 65279],
			["SYM", "HALFWIDTH_FULLWIDTH", 65280, 65519],
			["SYM", "SPECIALS", 65520, 65535],
			["SYM", "LINEAR_B", 65536, 65663],
			["SYM", "LINEAR_B", 65664, 65791],
			["SYM", "NUMBERS", 65792, 65855],
			["SYM", "NUMBERS", 65856, 65935],
			["SYM", "ANCIENT", 65936, 65999],
			["SYM", "PHAISTOS", 66000, 66047],
			["SYM", "LYCIAN", 66176, 66207],
			["SYM", "CARIAN", 66208, 66271],
			["SYM", "NUMBERS", 66272, 66303],
			["SYM", "ITALIC", 66304, 66351],
			["SYM", "GOTHIC", 66352, 66383],
			["SYM", "PERMIC", 66384, 66431],
			["SYM", "UGARITIC", 66432, 66463],
			["SYM", "PERSIAN", 66464, 66527],
			["SYM", "DESERET", 66560, 66639],
			["SYM", "SHAVIAN", 66640, 66687],
			["SYM", "OSMANYA", 66688, 66735],
			["SYM", "ELBASAN", 66816, 66863],
			["SYM", "ALBANIAN", 66864, 66927],
			["SYM", "LINEAR_A", 67072, 67455],
			["SYM", "CYPRIOT", 67584, 67647],
			["SYM", "ARAMAIC", 67648, 67679],
			["SYM", "PALMYRENE", 67680, 67711],
			["SYM", "NABATAEAN", 67712, 67759],
			["SYM", "PHOENICIAN", 67840, 67871],
			["SYM", "LYDIAN", 67872, 67903],
			["SYM", "MEROITIC", 67968, 67999],
			["SYM", "MEROITIC", 68000, 68095],
			["SYM", "KHAROSHTHI", 68096, 68191],
			["SYM", "ARABIAN", 68192, 68223],
			["SYM", "ARABIAN", 68224, 68255],
			["SYM", "MANICHAEAN", 68288, 68351],
			["SYM", "AVESTAN", 68352, 68415],
			["SYM", "PARTHIAN", 68416, 68447],
			["SYM", "PAHLAVI", 68448, 68479],
			["SYM", "PAHLAVI", 68480, 68527],
			["SYM", "TURKIC", 68608, 68687],
			["SYM", "RUMI", 69216, 69247],
			["SYM", "BRAHMI", 69632, 69759],
			["SYM", "KAITHI", 69760, 69839],
			["SYM", "SORA", 69840, 69887],
			["SYM", "CHAKMA", 69888, 69967],
			["SYM", "MAHAJANI", 69968, 70015],
			["SYM", "SHARADA", 70016, 70111],
			["SYM", "NUMBERS", 70112, 70143],
			["SYM", "KHOJKI", 70144, 70223],
			["SYM", "KHUDAWADI", 70320, 70399],
			["SYM", "GRANTHA", 70400, 70527],
			["SYM", "TIRHUTA", 70784, 70879],
			["SYM", "SIDDHAM", 71040, 71167],
			["SYM", "MODI", 71168, 71263],
			["SYM", "TAKRI", 71296, 71375],
			["SYM", "WARANG", 71840, 71935],
			["SYM", "PAU_CIN_HAU", 72384, 72447],
			["SYM", "CUNEIFORM", 73728, 74751],
			["SYM", "NUMBERS", 74752, 74879],
			["SYM", "EGYPTIAN", 77824, 78895],
			["SYM", "BAMUM", 92160, 92735],
			["SYM", "MRO", 92736, 92783],
			["SYM", "BASSA_VAH", 92880, 92927],
			["SYM", "PAHAWH_HMONG", 92928, 93071],
			["SYM", "MIAO", 93952, 94111],
			["SYM", "KANA", 110592, 110847],
			["SYM", "DUPLOYAN", 113664, 113823],
			["SYM", "SHORTHAND", 113824, 113839],
			["SYM", "MUSICAL", 118784, 119039],
			["SYM", "MUSICAL", 119040, 119295],
			["SYM", "MUSICAL", 119296, 119375],
			["SYM", "TAI", 119552, 119647],
			["SYM", "MATHEMATICAL", 119648, 119679],
			["SYM", "MATHEMATICAL", 119808, 120831],
			["SYM", "MENDE_KIKAKUI", 124928, 125151],
			["SYM", "MATHEMATICAL", 126464, 126719],
			["SYM", "MAHJONG", 126976, 127023],
			["SYM", "DOMINO", 127024, 127135],
			["SYM", "CARDS", 127136, 127231],
			["SYM", "ALPHANUMERIC", 127232, 127487],
			["SYM", "IDEOGRAPHIC", 127488, 127743],
			["SYM", "MISCELLANEOUS", 127744, 128511],
			["SYM", "EMOTICONS", 128512, 128591],
			["SYM", "DINGBATS", 128592, 128639],
			["SYM", "TRANSPORT_MAP", 128640, 128767],
			["SYM", "ALCHEMICAL", 128768, 128895],
			["SYM", "GEOMETRIC_SHAPES", 128896, 129023],
			["SYM", "ARROWS", 129024, 129279]
		];
		$this->vUTF8Groups = $vUTF8Groups;
	}

	/** FUNCTION {
		"name" : "chr", 
		"type" : "public",
		"description" : "Devuelve una cadena de un caracter que contiene el carácter especificado por $nCode",
		"parameters" : {
			"$nCode" : ["int", "Código unicode del caracter buscado"]
		},
		"return" : "string"
	} **/
	public function chr($nCode) {
		if($this->bDisable) {
			$sChr = \chr($nCode);
		} else {
			if($nCode <= 0x7F) {
				$sChr = \chr($nCode);
			} else if ($nCode <= 0x7FF) {
				$sChr = \chr(0xC0 | $nCode >> 6).\chr(0x80 | $nCode & 0x3F);
			} else if ($nCode <= 0xFFFF) {
				$sChr = \chr(0xE0 | $nCode >> 12).\chr(0x80 | $nCode >> 6 & 0x3F).\chr(0x80 | $nCode & 0x3F);
			} else if($nCode <= 0x10FFFF) {
				$sChr = \chr(0xF0 | $nCode >> 18).\chr(0x80 | $nCode >> 12 & 0x3F).\chr(0x80 | $nCode >> 6 & 0x3F).\chr(0x80 | $nCode & 0x3F);
			} else {
				$sChr = false;
			}
		}
		
		return $sChr;
	}

	/** FUNCTION {
		"name" : "escape", 
		"type" : "public",
		"description" : "
			Escapa una cadena en formato UNICODE.
			Donde los caracteres que no sean UTF-8 serán reemplazados por su ORD en formato hexadecimal precedidos de una <b>u</b>",
		"parameters" : {"$sString" : ["string", "Cadena a codificar"]},
		"seealso" : ["nglUnicode::unescape"],
		"return" : "string"
	} **/
	public function escape($sText) {
		$sUnicode = "";
		$sChar = "";
		$bUTF8 = false;
		$nText = strlen($sText);
		for($x=0; $x<$nText; $x++) {
			if((\ord($sText[$x])&0xC0)!=0x80) {
				if(\strlen($sChar)) {
					$nChar = $this->ord($sChar);
					$nChar = self::call()->dec2hex($nChar);
					$sUnicode .= (!$bUTF8) ? $sChar : "\\u".\str_pad($nChar, 4, "0", STR_PAD_LEFT);
					$sChar = "";
					$bUTF8 = false;
				}
			} else {
				$bUTF8 = true;
			}

			$sChar .= $sText[$x];
		}

		$nChar = $this->ord($sChar);
		$nChar = self::call()->dec2hex($nChar);
		$sUnicode .= (!$bUTF8) ? $sChar : "\\u".\str_pad($nChar, 4, "0", STR_PAD_LEFT);
		
		return $sUnicode;
	}

	/** FUNCTION {
		"name" : "explode", 
		"type" : "public",
		"description" : "Divide una cadena en varias",
		"parameters" : {
			"$sSplitter" : ["string", "Cadena delimitadora de 1 caracter unicode de largo"],
			"$sString" : ["string", "Origen de datos"],
			"$nLimit" : ["int", "
				Número máximo de subcadenas. $nLimit actua igual que en PHP, es decir 
				Si es positivo, el array devuelto contendrá el máximo de elementos en el limit y el último elemento contendrá el resto del string.
				Si es negativo, se devolverán todos los componentes a excepción de los últimos -limit.
				Si es cero, actuará como si su valor fuera 1.

			", "null"]
		},
		"return" : "string"
	} **/
	public function explode($sSplitter, $sString, $nLimit=null) {
		if($this->bDisable) {
			return ($nLimit===null) ? \explode($sSplitter, $sString) : \explode($sSplitter, $sString, $nLimit);
		} else {
			if($nLimit!==null && !$nLimit) { return array($sString); }
			$sChar = $sPart = "";
			$nString = \strlen($sString);
			for($x=0; $x<$nString; $x++) {
				if((\ord($sString[$x])&0xC0)!=0x80) {
					if($sChar!=$sSplitter) { $sPart .= $sChar; }
					if($sPart!="") {
						if($sChar==$sSplitter) {
							$aExplode[] = $sPart;
							$sChar = $sPart = "";
						} else {
							$sChar = "";
						}
					}
					$sChar .= $sString[$x];
				} else {
					$sChar .= $sString[$x];
				}
				
				if($sChar==$sSplitter && $sPart=="") {	
					$aExplode[] = "";
					$sChar = "";
				}
			}

			if($sChar!=$sSplitter) { $sPart .= $sChar; }
			$aExplode[] = $sPart;
			if($sChar==$sSplitter) { $aExplode[] = ""; }
			
			if($nLimit!==null) {
				if($nLimit>0) {
					if($nLimit>=\count($aExplode)) { return $aExplode; }
					$aSlice = \array_slice($aExplode, 0, $nLimit-1);
					$aEnd = \array_slice($aExplode, $nLimit-1);
					$aSlice[] = \implode($sSplitter, $aEnd);
					return $aSlice;
				} else {
					return \array_slice($aExplode, 0, $nLimit);
				}
			}
		}
		
		return $aExplode;
	}

	/** FUNCTION {
		"name" : "groups", 
		"type" : "public",
		"description" : "
			Retorna la información de los grupos de caracteres UTF-8
			
			<b>Tipos</b>
			<ul>
				<li><b>ABC:</b> alfabeto</li>
				<li><b>ABU:</b> abugida</li>
				<li><b>NUM:</b> números</li>
				<li><b>SYL:</b> silabario</li>
				<li><b>SYM:</b> símbolos</li>
			</ul>
			
			<b>Grupos</b>
			<ul>
				<li><b>SYM - CONTROL:</b> Control character (0-31)</li>
				<li><b>SYM - LATIN_BASIC_SYMBOLS:</b> Basic Latin (32-47)</li>
				<li><b>NUM - LATIN_BASIC_NUMBERS:</b> Basic Latin - Numbers (48-57)</li>
				<li><b>SYM - LATIN_BASIC_SYMBOLS:</b> Basic Latin (58-64)</li>
				<li><b>ABC - LATIN_BASIC_UPPERCASE:</b> Basic Latin - uppercase (65-90)</li>
				<li><b>SYM - LATIN_BASIC_SYMBOLS:</b> Basic Latin (91-96)</li>
				<li><b>ABC - LATIN_BASIC_LOWERCASE:</b> Basic Latin - lowercase (97-122)</li>
				<li><b>SYM - LATIN_BASIC_SYMBOLS:</b> Basic Latin (123-127)</li>
				<li><b>SYM - LATIN1_CONTROL:</b> Control C1 (128-159)</li>
				<li><b>SYM - LATIN1_SYMBOLS:</b> Special symbols (160-191)</li>
				<li><b>ABC - LATIN1_SUPP:</b> Latin-1 Supplement (192-255)</li>
				<li><b>ABC - LATIN_EXT_A:</b> Latin Extended-A (256-383)</li>
				<li><b>ABC - LATIN_EXT_B:</b> Latin Extended-B (384-591)</li>
				<li><b>ABC - IPA:</b> IPA Extensions (592-687)</li>
				<li><b>SYM - SPACING:</b> Spacing Modifier Letters (688-767)</li>
				<li><b>SYM - DIACRITICAL_MARKS:</b> Combining Diacritical Marks (768-879)</li>
				<li><b>ABC - GREEK:</b> Greek and Coptic (880-1023)</li>
				<li><b>ABC - CYRILLIC:</b> Cyrillic (1024-1279)</li>
				<li><b>ABC - CYRILLIC:</b> Cyrillic Supplement (1280-1327)</li>
				<li><b>ABC - ARMENIAN:</b> Armenian (1328-1423)</li>
				<li><b>ABC - HEBREW:</b> Hebrew (1424-1535)</li>
				<li><b>ABC - ARABIC:</b> Arabic (1536-1791)</li>
				<li><b>ABC - SYRIAC:</b> Syriac (1792-1871)</li>
				<li><b>ABC - ARABIC:</b> Arabic Supplement (1872-1919)</li>
				<li><b>ABC - THAANA:</b> Thaana (1920-1983)</li>
				<li><b>ABC - NKO:</b> NKo (1984-2047)</li>
				<li><b>ABC - SAMARITAN:</b> Samaritan (2048-2111)</li>
				<li><b>ABC - MANDAIC:</b> Mandaic (2112-2143)</li>
				<li><b>ABC - ARABIC:</b> Arabic Extended-A (2208-2303)</li>
				<li><b>ABU - DEVANAGARI:</b> Devanagari (2304-2431)</li>
				<li><b>ABU - BENGALI:</b> Bengali (2432-2559)</li>
				<li><b>ABU - GURMUKHI:</b> Gurmukhi (2560-2687)</li>
				<li><b>ABU - GUJARATI:</b> Gujarati (2688-2815)</li>
				<li><b>ABU - ORIYA:</b> Oriya (2816-2943)</li>
				<li><b>ABU - TAMIL:</b> Tamil (2944-3071)</li>
				<li><b>ABU - TELUGU:</b> Telugu (3072-3199)</li>
				<li><b>ABU - KANNADA:</b> Kannada (3200-3327)</li>
				<li><b>ABU - MALAYALAM:</b> Malayalam (3328-3455)</li>
				<li><b>ABU - SINHALA:</b> Sinhala (3456-3583)</li>
				<li><b>ABU - THAI:</b> Thai (3584-3711)</li>
				<li><b>ABU - LAO:</b> Lao (3712-3839)</li>
				<li><b>ABU - TIBETAN:</b> Tibetan (3840-4095)</li>
				<li><b>ABU - MYANMAR:</b> Myanmar (4096-4255)</li>
				<li><b>ABU - GEORGIAN:</b> Georgian (4256-4351)</li>
				<li><b>ABU - HANGUL_JAMO:</b> Hangul Jamo (4352-4607)</li>
				<li><b>ABU - ETHIOPIC:</b> Ethiopic (4608-4991)</li>
				<li><b>ABU - ETHIOPIC:</b> Ethiopic Supplement (4992-5023)</li>
				<li><b>SYL - CHEROKEE:</b> Cherokee (5024-5119)</li>
				<li><b>ABU - ABORIGINAL:</b> Unified Canadian Aboriginal Syllabics (5120-5759)</li>
				<li><b>ABC - OGHAM:</b> Ogham (5760-5791)</li>
				<li><b>ABC - RUNIC:</b> Runic (5792-5887)</li>
				<li><b>ABU - TAGALOG:</b> Tagalog (5888-5919)</li>
				<li><b>ABU - HANUNOO:</b> Hanunoo (5920-5951)</li>
				<li><b>ABU - BUHID:</b> Buhid (5952-5983)</li>
				<li><b>ABU - TAGBANWA:</b> Tagbanwa (5984-6015)</li>
				<li><b>ABU - KHMER:</b> Khmer (6016-6143)</li>
				<li><b>ABU - MONGOLIAN:</b> Mongolian (6144-6319)</li>
				<li><b>ABU - ABORIGINAL:</b> Unified Canadian Aboriginal Syllabics Extended (6320-6399)</li>
				<li><b>ABU - LIMBU:</b> Limbu (6400-6479)</li>
				<li><b>ABU - TAI:</b> Tai Le (6480-6527)</li>
				<li><b>ABC - TAI:</b> New Tai Lue (6528-6623)</li>
				<li><b>SYM - KHMER:</b> Khmer Symbols (6624-6655)</li>
				<li><b>ABU - BUGINESE:</b> Buginese (6656-6687)</li>
				<li><b>ABU - TAI:</b> Tai Tham (6688-6831)</li>
				<li><b>SYM - DIACRITICAL_MARKS:</b> Combining Diacritical Marks Extended (6832-6911)</li>
				<li><b>ABU - BALINESE:</b> Balinese (6912-7039)</li>
				<li><b>ABU - SUNDANESE:</b> Sundanese (7040-7103)</li>
				<li><b>ABU - BATAK:</b> Batak (7104-7167)</li>
				<li><b>ABU - LEPCHA:</b> Lepcha (7168-7247)</li>
				<li><b>ABC - OL_CHIKI:</b> Ol Chiki (7248-7295)</li>
				<li><b>ABU - SUNDANESE:</b> Sundanese Supplement (7360-7375)</li>
				<li><b>SYM - VEDIC:</b> Vedic Extensions (7376-7423)</li>
				<li><b>SYM - PHONETIC:</b> Phonetic Extensions (7424-7551)</li>
				<li><b>SYM - PHONETIC:</b> Phonetic Extensions Supplement (7552-7615)</li>
				<li><b>SYM - DIACRITICAL_MARKS:</b> Combining Diacritical Marks Supplement (7616-7679)</li>
				<li><b>SYM - LATIN_EXT:</b> Latin Extended Additional (7680-7935)</li>
				<li><b>SYM - GREEK:</b> Greek Extended (7936-8191)</li>
				<li><b>SYM - PUNCTUATION:</b> General Punctuation (8192-8303)</li>
				<li><b>SYM - SUP_SUB_SCRIPTS:</b> Superscripts and Subscripts (8304-8351)</li>
				<li><b>SYM - CURRENCY:</b> Currency Symbols (8352-8399)</li>
				<li><b>SYM - DIACRITICAL_MARKS:</b> Combining Diacritical Marks for Symbols (8400-8447)</li>
				<li><b>SYM - LETTERLIKE:</b> Letterlike Symbols (8448-8527)</li>
				<li><b>SYM - NUMBER:</b> Number Forms (8528-8591)</li>
				<li><b>SYM - ARROWS:</b> Arrows (8592-8703)</li>
				<li><b>SYM - MATHEMATICAL:</b> Mathematical Operators (8704-8959)</li>
				<li><b>SYM - TECHNICAL:</b> Miscellaneous Technical (8960-9215)</li>
				<li><b>SYM - CONTROL_PICTURES:</b> Control Pictures (9216-9279)</li>
				<li><b>SYM - OPTICAL:</b> Optical Character Recognition (9280-9311)</li>
				<li><b>SYM - ALPHANUMERICS:</b> Enclosed Alphanumerics (9312-9471)</li>
				<li><b>SYM - BOX_DRAWINGS:</b> Box Drawing (9472-9599)</li>
				<li><b>SYM - BLOCK_ELEMENTS:</b> Block Elements (9600-9631)</li>
				<li><b>SYM - GEOMETRIC_SHAPES:</b> Geometric Shapes (9632-9727)</li>
				<li><b>SYM - MISCELLANEOUS:</b> Miscellaneous Symbols (9728-9983)</li>
				<li><b>SYM - DINGBATS:</b> Dingbats (9984-10175)</li>
				<li><b>SYM - MATHEMATICAL:</b> Miscellaneous Mathematical Symbols-A (10176-10223)</li>
				<li><b>SYM - ARROWS:</b> Supplemental Arrows-A (10224-10239)</li>
				<li><b>SYM - BRAILLE:</b> Braille Patterns (10240-10495)</li>
				<li><b>SYM - ARROWS:</b> Supplemental Arrows-B (10496-10623)</li>
				<li><b>SYM - MATHEMATICAL:</b> Miscellaneous Mathematical Symbols-B (10624-10751)</li>
				<li><b>SYM - MATHEMATICAL:</b> Supplemental Mathematical Operators (10752-11007)</li>
				<li><b>SYM - MISCELLANEOUS:</b> Miscellaneous Symbols and Arrows (11008-11263)</li>
				<li><b>ABC - GLAGOLITIC:</b> Glagolitic (11264-11359)</li>
				<li><b>ABC - LATIN_EXT_C:</b> Latin Extended-C (11360-11391)</li>
				<li><b>ABC - COPTIC:</b> Coptic (11392-11519)</li>
				<li><b>ABC - GEORGIAN:</b> Georgian Supplement (11520-11567)</li>
				<li><b>ABY - TIFINAGH:</b> Tifinagh (11568-11647)</li>
				<li><b>ABU - ETHIOPIC:</b> Ethiopic Extended (11648-11743)</li>
				<li><b>ABC - CYRILLIC:</b> Cyrillic Extended-A (11744-11775)</li>
				<li><b>SYM - PUNCTUATION:</b> Supplemental Punctuation (11776-11903)</li>
				<li><b>SYM - CJK:</b> CJK Radicals Supplement (11904-12031)</li>
				<li><b>SYM - KANGXI:</b> Kangxi Radicals (12032-12255)</li>
				<li><b>SYM - IDEOGRAPHIC:</b> Ideographic Description Characters (12272-12287)</li>
				<li><b>SYM - CJK:</b> CJK Symbols and Punctuation (12288-12351)</li>
				<li><b>SYL - HIRAGANA:</b> Hiragana (12352-12447)</li>
				<li><b>SYL - KATAKANA:</b> Katakana (12448-12543)</li>
				<li><b>SYL - BOPOMOFO:</b> Bopomofo (12544-12591)</li>
				<li><b>SYL - HANGUL:</b> Hangul Compatibility Jamo (12592-12687)</li>
				<li><b>SYL - KANBUN:</b> Kanbun (12688-12703)</li>
				<li><b>SYL - BOPOMOFO:</b> Bopomofo Extended (12704-12735)</li>
				<li><b>SYL - CJK:</b> CJK Strokes (12736-12783)</li>
				<li><b>SYL - KATAKANA:</b> Katakana Phonetic Extensions (12784-12799)</li>
				<li><b>SYL - CJK:</b> Enclosed CJK Letters and Months (12800-13055)</li>
				<li><b>SYL - CJK:</b> CJK Compatibility (13056-13311)</li>
				<li><b>SYL - IDEOGRAPHIC:</b> CJK Unified Ideographs Extension A (13312-19893)</li>
				<li><b>SYL - YIJING:</b> Yijing Hexagram Symbols (19904-19967)</li>
				<li><b>SYL - IDEOGRAPHIC:</b> CJK Unified Ideographs (19968-40908)</li>
				<li><b>SYL - YI:</b> Yi Syllables (40960-42127)</li>
				<li><b>SYL - YI:</b> Yi Radicals (42128-42191)</li>
				<li><b>SYM - LISU:</b> Lisu (42192-42239)</li>
				<li><b>SYL - VAI:</b> Vai (42240-42559)</li>
				<li><b>ABC - CYRILLIC:</b> Cyrillic Extended-B (42560-42655)</li>
				<li><b>SYL - BAMUM:</b> Bamum (42656-42751)</li>
				<li><b>SYM - TONE_LETTERS:</b> Modifier Tone Letters (42752-42783)</li>
				<li><b>ABC - LATIN_EXT_D:</b> Latin Extended-D (42784-43007)</li>
				<li><b>ABU - SYLOTI:</b> Syloti Nagri (43008-43055)</li>
				<li><b>NUM - INDIC_NUMBER:</b> Common Indic Number Forms (43056-43071)</li>
				<li><b>ABU - PHAGS_PA:</b> Phags-pa (43072-43135)</li>
				<li><b>ABC - SAURASHTRA:</b> Saurashtra (43136-43231)</li>
				<li><b>ABU - DEVANAGARI:</b> Devanagari Extended (43232-43263)</li>
				<li><b>ABU - KAYAH:</b> Kayah Li (43264-43311)</li>
				<li><b>ABU - REJANG:</b> Rejang (43312-43359)</li>
				<li><b>ABC - HANGUL:</b> Hangul Jamo Extended-A (43360-43391)</li>
				<li><b>ABU - JAVANESE:</b> Javanese (43392-43487)</li>
				<li><b>SYM - MYANMAR:</b> Myanmar Extended-B (43488-43519)</li>
				<li><b>ABU - CHAM:</b> Cham (43520-43615)</li>
				<li><b>ABU - MYANMAR:</b> Myanmar Extended-A (43616-43647)</li>
				<li><b>ABU - TAI:</b> Tai Viet (43648-43743)</li>
				<li><b>ABU - MEETEI:</b> Meetei Mayek Extensions (43744-43775)</li>
				<li><b>ABU - ETHIOPIC:</b> Ethiopic Extended-A (43776-43823)</li>
				<li><b>SYM - LATIN_EXT:</b> Latin Extended-E (43824-43887)</li>
				<li><b>SYM - MEETEI:</b> Meetei Mayek (43968-44031)</li>
				<li><b>ABC - HANGUL:</b> Hangul Syllables (44032-55203)</li>
				<li><b>SYM - HANGUL:</b> Hangul Jamo Extended-B (55216-55295)</li>
				<li><b>SYM - SURROGATES:</b> High Surrogates (55296-56191)</li>
				<li><b>SYM - SURROGATES:</b> High Private Use Surrogates (56192-56319)</li>
				<li><b>SYM - SURROGATES:</b> Low Surrogates (56320-57343)</li>
				<li><b>SYM - USE_AREA:</b> Private Use Area (57344-63743)</li>
				<li><b>SYM - IDEOGRAPHIC:</b> CJK Compatibility Ideographs (63744-64255)</li>
				<li><b>SYM - ALPHABETIC_FORMS:</b> Alphabetic Presentation Forms (64256-64335)</li>
				<li><b>SYM - ARABIC:</b> Arabic Presentation Forms-A (64336-65023)</li>
				<li><b>SYM - SELECTORS:</b> Variation Selectors (65024-65039)</li>
				<li><b>SYM - VERTICAL_FORMS:</b> Vertical Forms (65040-65055)</li>
				<li><b>SYM - HALF_MARKS:</b> Combining Half Marks (65056-65071)</li>
				<li><b>SYM - CJK:</b> CJK Compatibility Forms (65072-65103)</li>
				<li><b>SYM - VARIANTS:</b> Small Form Variants (65104-65135)</li>
				<li><b>SYM - ARABIC:</b> Arabic Presentation Forms-B (65136-65279)</li>
				<li><b>SYM - HALFWIDTH_FULLWIDTH:</b> Halfwidth and Fullwidth Forms (65280-65519)</li>
				<li><b>SYM - SPECIALS:</b> Specials (65520-65535)</li>
				<li><b>SYM - LINEAR_B:</b> Linear B Syllabary (65536-65663)</li>
				<li><b>SYM - LINEAR_B:</b> Linear B Ideograms (65664-65791)</li>
				<li><b>SYM - NUMBERS:</b> Aegean Numbers (65792-65855)</li>
				<li><b>SYM - NUMBERS:</b> Ancient Greek Numbers (65856-65935)</li>
				<li><b>SYM - ANCIENT:</b> Ancient Symbols (65936-65999)</li>
				<li><b>SYM - PHAISTOS:</b> Phaistos Disc (66000-66047)</li>
				<li><b>SYM - LYCIAN:</b> Lycian (66176-66207)</li>
				<li><b>SYM - CARIAN:</b> Carian (66208-66271)</li>
				<li><b>SYM - NUMBERS:</b> Coptic Epact Numbers (66272-66303)</li>
				<li><b>SYM - ITALIC:</b> Old Italic (66304-66351)</li>
				<li><b>SYM - GOTHIC:</b> Gothic (66352-66383)</li>
				<li><b>SYM - PERMIC:</b> Old Permic (66384-66431)</li>
				<li><b>SYM - UGARITIC:</b> Ugaritic (66432-66463)</li>
				<li><b>SYM - PERSIAN:</b> Old Persian (66464-66527)</li>
				<li><b>SYM - DESERET:</b> Deseret (66560-66639)</li>
				<li><b>SYM - SHAVIAN:</b> Shavian (66640-66687)</li>
				<li><b>SYM - OSMANYA:</b> Osmanya (66688-66735)</li>
				<li><b>SYM - ELBASAN:</b> Elbasan (66816-66863)</li>
				<li><b>SYM - ALBANIAN:</b> Caucasian Albanian (66864-66927)</li>
				<li><b>SYM - LINEAR_A:</b> Linear A (67072-67455)</li>
				<li><b>SYM - CYPRIOT:</b> Cypriot Syllabary (67584-67647)</li>
				<li><b>SYM - ARAMAIC:</b> Imperial Aramaic (67648-67679)</li>
				<li><b>SYM - PALMYRENE:</b> Palmyrene (67680-67711)</li>
				<li><b>SYM - NABATAEAN:</b> Nabataean (67712-67759)</li>
				<li><b>SYM - PHOENICIAN:</b> Phoenician (67840-67871)</li>
				<li><b>SYM - LYDIAN:</b> Lydian (67872-67903)</li>
				<li><b>SYM - MEROITIC:</b> Meroitic Hieroglyphs (67968-67999)</li>
				<li><b>SYM - MEROITIC:</b> Meroitic Cursive (68000-68095)</li>
				<li><b>SYM - KHAROSHTHI:</b> Kharoshthi (68096-68191)</li>
				<li><b>SYM - ARABIAN:</b> Old South Arabian (68192-68223)</li>
				<li><b>SYM - ARABIAN:</b> Old North Arabian (68224-68255)</li>
				<li><b>SYM - MANICHAEAN:</b> Manichaean (68288-68351)</li>
				<li><b>SYM - AVESTAN:</b> Avestan (68352-68415)</li>
				<li><b>SYM - PARTHIAN:</b> Inscriptional Parthian (68416-68447)</li>
				<li><b>SYM - PAHLAVI:</b> Inscriptional Pahlavi (68448-68479)</li>
				<li><b>SYM - PAHLAVI:</b> Psalter Pahlavi (68480-68527)</li>
				<li><b>SYM - TURKIC:</b> Old Turkic (68608-68687)</li>
				<li><b>SYM - RUMI:</b> Rumi Numeral Symbols (69216-69247)</li>
				<li><b>SYM - BRAHMI:</b> Brahmi (69632-69759)</li>
				<li><b>SYM - KAITHI:</b> Kaithi (69760-69839)</li>
				<li><b>SYM - SORA:</b> Sora Sompeng (69840-69887)</li>
				<li><b>SYM - CHAKMA:</b> Chakma (69888-69967)</li>
				<li><b>SYM - MAHAJANI:</b> Mahajani (69968-70015)</li>
				<li><b>SYM - SHARADA:</b> Sharada (70016-70111)</li>
				<li><b>SYM - NUMBERS:</b> Sinhala Archaic Numbers (70112-70143)</li>
				<li><b>SYM - KHOJKI:</b> Khojki (70144-70223)</li>
				<li><b>SYM - KHUDAWADI:</b> Khudawadi (70320-70399)</li>
				<li><b>SYM - GRANTHA:</b> Grantha (70400-70527)</li>
				<li><b>SYM - TIRHUTA:</b> Tirhuta (70784-70879)</li>
				<li><b>SYM - SIDDHAM:</b> Siddham (71040-71167)</li>
				<li><b>SYM - MODI:</b> Modi (71168-71263)</li>
				<li><b>SYM - TAKRI:</b> Takri (71296-71375)</li>
				<li><b>SYM - WARANG:</b> Warang Citi (71840-71935)</li>
				<li><b>SYM - PAU_CIN_HAU:</b> Pau Cin Hau (72384-72447)</li>
				<li><b>SYM - CUNEIFORM:</b> Cuneiform (73728-74751)</li>
				<li><b>SYM - NUMBERS:</b> Cuneiform Numbers and Punctuation (74752-74879)</li>
				<li><b>SYM - EGYPTIAN:</b> Egyptian Hieroglyphs (77824-78895)</li>
				<li><b>SYM - BAMUM:</b> Bamum Supplement (92160-92735)</li>
				<li><b>SYM - MRO:</b> Mro (92736-92783)</li>
				<li><b>SYM - BASSA_VAH:</b> Bassa Vah (92880-92927)</li>
				<li><b>SYM - PAHAWH_HMONG:</b> Pahawh Hmong (92928-93071)</li>
				<li><b>SYM - MIAO:</b> Miao (93952-94111)</li>
				<li><b>SYM - KANA:</b> Kana Supplement (110592-110847)</li>
				<li><b>SYM - DUPLOYAN:</b> Duployan (113664-113823)</li>
				<li><b>SYM - SHORTHAND:</b> Shorthand Format Controls (113824-113839)</li>
				<li><b>SYM - MUSICAL:</b> Byzantine Musical Symbols (118784-119039)</li>
				<li><b>SYM - MUSICAL:</b> Musical Symbols (119040-119295)</li>
				<li><b>SYM - MUSICAL:</b> Ancient Greek Musical Notation (119296-119375)</li>
				<li><b>SYM - TAI:</b> Tai Xuan Jing Symbols (119552-119647)</li>
				<li><b>SYM - MATHEMATICAL:</b> Counting Rod Numerals (119648-119679)</li>
				<li><b>SYM - MATHEMATICAL:</b> Mathematical Alphanumeric Symbols (119808-120831)</li>
				<li><b>SYM - MENDE_KIKAKUI:</b> Mende Kikakui (124928-125151)</li>
				<li><b>SYM - MATHEMATICAL:</b> Arabic Mathematical Alphabetic Symbols (126464-126719)</li>
				<li><b>SYM - MAHJONG:</b> Mahjong Tiles (126976-127023)</li>
				<li><b>SYM - DOMINO:</b> Domino Tiles (127024-127135)</li>
				<li><b>SYM - CARDS:</b> Playing Cards (127136-127231)</li>
				<li><b>SYM - ALPHANUMERIC:</b> Enclosed Alphanumeric Supplement (127232-127487)</li>
				<li><b>SYM - IDEOGRAPHIC:</b> Enclosed Ideographic Supplement (127488-127743)</li>
				<li><b>SYM - MISCELLANEOUS:</b> Miscellaneous Symbols and Pictographs (127744-128511)</li>
				<li><b>SYM - EMOTICONS:</b> Emoticons (Emoji) (128512-128591)</li>
				<li><b>SYM - DINGBATS:</b> Ornamental Dingbats (128592-128639)</li>
				<li><b>SYM - TRANSPORT_MAP:</b> Transport and Map Symbols (128640-128767)</li>
				<li><b>SYM - ALCHEMICAL:</b> Alchemical Symbols (128768-128895)</li>
				<li><b>SYM - GEOMETRIC_SHAPES:</b> Geometric Shapes Extended (128896-129023)</li>
				<li><b>SYM - ARROWS:</b> Supplemental Arrows-C (129024-129279)</li>
			</ul>
		",
		"return" : "array"
	} **/
	public function groups() {
		$vGroups = [];
		foreach($this->vUTF8Groups as $vGroup) {
			$vGroups[] = \array_combine(["type","group","from","to"], $vGroup);
		}

		return $vGroups;
	}

	/** FUNCTION {
		"name" : "info", 
		"type" : "public",
		"description" : "
			Devuelve información de un caracter dado
			<ul>
				<li><b>char:</b> caracter</li>
				<li><b>type:</b> tipo de caracter</li>
				<li><b>group:</b> grupo UTF-8 al que pertenece</li>
				<li><b>bytes:</b> bytes que ocupa</li>
				<li><b>decimal:</b> valor decimal</li>
				<li><b>hexadecimal:</b> valor hexadecimal</li>
				<li><b>html:</b> código HTML</li>
				<li><b>escaped:</b> valor UNICODE escapado</li>
			</ul>
		",
		"parameters" : {
			"$sChar" : ["string", "Caracter unicode del que se desea conocer el tipo"]
		},
		"return" : "string"
	} **/
	public function info($sChar) {
		$vReturn = null;
		$nOrd = self::call("unicode")->ord($sChar);
		foreach($this->vUTF8Groups as $vRange) {
			if($nOrd>=$vRange[2] && $nOrd<=$vRange[3]) {
				$sHex = \dechex($nOrd);
				$sHex = \str_pad($sHex, 4, "0", STR_PAD_LEFT);
				$sHex = \strtoupper($sHex);

				$vReturn = [];
				$vReturn["char"]		= $sChar;
				$vReturn["type"]		= $vRange[0];
				$vReturn["group"]		= $vRange[1];
				$vReturn["bytes"]		= \strlen($sChar);
				$vReturn["decimal"]		= $nOrd;
				$vReturn["hexadecimal"]	= $sHex;
				$vReturn["html"]		= "&#".$nOrd.";";
				$vReturn["escaped"]		= "\\u".$sHex;
				break;
			}
		}

		return $vReturn;
	}

	/** FUNCTION {
		"name" : "ischr", 
		"type" : "public",
		"description" : "Retorna el tipo, grupo y valor decimal de un caracter dado, o false en caso de error",
		"parameters" : {
			"$sChar" : ["string", "Caracter unicode del que se desea conocer el tipo"]
		},
		"return" : "array"
	} **/
	public function ischr($sChar) {
		$nOrd = self::call("unicode")->ord($sChar);
		foreach($this->vUTF8Groups as $vRange) {
			if($nOrd>=$vRange[2] && $nOrd<=$vRange[3]) {
				return [$vRange[0], $vRange[1], $nOrd];
			}
		}

		return false;
	}
	
	/** FUNCTION {
		"name" : "ord", 
		"type" : "public",
		"description" : "Devuelve el valor UNICODE del caracter $sChar",
		"parameters" : {
			"$sChar" : ["string", "Caracter unicode del que se desea conocer el código"]
		},
		"return" : "integer"
	} **/
	public function ord($sChar) {
		if($this->bDisable) {
			$nOrd = \ord($sChar);
		} else {
			$nOrd = false;
			if($sChar!==null && $sChar!=="" && isset($sChar[0])) {
				$nOrd = \ord($sChar[0]);
				if($nOrd <= 0x7F) {
					$nOrd = $nOrd;
				} else if($nOrd < 0xC2) {
					$nOrd = false;
				} else if($nOrd <= 0xDF) {
					$nOrd = ($nOrd & 0x1F) << 6 | (\ord($sChar[1]) & 0x3F);
				} else if($nOrd <= 0xEF) {
					$nOrd = ($nOrd & 0x0F) << 12 | (\ord($sChar[1]) & 0x3F) << 6 | (\ord($sChar[2]) & 0x3F);
				} else if($nOrd <= 0xF4) {
					$nOrd = ($nOrd & 0x0F) << 18 | (\ord($sChar[1]) & 0x3F) << 12 | (\ord($sChar[2]) & 0x3F) << 6 | (\ord($sChar[3]) & 0x3F);
				}
			}
		}
		
		return $nOrd;
	}

	/** FUNCTION {
		"name" : "split", 
		"type" : "public",
		"description" : "Convierte $mSource en un array de caracteres UTF-8. Si $mSource es un array split retornara $mSource",
		"parameters" : {
			"$mSource" : ["mixed", "Origen de datos, string o array"]
		},
		"return" : "string"
	} **/
	public function split($mSource) {
		if($this->bDisable) {
			if(\is_string($mSource)) {
				$aSplit = \str_split($mSource);
			} else {
				$aSplit = $mSource;
			}
		} else {
			if(\is_string($mSource)) {
				$sText = $mSource;
				$sChar = "";
				$aSplit = [];
				$bUnicode = false;
				$nText = \strlen($sText);
				for($x=0; $x<$nText; $x++) {
					if((\ord($sText[$x])&0xC0)!=0x80) {
						if(\strlen($sChar)) {
							$aSplit[] = ($bUnicode) ? $sChar : \utf8_encode($sChar);
							$sChar = "";
							$bUnicode = false;
						}
					} else {
						$bUnicode = true;
					}

					$sChar .= $sText[$x];
				}

				$aSplit[] = ($bUnicode) ? $sChar : \utf8_encode($sChar);
			} else {
				$aSplit = $mSource;
			}
		}
		
		return $aSplit;
	}

	/** FUNCTION {
		"name" : "strlen", 
		"type" : "public",
		"description" : "Obtiene la longitud de una cadena",
		"parameters" : {
			"$sString" : ["string", "Origen de datos"]
		},
		"return" : "integer"
	} **/
	public function strlen($sString) {
		if($this->bDisable) {
			$nCount = \strlen($sString);
		} else {
			$nCount = 0;
			$sString = (string)$sString;
			$nString = \strlen($sString);
			for($x=0; $x<$nString; $x++) {
				if((\ord($sString[$x])&0xC0)!=0x80) {
					$nCount++;
				}
			}
		}
		return $nCount;
	}

	/** FUNCTION {
		"name" : "substr", 
		"type" : "public",
		"description" : "Devuelve la subcadena de $mSource comenzando en $nStart y por un largo de $nLength",
		"parameters" : {
			"$mSource" : ["mixed", "
				Origen de datos, string o array. Este método trabaja sobre un array, por lo que si $mSource es 
				del tipo string será convertido a un array por medio de split.
			"],
			"$nStart" : ["int", "
				Inicio de la subcadena:
				Si no es negativo, la cadena devuelta comenzará a $nStart caracteres de la posición cero.
				Si es negativo, la cadena devuelta empezará a $nStart caracteres contando desde el final de string.
				Si la longitud del string es menor o igual a $nStart, la función devolverá FALSE. 
			", "0"],
			"$nLength" : ["int", "
				Si se especifica el $nLength y es positivo, la cadena devuelta contendrá como máximo $nLength caracteres
				Si se especifica $nLength y es negativo, entonces ese número de caracteres se omiten al final de la cadena
				Si se omite el $nLength, la subcadena empezará en $nStart hasta el final de la cadena
				Si se especifica $nLength y es 0, FALSE o NULL se devolverá la subcadena comprendida entre $nStart y el final de la cadena
			", "null"]
		},
		"return" : "string"
	} **/
	public function substr($mSource, $nStart=0, $nLength=null) {
		if($this->bDisable) {
			$sSource = (\is_string($mSource)) ? $mSource : \implode($mSource);
			return (!$nLength) ? \substr($sSource, $nStart) : \substr($sSource, $nStart, $nLength);
		} else {
			$aSource = (\is_string($mSource)) ? $this->split($mSource) : $mSource;

			if(!$nLength) { $nLength = \count($aSource); }
			if($nLength) {
				$aSubString = \array_slice($aSource, $nStart, $nLength);
				$sSubString = \implode($aSubString);
				return $sSubString;
			}
		}

		return false;
	}

	public function strpad($sText, $nLength, $sString=" ", $nType=STR_PAD_RIGHT) {
		$nDiff = $this->strlen($sText) - \strlen($sText);
		return str_pad($sText, ($nLength-$nDiff), $sString, $nType);
	}

	public function str_split($sText, $nLength=1) {
		$aText = preg_split("//u", $sText, -1, PREG_SPLIT_NO_EMPTY);
		if($nLength > 1) {
			$aChunks = array_chunk($aText, $nLength);
			foreach($aChunks as $x => $sChunk) {
				$aChunks[$x] = \implode("", (array)$sChunk);
			}
			$aText = $aChunks;
		}

		return $aText;
	}

	/** FUNCTION {
		"name" : "unescape", 
		"type" : "public",
		"description" : "Desescapa una cadena UNICODE",
		"parameters" : {"$sString" : ["string", "Cadena UNICODE a decodificar"]},
		"seealso" : ["nglUnicode::unescapeChar", "nglUnicode::escape"],
		"return" : "string"
	} **/
	public function unescape($sText) {
		return \preg_replace_callback("/\\\u([0-9A-F]{4})/i", [$this, "unescapeChar"], $sText);
	}
	
	/** FUNCTION {
		"name" : "unescapeChar", 
		"type" : "private",
		"description" : "Auxiliar del método unescape",
		"parameters" : {"$sString" : ["string", "Cadena UNICODE a decodificar"]},
		"seealso" : ["nglUnicode::unescape"],
		"return" : "string"
	} **/
	private function unescapeChar($aChars) {
		$nChar = $aChars[1];
		$nChar = self::call()->hex2dec($nChar);
		return $this->chr($nChar);
	}
}

?>