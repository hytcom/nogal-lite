<?php

namespace nogal;

/** CLASS {
	"name" : "nglImage",
	"object" : "image",
	"type" : "instanciable",
	"revision" : "20160201",
	"extends" : "nglBranch",
	"interfaces" : "inglBranch",
	"description" : "Crea un objeto sobre una imagen y permite trabajar con ella.",
	"configfile" : "image.conf",
	"arguments": {
		"alpha" : ["string", "Determina si la siguiente copia tendra fondo transparente", " false"],
		"canvas_color" : ["string", "Valor hexadecimal del color del canvas", "#FFFFFF"],
		"canvas_height" : ["string", "Alto del canvas", "0"],
		"canvas_width" : ["string", "Ancho del canvas", "0"],
		"filepath" : ["mixed", "Ruta del archivo de imagen, puntero o null", "null"],
		"filter_name" : ["string", "
			Filtro que se aplicará sobre la imagen
			Los filtros disponibles son:
			<ul>
				<li><b>blur:</b> Pone borrosa la imagen</li>
				<li><b>brightness:</b> Cambia el brillo de la imagen</li>
				<li><b>colorize:</b> Como <strong>grayscale</strong>, excepto que se puede especificar el color</li>
				<li><b>contrast:</b> Cambia el contraste de la imagen</li>
				<li><b>emboss:</b> Pone en relieve la imagen</li>
				<li><b>gaussian_blur:</b> Pone borrosa la imagen usando el método Gaussiano</li>
				<li><b>grayscale:</b> Convierte la imagen a escala de grises</li>
				<li><b>negative:</b> Invierte todos los colores de la imagen</li>
				<li><b>pixelate:</b> grayscale</li>
				<li><b>sharpe:</b> Utiliza detección de borde para resaltar los bordes de la imagen</li>
				<li><b>sketch:</b> Utiliza eliminación media para lograr un efecto superficial</li>
				<li><b>smooth:</b> Suaviza la imagen</li>
			</ul>
		", "null"],
		"filter_args" : ["mixed", "
			Argumento solicitado por algunos de los filtros
			<ul>
				<li><b>brightness:</b> Nivel de brillo, rango: -255 a 255</li>
				<li><b>colorize:</b> Color hexadecimal con canal alpha: #RRGGBBAA</li>
				<li><b>contrast:</b> Nivel de contraste</li>
				<li><b>pixelate:</b> Tamaño de bloque de pixelación</li>
				<li><b>smooth:</b> Nivel de suavidad</li>
			</ul>
		", "null"],
		"height" : ["string", "Alto que se aplicará en la próxima copia de la imagen actual", "0"],
		"merge_image" : ["resource", "Puntero de la imagen que se incorporará a la imagen actual"],
		"merge_alpha" : ["string", "Determina si la imagen <b>merge_image</b> será incorporada en modo de transparencia", " true"],
		"merge_position" : ["string", "Posición de la imagen <b>merge_image</b> en el canvas actual", "center center"],
		"position" : ["string", "
			Posición de la imagen en el canvas, este valor puede ser un par ordenado de coordenadas TOP y LEFT separados por ; (punto y coma) ó , (coma) ó alguna de las siguientes combinaciones:
			<ul>
				<li>top left</li>
				<li>top center</li>
				<li>top right</li>
				<li>center left</li>
				<li>center center</li>
				<li>center right</li>
				<li>bottom left</li>
				<li>bottom center</li>
				<li>bottom right</li>
			</ul>
		", "center center"],
		"quality" : ["string", "Calidad de la imagen en el método de salida nglImage::write", "75"],
		"rc_find" : ["string", "Valor hexadecimal del color que se desea reemplazar en la imagen", "#000000"],
		"rc_replace" : ["string", "Valor hexadecimal del color con el que será reemplazado <b>rc_find</b>", "#FFFFFF"],
		"rc_tolerance" : ["string", "Grado de tolerancia (0-255) aplicado a la hora reemplazar colores", "0"],
		"text_content" : ["string", "Texto que se escribirá sobre la imagen actual"],
		"text_angle" : ["int", "Angulo de escritura", "0"],
		"text_color" : ["string", "Color del texto", "#000000"],
		"text_font" : ["string", "Ruta del archivo TTF con la que se escribirá el texto", "null"],
		"text_margin" : ["string", "Margin aplicado al texto, valor entero o dos enteros separados por un espacio", "0"],
		"text_position" : ["string", "Posición del texto dentro del canvas, en igual formato que argument::position", "center center"],
		"text_size" : ["int", "Tamaño del texto", "10"],
		"type" : ["string", "Tipo de imagen. Pueden ser: jpeg, jpg, png o gif", "jpeg"],
		"width" : ["string", "Ancho que se aplicará en la próxima copia de la imagen actual", "0"]
	},
	"attributes": {
		"data" : ["array", "Array con los datos IPTC y EXIF"],
		"height" : ["int", "Alto de la imagen actual"],
		"image" : ["resource", "Puntero de la imagen actual"],
		"info" : ["array", "Array con los datos de getimagesize"],
		"mime" : ["string", "MimeType de la imagen actual"],
		"path" : ["string", "Ruta de la imagen"],
		"size" : ["int", "Tamaño en bytes de la imagen actual"],
		"type" : ["string", "Tipo de imagen actual"],
		"width" : ["int", "Ancho de la imagen actual"]
	},
	"variables" : {
		"$image" : ["private", "Image resource"],
		"$fOutput" : ["private", "Función de salida de imagen"]
	}
} **/
class nglImage extends nglBranch implements inglBranch {

	private $image;
	private $fOutput;
	private $sType;

	final protected function __declareArguments__() {
		$vArguments						= [];
		$vArguments["alpha"]			= ['self::call()->istrue($mValue)', false];
		$vArguments["canvas_color"]		= ['(string)$mValue', "#FFFFFF"];
		$vArguments["canvas_height"]	= ['(int)$mValue', 0];
		$vArguments["canvas_width"]		= ['(int)$mValue', 0];
		$vArguments["filepath"]			= ['$mValue', null];
		$vArguments["filter_name"]		= ['$mValue', null];
		$vArguments["filter_args"]		= ['$mValue', null];
		$vArguments["height"]			= ['(int)$mValue', 0];
		$vArguments["merge_image"]		= ['$mValue'];
		$vArguments["merge_alpha"]		= ['self::call()->istrue($mValue)', true];
		$vArguments["merge_position"]	= ['strtolower($mValue)', "center center"];
		$vArguments["position"]			= ['strtolower($mValue)', "center center"];
		$vArguments["quality"]			= ['(int)$mValue', 100];
		$vArguments["rc_find"]			= ['(string)$mValue', "#000000"];
		$vArguments["rc_replace"]		= ['(string)$mValue', "#FFFFFF"];
		$vArguments["rc_tolerance"]		= ['self::call()->istrue($mValue)', 0];
		$vArguments["text_content"]		= ['(string)$mValue'];
		$vArguments["text_angle"]		= ['(int)$mValue', 0];
		$vArguments["text_color"]		= ['(string)$mValue', "#000000"];
		$vArguments["text_font"]		= ['(string)$mValue', NGL_FONT];
		$vArguments["text_margin"]		= ['(string)$mValue', 0];
		$vArguments["text_position"]	= ['(string)$mValue', "center center"];
		$vArguments["text_size"]		= ['(int)$mValue', 10];
		$vArguments["type"]				= ['(string)$mValue', "jpeg"];
		$vArguments["width"]			= ['(int)$mValue', 0];
		
		return $vArguments;
	}

	final protected function __declareAttributes__() {
		$vAttributes					= [];
		$vAttributes["data"]	 		= null;
		$vAttributes["imageheight"]	 	= null;
		$vAttributes["image"]			= null;
		$vAttributes["info"]			= null;
		$vAttributes["mime"]	 		= null;
		$vAttributes["path"]	 		= null;
		$vAttributes["size"]			= null;
		$vAttributes["imagetype"]		= null;
		$vAttributes["imagewidth"]	 	= null;
		
		return $vAttributes;
	}

	final protected function __declareVariables__() {
	}

	final public function __init__() {
	}

	/** FUNCTION {
		"name" : "base64", 
		"type" : "public",
		"description" : "Exporta el contenido de imagen para ser usado como origen de datos de <img> o css",
		"parameters" : { 
			"$bAlpha" : ["boolean", "", "argument::alpha"]
		},
		"input" : "mime",
		"examples" : {
			"Uso" : "
				echo "<img src='".$ngl("image.")->load("demo.jpg")->base64()."' />";
			"
		},
		"return" : "string"
	} **/
	public function base64() {
		list($bAlpha) = $this->getarguments("alpha", \func_get_args());

		if($bAlpha) {
			\imagealphablending($this->image, true);
			\imagesavealpha($this->image, true);
		} else {
			\imagealphablending($this->image, false);
		}

		$fOutput = $this->fOutput;
		\ob_start();
		$fOutput ($this->image);
		$sSource = \ob_get_contents();
		\ob_end_clean();

		return "data:image/".\strtolower($this->sType).";base64,".\base64_encode($sSource);
	}

	/** FUNCTION {
		"name" : "CalculatePosition",
		"type" : "private",
		"description" : "
			Calcula el TOP y LEFT en base al alto y ancho de la imagen y el alto y ancho del canvas en función del parámetro <b>$sPosition</b>.
			Cuando $nWidth = $nCanvasWidth y $nHeight = $nCanvasHeight, el método retonará 0 para el top y el left
			Este método retorna un array de 2 indices:
			<ul>
				<li>top</li>
				<li>left</li>
			</ul>
		",
		"parameters" : { 
			"$sPosition" : ["string", "Valores de entrada TOP y LEFT, en el formato de argument::position"],
			"$nWidth" : ["int", "Ancho de la imagen"],
			"$nHeight" : ["int", "Alto de la imagen"],
			"$nCanvasWidth" : ["int", "Ancho del canvas"],
			"$nCanvasHeight" : ["int", "Alto del canvas"]
		},
		"return": "array"
	} **/
	private function CalculatePosition($sPosition, $nWidth, $nHeight, $nCanvasWidth, $nCanvasHeight) {
		$nTop = $nLeft = 0;
		if($nCanvasWidth!=$nWidth || $nCanvasHeight!=$nHeight) {
			if(\strstr($sPosition, ";")) {
				\sscanf($sPosition, "%d;%d", $nTop, $nLeft);
			} else if(\strstr($sPosition, ",")) {
				\sscanf($sPosition, "%d;%d", $nTop, $nLeft);
			} else {
				\sscanf($sPosition, "%s %s", $sTop, $sLeft);
				$sTop = \strtolower($sTop);
				$sLeft = \strtolower($sLeft);
				
				// top
				if($sTop=="center") {
					$nTop = ($nCanvasHeight-$nHeight)/2;
				} else if($sTop=="bottom") {
					$nTop = ($nCanvasHeight-$nHeight);
				}

				// left
				if($sLeft=="center") {
					$nLeft = ($nCanvasWidth-$nWidth)/2;
				} else if($sLeft=="right") {
					$nLeft = ($nCanvasWidth-$nWidth);
				}
			}
		}

		return array($nTop, $nLeft);
	}

	/** FUNCTION {
		"name" : "CalculateSizes",
		"type" : "private",
		"description" : "
			Calcula el el ancho y alto de una imagen y su lienzo manteniendo la proporcionalidad
			Este método retorna un array de 4 indices:
			<ul>
				<li>ancho</li>
				<li>alto</li>
				<li>ancho del lienzo</li>
				<li>alto del lienzo</li>
			</ul>
		",
		"parameters" : { 
			"$nArgWidth" : ["int", "Nuevo ancho de la imagen"],
			"$nArgHeight" : ["int", "Nuevo alto de la imagen"],
			"$nArgCanvasWidth" : ["int", "Nuevo ancho del canvas"],
			"$nArgCanvasHeight" : ["int", "Nuevo alto del canvas"]
		},
		"input" : "width,height",
		"return": "array"
	} **/
	private function CalculateSizes($nArgWidth, $nArgHeight, $nArgCanvasWidth, $nArgCanvasHeight) {
		$nImgWidth	= $this->attribute("imagewidth");
		$nImgHeight	= $this->attribute("imageheight");

		// dimensiones de la imagen
		if($nArgWidth && !$nArgHeight) {
			$nWidth  	= $nArgWidth;
			$nHeight 	= $nImgHeight * $nWidth / $nImgWidth;
		} else if(!$nArgWidth && $nArgHeight) {
			$nHeight	= $nArgHeight;
			$nWidth 	= $nImgWidth * $nHeight / $nImgHeight;
		} else if($nArgWidth && $nArgHeight) {
			$nWidth		= $nArgWidth;
			$nHeight	= $nArgHeight;
		} else {
			$nWidth		= $nImgWidth;
			$nHeight	= $nImgHeight;
		}

		// dimensiones del lienzo
		$nCanvasWidth	= ($nArgCanvasWidth) ? $nArgCanvasWidth : $nWidth;
		$nCanvasHeight	= ($nArgCanvasHeight) ? $nArgCanvasHeight : $nHeight;

		$vSizes = array(
			\ceil($nWidth),
			\ceil($nHeight),
			\ceil($nCanvasWidth),
			\ceil($nCanvasHeight)
		);

		return $vSizes;
	}

	/** FUNCTION {
		"name" : "canvas",
		"type" : "public",
		"description" : "Redimensiona el lienzo de la imagen",
		"parameters" : { 
			"$nNewCanvasWidth" : ["int", "", "argument::canvas_width"],
			"$nNewCanvasHeight" : ["int", "", "argument::canvas_height"],
			"$sCanvasColor" : ["string", "", "argument::canvas_color"],
			"$sPosition" : ["string", "", "argument::position"],
			"$bAlpha" : ["boolean", "", "argument::alpha"]
		},
		"input" : "width,height",
		"output" : "width,height",
		"examples" : {
			"Cambio del tamaño del canvas" : "
				$ngl("image.")->load("demo.jpg")->canvas(200,200)->view();
			"
		},
		"return" : "$this"
	} **/
	public function canvas() {
		list($nNewCanvasWidth,$nNewCanvasHeight,$sCanvasColor,$sPosition,$bAlpha) = $this->getarguments(
			"canvas_width,canvas_height,canvas_color,position,alpha", \func_get_args()
		);

		$nWidth	= $this->attribute("imagewidth");
		$nHeight = $this->attribute("imageheight");
		$this->CreateCopy($nWidth, $nHeight, $nNewCanvasWidth, $nNewCanvasHeight, $bAlpha, $sPosition, $sCanvasColor);
		
		return $this;
	}

	/** FUNCTION {
		"name" : "CreateCopy",
		"type" : "private",
		"description" : "Redimensiona el lienzo de la imagen",
		"parameters" : { 
			"$nWidth" : ["int", "Ancho de la imagen"],
			"$nHeight" : ["int", "Alto de la imagen"],
			"$nCanvasWidth" : ["int", "Ancho del lienzo"],
			"$nCanvasHeight" : ["int", "Alto del lienzo"],
			"$bAlpha" : ["boolean", "Determina si la copia tendra fondo transparente"],
			"$sPosition" : ["string", "Valores de entrada TOP y LEFT, en el formato de argument::position", "center center"],
			"$sCanvasColor" : ["string", "Valor hexadecimal para el color de fondo", "#FFFFFF"]
		},
		"input" : "width,height",
		"output" : "width,height",
		"return" : "boolean"
	} **/
	private function CreateCopy($nWidth, $nHeight, $nCanvasWidth, $nCanvasHeight, $bAlpha, $sPosition="center center", $sCanvasColor="#FFFFFF") {
		$nImageWidth	= $this->attribute("imagewidth");
		$nImageHeight	= $this->attribute("imageheight");
		
		// dimensiones
		list($nNewWidth, $nNewHeight, $nNewCanvasWidth, $nNewCanvasHeight) = 
			$this->CalculateSizes($nWidth, $nHeight, $nCanvasWidth, $nCanvasHeight);

		// nueva imagen
		$hNewImage = \imagecreatetruecolor($nNewCanvasWidth, $nNewCanvasHeight);

		// posicion de la imagen en el lienzo
		$aPositions = $this->CalculatePosition($sPosition, $nNewWidth, $nNewHeight, $nNewCanvasWidth, $nNewCanvasHeight);
		$nTop = $aPositions[0];
		$nLeft = $aPositions[1];

		// color
		$vRGB = $this->GetTransparency($this->image);
		if($vRGB===false) {
			$sCanvasColor = \str_replace("#", "", $sCanvasColor);
			$vRGB = self::call()->colorRGB($sCanvasColor);
			$nColor = \imagecolorallocate($hNewImage, $vRGB["red"], $vRGB["green"], $vRGB["blue"]);
			\imagefill($hNewImage, 0, 0, $nColor);
		} else {
			$nColor = \imagecolorallocate($hNewImage, $vRGB["red"], $vRGB["green"], $vRGB["blue"]);
			\imagefill($hNewImage, 0, 0, $nColor);
			\imagecolortransparent($hNewImage, $nColor); 
		}

		// alpha
		if($bAlpha) {
			\imagealphablending($hNewImage, true);
			\imagesavealpha($hNewImage, true);
		} else {
			\imagealphablending($hNewImage, false);
		}

		\imagecopyresampled($hNewImage, $this->image, $nLeft, $nTop, 0, 0, $nNewWidth, $nNewHeight, $nImageWidth, $nImageHeight);
		$nImgWidth = \imageSX($hNewImage);
		$nImgHeight = \imageSY($hNewImage);

		$this->image = $hNewImage;

		// imagepalettecopy($hNewImage, $this->image);
		$this->attribute("imagewidth", $nImgWidth);
		$this->attribute("imageheight", $nImgHeight);

		return true;
	}

	/** FUNCTION {
		"name" : "data", 
		"type" : "public",
		"description" : "Retorna los datos IPTC y EXIF que pueda contener la imagen",
		"input" : "info,path",
		"output" : "data",
		"examples" : {
			"Datos IPTC y EXIF" : "
				print_r($ngl("image.foo")->load("readme.txt")->data());
			"
		},
		"return" : "array"
	} **/
	public function data() {
		$sMark = "APP13";
		$aInfo = $this->attribute("info");
		if(\is_array($aInfo) && \array_key_exists($sMark, $aInfo)) {
			if($aData = \iptcparse($aInfo[$sMark])) {
				$vIPTCCodes = [];
				$vIPTCCodes["2#000"] = "record_version";
				$vIPTCCodes["2#003"] = "object_type";
				$vIPTCCodes["2#004"] = "object_attribute";
				$vIPTCCodes["2#005"] = "object_name";
				$vIPTCCodes["2#007"] = "edit_status";
				$vIPTCCodes["2#008"] = "editorial_update";
				$vIPTCCodes["2#010"] = "urgency";
				$vIPTCCodes["2#012"] = "subject";
				$vIPTCCodes["2#015"] = "category";
				$vIPTCCodes["2#020"] = "supp_category";
				$vIPTCCodes["2#022"] = "fixture_id";
				$vIPTCCodes["2#025"] = "keywords";
				$vIPTCCodes["2#026"] = "location_code";
				$vIPTCCodes["2#027"] = "location_name";
				$vIPTCCodes["2#030"] = "release_date";
				$vIPTCCodes["2#035"] = "release_time";
				$vIPTCCodes["2#037"] = "expiration_date";
				$vIPTCCodes["2#038"] = "expiration_time";
				$vIPTCCodes["2#040"] = "special_instructions";
				$vIPTCCodes["2#042"] = "action_advised";
				$vIPTCCodes["2#045"] = "reference_service";
				$vIPTCCodes["2#047"] = "reference_date";
				$vIPTCCodes["2#050"] = "reference_number";
				$vIPTCCodes["2#055"] = "date_created";
				$vIPTCCodes["2#060"] = "time_created";
				$vIPTCCodes["2#062"] = "digitization_date";
				$vIPTCCodes["2#063"] = "digitization_time";
				$vIPTCCodes["2#065"] = "program";
				$vIPTCCodes["2#070"] = "program_version";
				$vIPTCCodes["2#075"] = "object_cycle";
				$vIPTCCodes["2#080"] = "byline";
				$vIPTCCodes["2#085"] = "byline_title";
				$vIPTCCodes["2#090"] = "city";
				$vIPTCCodes["2#092"] = "sub_location";
				$vIPTCCodes["2#095"] = "province_state";
				$vIPTCCodes["2#100"] = "country_code";
				$vIPTCCodes["2#101"] = "country_name";
				$vIPTCCodes["2#103"] = "transmission_reference";
				$vIPTCCodes["2#105"] = "headline";
				$vIPTCCodes["2#110"] = "credit";
				$vIPTCCodes["2#115"] = "source";
				$vIPTCCodes["2#116"] = "copyright";
				$vIPTCCodes["2#118"] = "contact";
				$vIPTCCodes["2#120"] = "caption";
				$vIPTCCodes["2#122"] = "writer";
				$vIPTCCodes["2#125"] = "rasterized_caption";
				$vIPTCCodes["2#130"] = "image_type";
				$vIPTCCodes["2#131"] = "image_orientation";
				$vIPTCCodes["2#135"] = "language";
				$vIPTCCodes["2#150"] = "audio_type";
				$vIPTCCodes["2#151"] = "audio_rate";
				$vIPTCCodes["2#152"] = "audio_resolution";
				$vIPTCCodes["2#153"] = "audio_duration";
				$vIPTCCodes["2#154"] = "audio_outcue";
				$vIPTCCodes["2#200"] = "preview_format";
				$vIPTCCodes["2#201"] = "preview_version";
				$vIPTCCodes["2#202"] = "preview";

				foreach($aData as $mKey => $aValue) {
					if(@\count($aValue)>1) {
						foreach($aValue as $sValue) {
							if(isset($vIPTCCodes[$mKey])) {
								$aResult[$vIPTCCodes[$mKey]][] = \trim($sValue);					
							} else {
								$aResult[$mKey][] = \trim($sValue);					
							}
						}
					} else {
						if(isset($vIPTCCodes[$mKey])) {
							$aResult[$vIPTCCodes[$mKey]] = \trim($aValue[0]);
						} else {
							$aResult[$mKey] = \trim($aValue[0]);
						}
					}
				}
				$aIPTCData = $aResult;
			} else {
				$aIPTCData = 0;
			}
		} else {
			$aIPTCData = 0;
		}

		$vImageData = [];
		$vImageData["EXIF"] = (\function_exists("exif_read_data")) ? \exif_read_data($this->attribute("path")) : "Undefined EXIF Functions";
		$vImageData["IPTC"] = $aIPTCData;

		$this->attribute("data", $vImageData);
		return $vImageData;
	}
	
	/** FUNCTION {
		"name" : "filter", 
		"type" : "public",
		"description" : "Aplica un filtro o efecto sobre la imagen actual",
		"parameters" : { 
			"$sFilter" : ["string", "", "argument::filter_name"],
			"$mValue" : ["mixed", "", "argument::filter_args"]
		},
		"examples" : {
			"Aplicando filtros" : "
				$img = $ngl("image.foo")->load("demo.jpg");
				$img->filter("blur")->filter("emboss")->filter("colorize", "#FF990066")->view();
			"
		},
		"return" : "$this"
	} **/
	public function filter() {
		list($sFilter,$mValue) = $this->getarguments("filter_name,filter_args", \func_get_args());

		switch(\strtolower($sFilter)) {
			case "negative":
				\imagefilter($this->image, IMG_FILTER_NEGATE);
				break;

			case "grayscale":
				\imagefilter($this->image, IMG_FILTER_GRAYSCALE);
				break;

			case "sharpe":
				\imagefilter($this->image, IMG_FILTER_EDGEDETECT);
				break;

			case "emboss":
				\imagefilter($this->image, IMG_FILTER_EMBOSS);
				break;

			case "gaussian_blur":
				\imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
				break;

			case "blur":
				\imagefilter($this->image, IMG_FILTER_SELECTIVE_BLUR);
				break;

			case "sketch":
				\imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
				break;

			case "brightness":
				\imagefilter($this->image, IMG_FILTER_BRIGHTNESS, (int)$mValue);
				break;

			case "contrast":
				\imagefilter($this->image, IMG_FILTER_CONTRAST, (int)$mValue);
				break;

			case "smooth":
				\imagefilter($this->image, IMG_FILTER_SMOOTH, (int)$mValue);
				break;

			case "pixelate":
				\imagefilter($this->image, IMG_FILTER_PIXELATE, (int)$mValue, true);
				break;

			case "colorize":
				$vRGB = self::call()->colorRGB($mValue);
				\imagefilter($this->image, IMG_FILTER_COLORIZE, $vRGB["red"], $vRGB["green"], $vRGB["blue"], $vRGB["alpha"]);
				break;
		}
		
		return $this;
	}

	/** FUNCTION {
		"name" : "GetTransparency", 
		"type" : "private",
		"description" : "Obtiene el grado de transparencia de la imagen",
		"parameters" : { 
			"$hSourceImage" : ["resource", "Imagen"]
		},
		"return" : "array"
	} **/
	private function GetTransparency($hSourceImage) {
		$nIndex = \imagecolortransparent($hSourceImage);
		if($nIndex >= 0) {
			$aColors = \imagecolorsforindex($hSourceImage, $nIndex);
			return $aColors;
		} else {
			return false;
		}
	}

	/** FUNCTION {
		"name" : "image", 
		"type" : "public",
		"description" : "Retorna el puntero de la imagen para ser utilizado en otro proceso",
		"parameters" : { 
			"$bAlpha" : ["boolean", "", "argument::alpha"]
		},
		"examples" : {
			"Obtener el puntero de la imagen" : "
				$img = $ngl("image.foo")->load("demo.jpg");
				$img->filter("blur")->filter("emboss")->margin(10);
				
				imagepng($img->image(), "demo2.jpg");
			"
		},
		"return" : "resource"
	} **/
	public function image() {
		list($bAlpha) = $this->getarguments("alpha", \func_get_args());

		if($bAlpha) {
			\imagealphablending($this->image, true);
			\imagesavealpha($this->image, true);
		} else {
			\imagealphablending($this->image, false);
		}

		return $this->image;
	}

	/** FUNCTION {
		"name" : "load", 
		"type" : "public",
		"description" : "
			Carga la imagen en el objeto.
			Si el parámetro $mFile fuese null, se creará una imagen vacia de 1x1 px
		",
		"parameters" : { 
			"$mFile" : ["mixed", "", "argument::filepath"],
			"$sType" : ["string", "", "argument::type"]
		},
		"output": "imageheight,info,mime,path,imagetype,imagewidth",
		"examples" : {
			"Archivo de imagen" : "
				$ngl("image.foo")->load("demo.jpg")->view();
			",
			"Carga de puntero" : "
				$ngl("image.foo")->load(
					$ngl("qr.bar")->image("test1234")
				)->view();
			",
			"Imagen vacia" : "
				$img = $ngl("image.foo");
				$img->text_font = "./roboto.ttf";
				$img->load()->resize(120,40)->text("hola mundo!", "#ffffff")->view();
			",
		},
		"return" : "$this o FALSE"
	} **/
	public function load() {
		list($mFile,$sType) = $this->getarguments("filepath,type", \func_get_args());

		$sType = \strtolower($sType);
		if($sType=="jpg") { $sType = "jpeg"; }

		$this->sType = $sType;
		if(empty($mFile)) {
			$this->fOutput = "Image".$sType;
			$image = \ImageCreate(1,1);
		} else if(\is_resource($mFile)) {
			$this->fOutput = "Image".$sType;
			$image = $mFile;
		} else {
			$sFileName = self::call()->clearPath($mFile);
			if(self::call()->isURL($sFileName)) {
				$file = self::call("file")->load($sFileName);
				$dst = self::call("file")->load($file->fileinfo()["basename"])->write($file->read());
				$sFileName = $dst->path;
			}

			if(isset($sFileName) && !empty($sFileName)) {
				$sFileName = self::call()->sandboxPath($sFileName);
				if(\file_exists($sFileName)) {
					if($vInfo = \getimagesize($sFileName, $aImageInfo)) {
						$aType = \explode("/", $vInfo["mime"]);
						$sType = $aType[1];

						$this->fOutput = "Image".$sType;
						$fCreate = "ImageCreateFrom".$aType[1];
						$image = $fCreate ($sFileName);
						
						$this->attribute("path", $sFileName);
						$this->attribute("info", $aImageInfo);
					} else {
						self::errorMessage($this->object, 1001);
						return false;
					}
				}
			}
		}
		
		if(isset($image)) {
			$this->attribute("mime", "image/".$sType);
			$this->attribute("imagetype", $sType);
			$this->attribute("imagewidth", \imageSX($image));
			$this->attribute("imageheight", \imageSY($image));
			$this->image = $image;
			return $this;
		}

		return false;
	}

	/** FUNCTION {
		"name" : "margin", 
		"type" : "public",
		"description" : "
			Añade un margen por fuera de los limites de la imagen.
			Si una imagen mide 100px de ancho y se le añaden 10px de margen, el nuevo ancho será de 120px
		",
		"parameters" : { 
			"$nMargin" : ["int", "", "argument::margin"],
			"$sCanvasColor" : ["string", "", "argument::canvas_color"]
		},
		"input" : "width,height",
		"output" : "width,height",
		"examples" : {
			"Margen de 10 pixeles" : "
				$ngl("image.")->load("demo.jpg")->margin(10)->view();
			"
		},
		"return" : "$this"
	} **/
	public function margin() {
		list($nMargin,$sCanvasColor) = $this->getarguments("margin,canvas_color", \func_get_args());

		$nWidth	= $this->attribute("imagewidth");
		$nHeight = $this->attribute("imageheight");
		$sPosition = "center center";
		$this->CreateCopy($nWidth, $nHeight, ($nWidth+$nMargin*2), ($nHeight+$nMargin*2), $this->alpha, $sPosition, $sCanvasColor);
		
		return $this;
	}

	/** FUNCTION {
		"name" : "padding", 
		"type" : "public",
		"description" : "
			Añade un margen por dentro de los limites de la imagen.
			Si una imagen mide 100px de ancho y se le añaden 10px de padding, el ancho seguirá siendo de 100px, pero el fotograma pasará a medir 80px de ancho
		",
		"parameters" : { 
			"$nPadding" : ["int", "", "argument::padding"],
			"$sCanvasColor" : ["string", "", "argument::canvas_color"]
		},
		"input" : "width,height",
		"examples" : {
			"Padding de 10 pixeles" : "
				$ngl("image.")->load("demo.jpg")->padding(10)->view();
			"
		},
		"return" : "$this"
	} **/
	public function padding() {
		list($nPadding,$sCanvasColor) = $this->getarguments("padding,canvas_color", \func_get_args());

		$nWidth	= $this->attribute("imagewidth");
		$nHeight = $this->attribute("imageheight");
		$sPosition = "center center";
		$this->CreateCopy(($nWidth-$nPadding*2), ($nHeight-$nPadding*2), $nWidth, $nHeight, $this->alpha, $sPosition, $sCanvasColor);
		
		return $this;
	}
	
	/** FUNCTION {
		"name" : "merge", 
		"type" : "public",
		"description" : "Inserta una imagen dentro de otra",
		"parameters" : { 
			"$image" : ["resource", "", "argument::merge_image"],
			"$sPosition" : ["string", "", "argument::merge_position"],
			"$bAlpha" : ["boolean", "", "argument::merge_alpha"]
		},
		"input" : "width,height",
		"examples" : {
			"Marca de agua" : "
				$logo = $ngl("image.logo")->load("logo.png");
				$img = $ngl("image.photo")->load("demo.jpg");
				$img->merge($logo->image(), "center center", true);
				$img->view();
			"
		},
		"return" : "$this"
	} **/
	public function merge() {
		list($image,$sPosition,$bAlpha) = $this->getarguments("merge_image,merge_position,merge_alpha", \func_get_args());

		$nWidth 		= \imageSX($image);
		$nHeight 		= \imageSY($image);
		$nCanvasWidth	= $this->attribute("imagewidth");
		$nCanvasHeight	= $this->attribute("imageheight");
		$sPosition 		= \strtolower($sPosition);
		
		// posicion de la imagen en el lienzo
		$aPositions = $this->CalculatePosition($sPosition, $nWidth, $nHeight, $nCanvasWidth, $nCanvasHeight);

		$nTop = $aPositions[0];
		$nLeft = $aPositions[1];

		// alpha modo
		if($bAlpha) {
			\imagesavealpha($this->image, true);
			\imagealphablending($this->image, true);
		} else {
			\imagealphablending($this->image, false);
		}

		\imageCopyResampled($this->image, $image, $nLeft, $nTop, 0, 0, $nWidth, $nHeight, $nWidth, $nHeight);
		
		return $this;
	}

	/** FUNCTION {
		"name" : "resize", 
		"type" : "public",
		"description" : "Redimensiona una imagen",
		"parameters" : { 
			"$nNewWidth" : ["int", "Nuevo ancho de la imagen, o 0 para que el nuevo ancho sea proporcional al nuevo alto", "argument::width"],
			"$mNewHeight" : ["string", "
				Este argumento puede tomar diferentes valores, según el tipo redimensionamiento que se quiera emplear.
				Cuando el valor sea <b>min</b> o <b>max</b>, el valor de $nNewWidth será interpretado como ancho o alto, segén corresponda
				<ul>
					<li><b>0:</b> para conseguir un alto proporcional al nuevo ancho</li>
					<li><b>int:</b> número entero que representa el nuevo ancho</li>
					<li><b>min:</b> hará que el valor de $nNewWidth sea el lado mas chico de la imagen, ya sea alto o ancho</li>
					<li><b>max:</b> hará que el valor de $nNewWidth sea el lado mas grande de la imagen, ya sea alto o ancho</li>
				</ul>
			", "argument::height"],
			"$bAlpha" : ["boolean", "", "argument::alpha"]
		},
		"input" : "width,height",
		"output" : "width,height",
		"examples" : {
			"Cambio de alto y ancho" : "
				$ngl("image.")->load("demo.jpg")->resize(800,800)->view();
			",
			"Ancho proporcional al alto" : "
				$ngl("image.")->load("demo.jpg")->resize(0,800)->view();
			",
			"300px para el lado mas grande de la imagen" : "
				$ngl("image.")->load("demo.jpg")->resize(300,"max")->view();
			"
		},
		"return" : "$this"
	} **/
	public function resize() {
		list($nNewWidth,$mNewHeight,$bAlpha) = $this->getarguments("width,height,alpha", \func_get_args());

		$bVertical = ($this->attribute("imagewidth")>$this->attribute("imageheight"));

		switch(true) {
			case (\strtolower($mNewHeight)==="max"):
				$nTemp = $nNewWidth;
				$nNewWidth	= (!$bVertical) ? 0 : $nTemp;
				$nNewHeight	= (!$bVertical) ? $nTemp : 0;
				break;

			case \strtolower($mNewHeight)==="min":
				$nTemp = $nNewWidth;
				$nNewWidth	= ($bVertical) ? 0 : $nTemp;
				$nNewHeight	= ($bVertical) ? $nTemp : 0;
				break;
			
			default:
				$nNewHeight = $mNewHeight;
		}

		$this->CreateCopy($nNewWidth, $nNewHeight, $nNewWidth, $nNewHeight, $bAlpha);
		return $this;
	}

	/** FUNCTION {
		"name" : "replace", 
		"type" : "public",
		"description" : "
			Reemplaza un color por otro.
			El reemplazo de colores en una imagen no es algo sencillo, mucho colores pueden parecer iguales a la vista, pero no lo son.
			Por ello este método es mas eficiente en el reemplazo de colores plenos en imagenes simples, como códigos QR, de barras o textos
		",
		"parameters" : { 
			"$sFind" : ["string", "", "argument::rc_find"],
			"$sReplace" : ["string", "", "argument::rc_replace"],
			"$nTolerance" : ["int", "", "argument::rc_tolerance"]
		},
		"input" : "width,height",
		"examples" : {
			"Reemplazo sin tolerancia" : "
				# Reemplaza el blanco pleno por rojo
				$ngl("image.")->load("demo.jpg")->replace("#FFFFFF", "#FF0000", 0)->view();
			",
			"Reemplazo con tolerancia" : "
				# Reemplaza tonalidades de azul por azul pletno
				$ngl("image.")->load("demo.jpg")->replace("#0000FF", "#0000FF", 50)->view();
			"
		},
		"return" : "$this"
	} **/
	public function replace() {
		list($sFind,$sReplace,$nTolerance) = $this->getarguments("rc_find,rc_replace,rc_tolerance", \func_get_args());

		$vFindMin = $vFindMax = self::call()->colorRGB($sFind);
		$vFindMin["red"]	-= $nTolerance;
		$vFindMin["green"]	-= $nTolerance;
		$vFindMin["blue"]	-= $nTolerance;

		$vFindMax["red"]	+= $nTolerance;
		$vFindMax["green"]	+= $nTolerance;
		$vFindMax["blue"]	+= $nTolerance;

		$vReplace = self::call()->colorRGB($sReplace);
		
		$nWidth	= $this->attribute("imagewidth");
		$nHeight = $this->attribute("imageheight");

		for($x=0;$x<$nWidth;$x++) {
			for($y=0;$y<$nHeight;$y++) {
				$nColor = \imagecolorat($this->image, $x, $y);
				$nRed = ($nColor >> 16) & 0xFF;
				$nGreen = ($nColor >> 8) & 0xFF;
				$nBlue = $nColor & 0xFF;

				if(
					($vFindMin["red"]<=$nRed && $nRed<=$vFindMax["red"]) && 
					($vFindMin["green"]<=$nGreen && $nGreen<=$vFindMax["green"]) && 
					($vFindMin["blue"]<=$nBlue && $nBlue<=$vFindMax["blue"])
				) {
					$nNewColor = \imagecolorallocate($this->image, $vReplace["red"], $vReplace["green"], $vReplace["blue"]); 
					\imagesetpixel($this->image, $x, $y, $nNewColor);
				}
			}
		}

		return $this;
	}

	/** FUNCTION {
		"name" : "text", 
		"type" : "public",
		"description" : "Inserta una imagen dentro de otra",
		"parameters" : { 
			"$sText" : ["string", "", "argument::text_content"],
			"$sColor" : ["string", "", "argument::text_color"],
			"$sPosition" : ["string", "", "argument::text_position"],
			"$sMargin" : ["string", "", "argument::text_margin"],
			"$nFont" : ["int", "", "argument::text_size"],
			"$nAngle" : ["int", "", "argument::text_angle"],
			"$bAlpha" : ["boolean", "", "argument::merge_alpha"],
			"$sFont" : ["string", "", "argument::text_font"]
		},
		"examples" : {
			"Imagen vacia con texto" : "
				$img = $ngl("image.");
				$img->text_font = "fonts/roboto.ttf";
				$img->load()->resize(120,40)->text("Foo Bar Text", "#FFFFFF", "bottom left")->view();
			",
			"Texto sobre una imagen" : "
				$img = $ngl("image.");
				$img->text_font = "fonts/roboto.ttf";
				$img->load("demo.jpg")->text("www.mydomain.com", "#FFFF00", "bottom right", -10)->view();
			"
		},
		"return" : "$this"
	} **/
	public function text() {
		list($sText,$sColor,$sPosition,$sMargin,$nFont,$nAngle,$sFont) = $this->getarguments("text_content,text_color,text_position,text_margin,text_size,text_angle,text_font", \func_get_args());
		if(!\file_exists($sFont)) {
			self::errorMessage($this->object, 1002);
			return false;
		}
		
		$vColor = self::call()->colorRGB($sColor);

		\imagealphablending($this->image, true);

		$nColor = \imagecolorallocate($this->image, $vColor["red"], $vColor["green"], $vColor["blue"]);
		
		$aMargin = \explode(" ", $sMargin);
		if(!isset($aMargin[1])) { $aMargin[1] = $aMargin[0]; }

		// caja circundante
		$vBox = \imageftbbox($nFont, $nAngle, $sFont, $sText);
		$nWidth = \imagesx($this->image);
		$nHeight = \imagesy($this->image);
		$nTextWidth = \abs($vBox[0]) + \abs($vBox[2]);
		$nTextHeight = \abs($vBox[1]) + \abs($vBox[5]);
		
		$aPositions = $this->CalculatePosition($sPosition, $nTextWidth, $nTextHeight, $nWidth, $nHeight);
		
		$nLeft	= $aPositions[1]+$aMargin[1];
		$nTop	= ($aPositions[0]+$nFont)+$aMargin[0];

		\imagefttext($this->image, $nFont, $nAngle, $nLeft, $nTop, $nColor, $sFont, $sText);
		return $this;
	}

	/** FUNCTION {
		"name" : "view", 
		"type" : "public",
		"description" : "Exportar la imagen al navegador",
		"parameters" : { 
			"$bAlpha" : ["boolean", "", "argument::alpha"]
		},
		"input" : "mime",
		"examples" : {
			"Salida al navegador" : "
				$ngl("image.")->load("demo.jpg")->view();
			"
		},
		"return" : "void"
	} **/
	public function view() {
		list($bAlpha) = $this->getarguments("alpha", \func_get_args());

		if($bAlpha) {
			\imagealphablending($this->image, true);
			\imagesavealpha($this->image, true);
		} else {
			\imagealphablending($this->image, false);
		}

		$fOutput = $this->fOutput;
		\header("Content-type: ".$this->attribute("mime"));
		$fOutput ($this->image);
		exit();
	}

	/** FUNCTION {
		"name" : "write", 
		"type" : "public",
		"description" : "Exportar la imagen a un archivo",
		"parameters" : { 
			"$sFilePath" : ["string", "", "argument::filepath"],
			"$nQuality" : ["int", "", "argument::quality"]
		},
		"examples" : {
			"Generar una miniatura" : "
				$ngl("image.")->load("demo.jpg")->resize(140,"max")->write("images/thumb.jpg");
			"
		},
		"return" : "$this"
	} **/
	public function write() {
		list($sFilePath,$nQuality) = $this->getarguments("filepath,quality", \func_get_args());

		if(!empty($sFilePath) && !self::call()->isURL($sFilePath)) {
			$sFilePath = self::call()->clearPath($sFilePath);
			$sFilePath = self::call()->sandboxPath($sFilePath);
			$vPath = \pathinfo($sFilePath);
			$sExtension = \strtolower($vPath["extension"]);
			$fOutput = $this->fOutput;

			$bAction = false;
			switch($sExtension) {
				case "jpeg":
				case "jpg":
					$bAction = $fOutput ($this->image, $sFilePath, $nQuality);
					break;

				case "gif":
					$bAction = $fOutput ($this->image, $sFilePath);
					break;

				case "png":
					$nQuality = 10 - \ceil($nQuality/100);
					$bAction = $fOutput ($this->image, $sFilePath, $nQuality);
					break;
			}
		}

		return $this;
	}
}

?>