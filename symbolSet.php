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
?>