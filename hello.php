<?php

	require_once('statistics.php');
	require_once('MapScriptHelper.php');

	echo "/var/www/2213/chapter02/DEU_adm1.map <br/>";
	echo "/var/www/2213/chapter02/lines.map <br/>";
	echo "/var/www/2213/chapter02/points.map";

	if (isset($_POST["selectsymbol"])) {
		echo "SELECTSYMBOL";
	}

	if (isset($_POST["submitStyle"])) {
		$style = $_POST["styles"];
		$startColor = hex2rgb($_POST["startColor"]);
		$endColor = hex2rgb($_POST["endColor"]);
		$classes = $_POST["classes"];

		$field = "";

		if (isset($_POST["field"])) {
			$field = trim($_POST["field"]);
		} else {
			$field = "NAME_1";
		}

		if ($_POST["mapfileLocation"] != "" && $_POST["cbLayers"] != "") {
			$map = new mapObj($_POST["mapfileLocation"]);
			$layer = $map->getLayerByName($_POST["cbLayers"]);
			$mapfile = $_POST["mapfileLocation"];
		}
		
		if ($style == "singleSymbol") {
			# code...
		} else if ($style == "categorizedSymbol") {
			$featuresInLayer = getNumOfFeatures($map,$layer,$field);
			$colors = getColors(array($startColor[0],$startColor[1],$startColor[2]),array($endColor[0],$endColor[1],$endColor[2]),count($featuresInLayer));
			saveToMapFile($map,$layer,$field,$style,$featuresInLayer,$colors,$mapfile);
		} else if ($style == "graduatedSymbol") {
			if ($_POST["mode"] == "equalInterval") {
				$data = getNumOfFeatures($map,$layer,$field);
				$breaks = equalInterval($data,$classes);
				$colors = getColors(array($startColor[0],$startColor[1],$startColor[2]),array($endColor[0],$endColor[1],$endColor[2]),count($breaks));
				saveToMapFile($map,$layer,$field,$style,$breaks,$colors,$mapfile);
			} else if ($_POST["mode"] == "naturalBreaks") {
				$data = getNumOfFeatures($map,$layer,$field);
				$breaks = jenks($data,$classes);
				$colors = getColors(array($startColor[0],$startColor[1],$startColor[2]),array($endColor[0],$endColor[1],$endColor[2]),count($breaks));
				saveToMapFile($map,$layer,$field,$style,$breaks,$colors,$mapfile);
			} else if ($_POST["mode"] == "quantile") {
				$data = getNumOfFeatures($map,$layer,$field);
				$breaks = quantile($data,$classes);
				$colors = getColors(array($startColor[0],$startColor[1],$startColor[2]),array($endColor[0],$endColor[1],$endColor[2]),count($breaks));
				saveToMapFile($map,$layer,$field,$style,$breaks,$colors,$mapfile);
			} else if ($_POST["mode"] == "standardDeviation") {
				$data = getNumOfFeatures($map,$layer,$field);
				$breaks = standardDeviation($data,$classes);
				$colors = getColors(array($startColor[0],$startColor[1],$startColor[2]),array($endColor[0],$endColor[1],$endColor[2]),count($breaks));
				saveToMapFile($map,$layer,$field,$style,$breaks,$colors,$mapfile);
			} else if ($_POST["mode"] == "prettyBreaks") {
				$data = getNumOfFeatures($map,$layer,$field);
				$breaks = pretty($data,$classes);
				$colors = getColors(array($startColor[0],$startColor[1],$startColor[2]),array($endColor[0],$endColor[1],$endColor[2]),count($breaks));
				saveToMapFile($map,$layer,$field,$style,$breaks,$colors,$mapfile);
			}
		}
		
		$image = $map->draw();
    	$image_url = $image->saveWebImage();
    	$legend = $map->drawLegend();
    	$legend_url = $legend->saveWebImage();

    	//generate SLD for GeoExt StyleStore
    	$sld = $map->generateSLD(); 
 
    	// save sld to a file
		$fp = fopen("parcel-sld.xml", "a");
		fputs( $fp, $sld );
		fclose($fp);
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
		// $map->save($map->mappath . "points.map");	
	}

	//Generates an array of colors for a colorramp and a number of features
	function getColors($startColor, $endColor, $count) {
		$resultArray = array();

		$c1 = $startColor; // start color
		$c2 = $endColor; // end color
		$nc = $count; // Number of colors to display.
		$dc = array(($c2[0]-$c1[0])/($nc-1),($c2[1]-$c1[1])/($nc-1),($c2[2]-$c1[2])/($nc-1)); // Step between colors

		for ($i=0;$i<$nc;$i++){
			$newColor = array(round($c1[0]+$dc[0]*$i),round($c1[1]+$dc[1]*$i),round($c1[2]+$dc[2]*$i));
		    array_push($resultArray, $newColor);
		}

		return $resultArray;	
	}

	function getNumOfFeatures($map,$layer,$field) {
		$resultArray = array();

		$status = $layer->open();
		$status = $layer->whichShapes($map->extent);	
		while ($shape = $layer->nextShape())
		{
			if (!in_array($shape->values[$field], $resultArray)) {
				array_push($resultArray, $shape->values[$field]);
			}
		}
		$layer->close();

		return $resultArray;
	}

	//Convert hex color code to rgb
	function hex2rgb($hex) {
	   $hex = str_replace("#", "", $hex);

	   if(strlen($hex) == 3) {
	      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   } else {
	      $r = hexdec(substr($hex,0,2));
	      $g = hexdec(substr($hex,2,2));
	      $b = hexdec(substr($hex,4,2));
	   }
	   $rgb = array($r, $g, $b);
	   //return implode(",", $rgb); // returns the rgb values separated by commas
	   return $rgb; // returns an array with the rgb values
	}
?>
<html>
	<head>
		<title>First PHP/MapScript Action</title>
		<script type="text/javascript" src="vendor/jscolor/jscolor.js"></script>
	</head>
	<body>
		<hr>
		<form name="style" action="hello.php" method="POST">
			<h1>Mapfile</h1>
			<input type="text" name="mapfileLocation" <?php if ($_POST['mapfileLocation'] != '') {
				echo "value=".$_POST['mapfileLocation'];
			}?>>
			<input type="submit" name="submitLayers" value="Get layers">
			<h1>Layers</h1>
			<select name="cbLayers" onchange="document.style.submit();">
				<?php
					echo "<option value=''>-- Select a layer --</option>";
					if (isset($_POST["mapfileLocation"]) != "") {
						$map = new mapObj($_POST["mapfileLocation"]);
						$layers = $map->getAllLayerNames();
						foreach($layers as $layer) {
							if ($_POST["cbLayers"] != "" && $_POST["cbLayers"] == $layer) {
									echo "<option value='$layer' selected>" . $layer . "</option>";
							} else {
								echo "<option value='$layer'>" . $layer . "</option>";
							}
							
						}
					}
				?>
			</select>
			<h1>Style</h1>
			<select name="styles" onchange="document.style.submit();">
				<option <?php if ($_POST["styles"] == "singleSymbol") {
					echo 'selected="selected"';
				}  ?> value="singleSymbol">Single Symbol</option>
				<option <?php if ($_POST["styles"] == "categorizedSymbol") {
					echo 'selected="selected"';
				}  ?>  value="categorizedSymbol">Categorized Symbol</option>
				<option <?php if ($_POST["styles"] == "graduatedSymbol") {
					echo 'selected="selected"';
				}  ?>  value="graduatedSymbol">Graduated Symbol</option>	
			</select>
			<br />
			<select name="field">
				<?php
					if (!empty($_POST['cbLayers'])) {
						$layerName = $_POST["cbLayers"];
						$layer = $map->getLayerByName("$layerName");
						$layerData = $layer->data;
						
						if ($layer) {
							$style = $_POST["styles"];

							// $ogrinfoQuery = 'ogrinfo -q ' . $layerData . ' -sql "SELECT * FROM ' . $layerName . '" -fid 1';
							// $ogrinfo = array();
							// exec($ogrinfoQuery,$ogrinfo);
							
							if ($style == "graduatedSymbol") {
								$attributes = getLayerAttributes($layerData,$layerName,true);
								foreach ($attributes as $key => $fieldName) {
									echo "<option value='$fieldName'>" . $fieldName . "</option>";	
								}
								// for ($i=3; $i < count($ogrinfo); $i++) {
								// 	if (strpos($ogrinfo[$i], "(Real)") || strpos($ogrinfo[$i], "(Integer)")) {
								// 		$field = explode(" (", $ogrinfo[$i]);
								// 		$field = trim($field[0]);
								// 		echo "<option value='$field'>" . $field . "</option>";
								// 	}
								// }	
							} else {
								$attributes = getLayerAttributes($layerData,$layerName);
								foreach ($attributes as $key => $fieldName) {
									echo "<option value='$fieldName'>" . $fieldName . "</option>";	
								}
								// for ($i=3; $i < count($ogrinfo); $i++) {
								// 	$field = explode(" (", $ogrinfo[$i]);
								// 	$field = trim($field[0]);
								// 	echo "<option value='$field'>" . $field . "</option>";
								// }	
							}
						}
					}
				?>
			</select>
			<br />
			<select <?php if ($_POST["styles"] != "graduatedSymbol") {echo 'hidden';} ?> name="mode">
				<option value="equalInterval">Equal Interval</option>
				<option value="quantile">Quantile (Equal Count)</option>
				<option value="naturalBreaks">Natural Breaks (Jenks)</option>
				<option value="standardDeviation">Standard Deviation</option>
				<option value="prettyBreaks">Pretty Breaks</option>	
			</select>
			<br />
			<input <?php if ($_POST["styles"] != "graduatedSymbol") {echo 'hidden';} ?> type="number" min="1" max="20" step="1" value="5" name="classes"/>
			<br />
			<input name="startColor" class="color"> - <input name="endColor" class="color">
			<input type="submit" name="submitStyle">
		</form>
		<hr>
			<table>
				<thead>
					<tr>
						<th>Symbol</th>
						<th>Value</th>
					</tr>
				</thead>
				<tbody>
					<?php
						if ($_POST["submitStyle"]) {
							$symbols = "";
							$symbolSet = new SymbolSet($map->symbolsetfilename);

							for ($i=0; $i < count($symbolSet->getSymbols()); $i++) {
								$symbolName = $symbolSet->getSymbols()[$i]->getName();
								$symbols = $symbols . "<option value='$symbolName'>$symbolName</option>";
							}

							for ($i=0; $i < $layer->numclasses; $i++) { 
								$class = $layer->getClass($i);
								echo "<tr>";
								echo "<td><select name='selectsymbol'>$symbols</select></td><td>$class->name</td>";
								echo "</tr>";
							}
						}
						
					?>
				</tbody>
			</table>
		<hr>
		<div style="float:left;width:600px;">
			<IMG SRC=<?php echo $image_url; ?> >	
		</div>
		<div style="float:left;margin:50px 50px;">
			<IMG SRC=<?php echo $legend_url; ?> >	
		</div>
	</body>
</html>