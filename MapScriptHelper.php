<?php
	class SymbolSet {
		private $file = "";
		private $symbols = array();

		public function __construct($file) {
			$this->file = $file;
			$this->readFile();
		}

		public function setFile($newFile) {
			$this->file = $newFile;
		}

		public function getFile() {
			return $this->file;
		}

		private function readFile() {
			$file_handle = fopen($this->file, "rb");

			while (!feof($file_handle)) {
				$line = fgets($file_handle);

				//trim whitespaces and tabs and new lines
				$line = trim($line, "\t");
				$line = trim($line, " ");
				$line = rtrim($line, "\t");
				$line = rtrim($line, " ");
				$line = rtrim($line, "\n");

				//ignore lines starting with #
				if (strpos($line, "#") !== 0) {
					if ($line == "SYMBOL") {
						$closingTagsRequiered++;
						$newSymbol = new Symbol(); 
					}
					if (strpos($line, "NAME") === 0) {
						$name = split(" ", $line)[1];
						$name = trim($name, '"');
						$name = rtrim($name, '"');
						// $newSymbol->setName(split(" ", $line)[1]);	
						$newSymbol->setName($name);	
					}
					if (strpos($line, "TYPE") === 0) {
						$newSymbol->setType(split(" ", $line)[1]);
					}
					if (strpos($line, "POINTS") === 0) {
						$closingTagsRequiered++;
					}
					if (strpos($line, "END") === 0) {
						$closingTagsFound++;
					}
				};

				if ($closingTagsRequiered && $closingTagsFound) {
					if ($closingTagsFound == $closingTagsRequiered) {
						$this->addSymbol($newSymbol);
						$closingTagsRequiered = 0;
						$closingTagsFound = 0;
					}
				}
				
			}
			fclose($file_handle);
		}

		private function addSymbol($symbol) {
			array_push($this->symbols, $symbol);
		}

		public function getSymbols() {
			return $this->symbols;
		}

		public function getSymbolById($index) {
			return $this->symbols[$index];
		}

		public function getSymbolByName($name) {
			//TODO: return symbol from array symbols by searching for the name		
		}
	}

	class Symbol {

		private $anchorpoint;
		private $antialias;
		private $character;
		private $filled;
		private $font;
		private $image;
		private $name;
		private $points;
		private $transparent;
		private $type;

		public function __construct() {

		}

		/*
			Getter for attributes
		*/
		public function getName($name) {
			return $this->name;
		}

		public function getFilled($filled) {
			return $this->filled;
		}

		public function getAnchorpoint($anchorpoint) {	
			return $this->anchorpoint;
		}

		public function getTransparent($transparent) {
			return $this->transparent;
		}

		public function getPoints($points) {
			return $this->points;
		}

		public function getType($type) {
			return $this->type;
		}

		/*
			Setter for attributes
		*/
		public function setName($name) {
			$this->name = $name;
		}

		public function setFilled($filled) {
			$this->filled = $filled;
		}

		public function setAnchorpoint($anchorpoint) {	
			$this->anchorpoint = $anchorpoint;
		}

		public function setTransparent($transparent) {
			$this->transparent = $transparent;
		}

		public function setPoints($points) {
			$this->points = $points;
		}

		public function setType($type) {
			$this->type = $type;
		}
	}

	function saveToMapFile($map,$layer,$field,$style,$breaks,$colors,$mapfile) {
		$symbol = $style;
		//remove old classes for layer $layer
		while ($layer->numclasses > 0) {
		    $layer->removeClass(0);
		}
		
		//create classObject (set Name(Layername), set Expression(filter for different styling))
		for ($i=0; $i < count($breaks); $i++) {
			$class = new classObj($layer);
			
			if ($symbol == "categorizedSymbol") {
				$class->set("name",$breaks[$i]);
				$class->setExpression("('[$field]' = '$breaks[$i]')");	
			} else {
				$j= $i+1;
				//check if it is the starting class
				if ($i == 0) {
					$class->set("name", $breaks[$i] . " - " . $breaks[$j]);
					$class->setExpression("(([$field] >= $breaks[$i]) AND ([$field] <= $breaks[$j]))");	
				} else if ($i < count($breaks)-1) {
					$class->set("name", $breaks[$i] . " - " . $breaks[$j]);
					$class->setExpression("(([$field] > $breaks[$i]) AND ([$field] <= $breaks[$j]))");
				}	
			}

			//create styleObject
			$style = new styleObj($class);
			$style->color->setRGB($colors[$i][0],$colors[$i][1],$colors[$i][2]);
			$style->outlinecolor->setRGB(0,0,0);

			if ($layer->type == 0) { //Point
				$style->size = rand(4,12);
				$style->outlinecolor->setRGB(0,0,0);
				$style->symbolname = "sld_mark_symbol_circle_filled";	
			} else if ($layer->type == 1) { //Line
				// $style->updateFromString("PATTERN 40 10 END");
				$style->width = 2;
				// $style2 = new styleObj($class);
				// $style2->updateFromString("GAP 50 INITIALGAP 20");
				// $style2->symbolname = "circlef";
				// $style2->color->setRGB(0,0,0);
				// $style2->size = 8;
			} else if ($layer->type == 2) { //Polygon
				$style->width = 0.26;
				// $style2 = new styleObj($class);
				// $style2->symbolname = "downwarddiagonalfill";
				// $style2->color->setRGB(0,0,0);
				// // $style2->outlinecolor->setRGB(0,0,0);
				// $style2->size = 35;
				// $style2->width = 5;
			}
		}

		//save map
		$map->save($mapfile);	
	}

	function updateStyles($mapfile,$layerName,$newStyle) {

		$map = new mapObj($mapfile);
		$layer = $map->getLayerByName($layerName);

		for ($i=0; $i < $layer->numclasses; $i++) { 
			$class = $layer->getClass($i);
			$styleOfClass = $class->getStyle(0);

			if ($layer->type == 0) { //Point
				$styleOfClass->size = $newStyle[$i];
			} else if ($layer->type == 1) { //Line
				$styleOfClass->width = $newStyle[$i];
			} else if ($layer->type == 2) { //Polygon
				$styleOfClass->width = $newStyle[$i];
			}
		}

		$map->save($mapfile);
	}

	function getLayerAttributes($dataSource, $layerName, $onlyContinuesAttributes) {
		$ogrinfoQuery = 'ogrinfo -q ' . $dataSource . ' -sql "SELECT * FROM ' . $layerName . '" -fid 1';
		$ogrinfo = array();
		$result = array();
		exec($ogrinfoQuery,$ogrinfo);
		if ($onlyContinuesAttributes) {
			for ($i=3; $i < count($ogrinfo); $i++) {
				if (strpos($ogrinfo[$i], "(Real)") || strpos($ogrinfo[$i], "(Integer)")) {
					$field = explode(" (", $ogrinfo[$i]);
					$field = trim($field[0]);
					array_push($result, $field);
				}
			}	
		} else {
			for ($i=3; $i < count($ogrinfo); $i++) {
				$field = explode(" (", $ogrinfo[$i]);
				$field = trim($field[0]);
				array_push($result, $field);
			}	
		}

		return $result;
	}
?>