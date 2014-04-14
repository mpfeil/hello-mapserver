<?php
	echo "/var/www/2213/chapter02/DEU_adm1.map <br/>";
	echo "/var/www/2213/chapter02/lines.map <br/>";
	echo "/var/www/2213/chapter02/points.map";

	// $sym1 = $map->getsymbolobjectbyid(0);
	// $sym2 = $map->getsymbolobjectbyid(1);
	// $sym3 = $map->getsymbolobjectbyid(2);
	// $sym4 = $map->getsymbolobjectbyid(3);

	// echo $sym1->name . "....<br />";
	// echo $sym2->name . "<br />";
	// echo $sym3->name . "<br />";
	// echo $sym4->name . "<br />";

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

			if ($layer->type == 0) {
				$style->size = 8;
				$style->outlinecolor->setRGB(0,0,0);
				$style->symbolname = "sld_mark_symbol_circle_filled";	
			} else if ($layer->type == 1) {
				// $style->updateFromString("PATTERN 40 10 END");
				$style->width = 2;
				// $style2 = new styleObj($class);
				// $style2->updateFromString("GAP 50 INITIALGAP 20");
				// $style2->symbolname = "circlef";
				// $style2->color->setRGB(0,0,0);
				// $style2->size = 8;
			} else if ($layer->type == 2) {
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

	//generates equal interval symbols for $field of $layer with $classes $map,$layer,$field,$classes
	function equalInterval($data, $classes) {
		$min = min($data);;
		$max = max($data);
		$range = ($max - $min) / $classes;
		$resultArray = array();

		array_push($resultArray, $min);
		for ($i=0; $i < $classes; $i++) { 
			$test = $resultArray[$i]+$range;
			array_push($resultArray, $test);
		}
		
		return $resultArray;
	}

	function jenks($data, $n_classes) {

		// Compute the matrices required for Jenks breaks. These matrices
    	// can be used for any classing of data with `classes <= n_classes`
		function getMatrices($data,$n_classes) {

			// in the original implementation, these matrices are referred to
	        // as `LC` and `OP`
	        //
	        // * lower_class_limits (LC): optimal lower class limits
	        // * variance_combinations (OP): optimal variance combinations for all classes
			$lower_class_limits = array();
			$variance_combinations = array();
			// loop counters
			$i;
			$j;
			// the variance, as computed at each step in the calculation
			$variance = 0;

			// Initialize and fill each matrix with zeroes
			for ($i=0; $i < count($data)+1; $i++) { 
				$tmp1 = array();
				$tmp2 = array();
				for ($j=0; $j < $n_classes + 1; $j++) { 
					array_push($tmp1, 0);
					array_push($tmp2, 0);
				}
				array_push($lower_class_limits, $tmp1);
				array_push($variance_combinations, $tmp2);
			}

			for ($i=1; $i < $n_classes + 1; $i++) { 
				$lower_class_limits[1][$i] = 1;
				$variance_combinations[1][$i] = 0;
				for ($j=2; $j < count($data)+1; $j++) { 
					$variance_combinations[$j][$i] = 9999999;
				}
			}

			for ($l=2; $l < count($data)+1 ; $l++) {
				$sum = 0;
				$sum_squares = 0;
				$w = 0;
				$i4 = 0;

				for ($m=1; $m <  $l + 1; $m++) {
					
					$lower_class_limit = $l - $m + 1;

					$val = $data[$lower_class_limit - 1];
					
					$w++;
					
					$sum += $val;
					$sum_squares += $val * $val;
	
					$variance = $sum_squares - ($sum * $sum) / $w;
				
					$i4 = $lower_class_limit - 1;
			
					if ($i4 !== 0) {
						for ($j=2; $j < $n_classes + 1; $j++) {
							if ($variance_combinations[$l][$j] >= ($variance + $variance_combinations[$i4][$j-1])) {
								$lower_class_limits[$l][$j] = $lower_class_limit;
								$variance_combinations[$l][$j] = $variance + $variance_combinations[$i4][$j-1];
							}
						}
					}
				}
				
				$lower_class_limits[$l][1] = 1;
            	$variance_combinations[$l][1] = $variance;
			}

			return array(
				"lower_class_limits"=>$lower_class_limits,
				"variance_combinations"=>$variance_combinations
			);
		}

		function breaks($data, $lower_class_limits, $n_classes) {
			$k = count($data) - 1;
			$kclass = array();
			$countNum = $n_classes;

			// the calculation of classes will never include the upper and
	        // lower bounds, so we need to explicitly set them
	        $kclass[$n_classes] = $data[count($data)-1];
	        $kclass[0] = $data[0];

	        // the lower_class_limits matrix is used as indexes into itself
        	// here: the `k` variable is reused in each iteration.
	        while ($countNum > 1) {
	        	$kclass[$countNum-1] = $data[$lower_class_limits[$k][$countNum]-2];
	        	$k = $lower_class_limits[$k][$countNum]-1;
	        	$countNum--;
	        }
	 
	        return $kclass;
		}

		if ($n_classes > count($data)) {
			return null;
		}

		//sort data in numerical order, expected by matrices function 
		sort($data);

		// get our basic matrices
    	$matrices = getMatrices($data, $n_classes);

        // we only need lower class limits here
        $lower_class_limits = $matrices["lower_class_limits"];
        $breaks = breaks($data, $lower_class_limits, $n_classes);
        ksort($breaks);
    	return $breaks;
	}

	function quantile($data, $classes) {
		sort($data);
		$n = count($data);
		$breaks = array();

		foreach(range(0,$classes-1) as $i) {
			$q = $i / (float)$classes;
			$a = $q * $n;
			$aa = (int)$q * $n;
			$r = $a - $aa;
			$Xq = (1 - $r) * $data[$aa] + $r * $data[$aa+1];
			array_push($breaks, $Xq);
		}
		array_push($breaks, $data[$n-1]);

		return $breaks;
	}

	function standardDeviation($data,$classes) {
		$mean = 0.0;
		$sd2 = 0.0;
		$n = count($data);
		$min = min($data);
		$max = max($data);
		for ($i=0; $i < $n; $i++) { 
			$mean = $mean + $data[$i];
		}
		$mean = $mean / $n;
		for ($i=0; $i < $n; $i++) {
			$sd = $data[$i] - $mean;
			$sd2 += $sd * $sd;
		}
		$sd2 = sqrt($sd2 / $n);
		$res = rpretty(($min-$mean)/$sd2, ($max-$mean)/$sd2, $classes);
		$res2 = array();
		foreach ($res as $val) {
			$tempVal = ($val*$sd2)+$mean;
			array_push($res2, $tempVal);
		}
		return $res2;
	}

	function rpretty($dmin, $dmax, $n) {
		$resultArray = array();
		$min_n = (int)($n / 3);
		$shrink_sml = 0.75;
		$high_u_bias = 1.5;
		$u5_bias = 0.5 + 1.5 * $high_u_bias;
		$h = $high_u_bias;
		$h5 = $u5_bias;
		$ndiv = $n;

		$dx = $dmax - $dmin;
		if ($dx == 0 && $dmax == 0) {
			$cell = 1.0;
			$i_small = True;
			$U = 1;
		} else {
			$cell = max(abs($dmin),abs($dmax));
			if ($h5 >= 1.5 * $h + 0.5) {
				$U = 1 + (1.0/(1+$h));
			} else {
				$U = 1 + (1.5 / (1 + $h5));
    			$i_small = $dx < ($cell * $U * max(1.0, $ndiv) * 1e-07 * 3.0);
			}
		}

		if ($i_small) {
			if ($cell > 10) {
				$cell = 9 + $cell / 10;
      			$cell = $cell * $shrink_sml;	
			}
			if ($min_n > 1) {
				$cell = $cell / $min_n;
			}
		} else {
			$cell = $dx;
			if ($ndiv > 1) {
				$cell = $cell / $ndiv;
			}
		}

		if ($cell < 20 * 1e-07) {
			$cell = 20 * 1e-07;
		}

		$base = pow(10.0, floor(log10($cell))); 
		$unit = $base;
		if ((2 * $base) - $cell < $h * ($cell - $unit)) {
			$unit = 2.0 * $base;
			if ((5 * $base) - $cell < $h5 * ($cell - $unit)) {
				$unit = 5.0 * $base;
				if ((10 * $base) - $cell < $h * ($cell - $unit)) {
					$unit = 10.0 * $base;
				}
			}
		}

		$ns = floor($dmin / $unit + 1e-07);
		$nu = ceil($dmax / $unit - 1e-07);

		while ($ns * $unit > $dmin + (1e-07 * $unit)) {
			$ns = $ns - 1;
		}
		while ($nu * $unit < $dmax - (1e-07 * $unit)) {
			$nu = $nu + 1;
		}

		$k = floor(0.5 + $nu-$ns);
		if ($k < $min_n) {
			$k = $min_n - $k;
			if ($ns >= 0) {
				$nu = $nu + $k / 2;
				$ns = $ns - $k / 2 + $k % 2;
			} else {
				$ns = $ns - $k / 2;
		      	$nu = $nu + $k / 2 + $k % 2	;
			}
		} else {
			$ndiv = $k;
		}

		$graphmin = $ns * $unit;
		$graphmax = $nu * $unit;

		$count = (int)(ceil($graphmax - $graphmin))/$unit;
		foreach(range(0,$count) as $i) {
			$tempVal = $graphmin + $i * $unit;
			array_push($resultArray, $tempVal);
		}

		if ($resultArray[0] < $dmin) {
			$resultArray[0] = $dmin;
		}
		if ($resultArray[count($resultArray)-1] > $dmax) {
			$resultArray[count($resultArray)-1] = $dmax;
		}
		return $resultArray;
	}

	function pretty($data,$classes) {
		$min = min($data);
		$max = max($data);
		
		return rpretty($min,$max,$classes);	
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
							$ogrinfoQuery = 'ogrinfo -q ' . $layerData . ' -sql "SELECT * FROM ' . $layerName . '" -fid 1';
							$ogrinfo = array();
							exec($ogrinfoQuery,$ogrinfo);
							
							if ($style == "graduatedSymbol") {
								for ($i=3; $i < count($ogrinfo); $i++) {
									if (strpos($ogrinfo[$i], "(Real)") || strpos($ogrinfo[$i], "(Integer)")) {
										$field = explode(" (", $ogrinfo[$i]);
										$field = trim($field[0]);
										echo "<option value='$field'>" . $field . "</option>";
									}
								}	
							} else {
								for ($i=3; $i < count($ogrinfo); $i++) {
									$field = explode(" (", $ogrinfo[$i]);
									$field = trim($field[0]);
									echo "<option value='$field'>" . $field . "</option>";
								}	
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
							for ($i=0; $i < $map->getNumSymbols(); $i++) { 
								$symbols += "<option value='$map->getSymbolObjectById($i)'>$map->getSymbolObjectById($i)</option>";
							}
							// $layer = $map->getLayerByName($_POST["cbLayers"]);
							for ($i=0; $i < $layer->numclasses; $i++) { 
								$class = $layer->getClass($i);
								echo "<tr>";
								echo "<td><select>$symbols</select></td><td>$class->name</td>";
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