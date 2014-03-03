<?php

	$map = new mapObj("/var/www/2213/chapter02/DEU_adm1.map");
	$layer = $map->getLayerByName('DEU_adm1');
	$layers = $map->getAllLayerNames();

	if (isset($_POST["submitStyle"])) {
		$style = $_POST["styles"];
		$startColor = hex2rgb($_POST["startColor"]);
		$endColor = hex2rgb($_POST["endColor"]);
		$classes = $_POST["classes"];

		$field = "";

		if (isset($_POST["field"])) {
			$field = $_POST["field"];
		} else {
			$field = "NAME_1";
		}

		if ($style == "singleSymbol") {
			# code...
		} else if ($style == "categorizedSymbol") {
			$featuresInLayer = getNumOfFeatures($map,$layer,$field);
			$colors = getColors(array($startColor[0],$startColor[1],$startColor[2]),array($endColor[0],$endColor[1],$endColor[2]),count($featuresInLayer));
			generateCategorizedStyle($map,$layer,$field,$featuresInLayer,$colors);
		} else if ($style == "graduatedSymbol") {
			if ($_POST["mode"] == "equalInterval") {
				equalInterval($map,$layer,$field,$startColor,$endColor,$classes);
			}
		}
		
		$image=$map->draw();
    	$image_url=$image->saveWebImage();
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

	function generateCategorizedStyle($map,$layer,$field,$featuresInLayer,$colors) {
		//remove all classes for layer
		while ($layer->numclasses > 0) {
		    $layer->removeClass(0);
		}

		//create classObject (set Name(Layername), set Expression(filter for different styling))
		for ($i=0; $i < count($featuresInLayer); $i++) {
			$class = new classObj($layer);
			$class->set("name",$featuresInLayer[$i]);
			$class->setExpression("('[$field]' = '$featuresInLayer[$i]')");
			
			//create styleObject
			$style = new styleObj($class);
			$style->color->setRGB($colors[$i][0],$colors[$i][1],$colors[$i][2]);
			$style->outlinecolor->setRGB(0,0,0);
			$style->width = 0.26;
		}
		//save map
		$map->save($map->mappath . "DEU_adm1.map");
	}

	function getNumOfFeatures($map,$layer,$field) {
		$resultArray = array();

		$status = $layer->open();
		$status = $layer->whichShapes($map->extent);	
		while ($shape = $layer->nextShape())
		{
			print_r($shape->values);
			echo "<br />";
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

	//generates equal interval symbols for $field of $layer with $classes
	function equalInterval($map,$layer,$field,$startColor,$endColor,$classes) {
		$min;
		$max;
		$range;

		$status = $layer->open();
		$status = $layer->whichShapes($map->extent);	
		while ($shape = $layer->nextShape())
		{
			if (!$min && !$max) {
				$min = $shape->values[$field];
				$max = $shape->values[$field];
			} else {
				if ($shape->values[$field] < $min) {
					$min = $shape->values[$field];
				} else if ($shape->values[$field] > $max) {
					$max = $shape->values[$field];
				}
			}
		}
		$layer->close();

		$range = ($max - $min) / $classes;

		//get Color for amount of classes
		$colors = getColors(array($startColor[0],$startColor[1],$startColor[2]),array($endColor[0],$endColor[1],$endColor[2]),$classes);

		//remove all classes for layer
		while ($layer->numclasses > 0) {
		    $layer->removeClass(0);
		}

		$tempMin = $min;

		//create classObject (set Name(Layername), set Expression(filter for different styling))
		for ($i=0; $i < $classes; $i++) {
			$rangeStart = $min ;
			$rangeEnd = $min + $range ;
			$class = new classObj($layer);
			$class->set("name", $rangeStart . " - " . $rangeEnd);

			//check if it is the starting class
			if ($rangeStart == $tempMin) {
				$class->setExpression("(([$field] >= $rangeStart) AND ([$field] <= $rangeEnd))");	
			} else {
				$class->setExpression("(([$field] > $rangeStart) AND ([$field] <= $rangeEnd))");
			}
			
			//create styleObject
			$style = new styleObj($class);
			$style->color->setRGB($colors[$i][0],$colors[$i][1],$colors[$i][2]);
			$style->outlinecolor->setRGB(0,0,0);
			$style->width = 0.26;

			$min += $range;
		}

		//save map
		$map->save($map->mappath . "DEU_adm1.map");
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
				// in the original implementation, 9999999 is used but
            	// since PHP has `Infinity`, we use that.
				for ($j=2; $j < count($data)+1; $j++) { 
					$variance_combinations[$j][$i] = Infinity;
				}
			}
			// print_r($lower_class_limits);
			// echo "<br />";
			// print_r($variance_combinations);
			// echo "<br />";

			for ($l=2; $l < count($data)+1 ; $l++) { 
				$sum = 0;
				$sum_squares = 0;
				$w = 0;
				$i4 = 0;

				for ($m=1; $m < $l + 1 ; $m++) { 
					$lower_class_limit = $l - $m + 1;
					// echo "Lower__Class_Limit: " . $lower_class_limit . "<br />";
					$val = $data[$lower_class_limit - 1];
					// echo "Val" . $val ."<br />";

					$w++;

					$sum += $val;
					$sum_squares += $val * $val;

					$variance = $sum_squares - ($sum * $sum) / $w;
					// echo $variance . "<br />";
					$i4 = $lower_class_limit - 1;
					// echo $i4 . "<br />";

					if ($i4 !== 0) {
						for ($j=2; $j < $n_classes + 1; $j++) {
							echo $variance_combinations[$l][$j] . "<br />";
							echo $variance + $variance_combinations[$i4][$j-1] . "<br />"; 
							if ($variance_combinations[$l][$j] >= ($variance + $variance_combinations[$i4][$j-1])) {
								echo "bigger <br/>";
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
    	// echo "MATRICES <br />";
    	// print_r($matrices["lower_class_limits"]);
     //    echo "<br />";

        // we only need lower class limits here
        $lower_class_limits = $matrices["lower_class_limits"];
        // echo "lower_class_limits <br />";
        // print_r($lower_class_limits);
 
    	// extract n_classes out of the computed matrices
    	//echo "BREAKS <br />";
    	print_r(breaks($data,$lower_class_limits,$n_classes));
    	// return breaks($data, $lower_class_limits, $n_classes);
	}

	jenks(array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16),5);
?>
<html>
	<head>
		<title>First PHP/MapScript Action</title>
		<script type="text/javascript" src="vendor/jscolor/jscolor.js"></script>
	</head>
	<body>
		<h1>Layers</h1>
		<form name="layers" action="hello.php" method="POST">
			<select name="cbLayers">
				<?php 
					foreach($layers as $layer) {
						echo "<option value='$layer'>" . $layer . "</option>";
					}	
				?>
			</select>
			<select name="test">
				<?php 
					$layer = $map->getLayerByName('DEU_adm1');
					$status = $layer->open();

					//read all attributes of layer
					$attributes = $layer->getItems();

					//iterate over layer attribtues	
					foreach ($attributes as $key => $value) {
						$isNumeric = false;
						//get shapes from layer and look up attribute values
						$status = $layer->whichShapes($map->extent);
						while ($shape = $layer->nextShape())
						{
							if (is_numeric($shape->values["$value"])) {
								$isNumeric = true;
							} else {
								$isNumeric = false;
								break 1;
							}
						}
						if ($isNumeric) {
							echo "<option value='$value'>" . $value . "</option>";
						}
					}
				?>
			</select>
			<input type="submit" name="submitLayers">
		</form>
		<hr>
		<h1>Style</h1>
		<form name="style" action="hello.php" method="POST">
			<select name="styles">
				<option value="singleSymbol">Single Symbol</option>
				<option value="categorizedSymbol">Categorized Symbol</option>
				<option value="graduatedSymbol">Graduated Symbol</option>	
			</select>
			<br />
			<select name="field">
				<?php
					if (isset($_POST['submitLayers'])) {	
						$layer = $map->getLayerByName("DEU_adm1");

						//open layer to work with it
						$status = $layer->open();

						//read all attributes of layer
						$attributes = $layer->getItems();
						
						foreach ($attributes as $key => $value) {
							echo "<option value='$value'>". $value . "</option>";
						}
					}
				?>
			</select>
			<br />
			<select name="mode">
				<option value="equalInterval">Equal Interval</option>
				<option value="quantile">Quantile (Equal Count)</option>
				<option value="naturalBreaks">Natural Breaks (Jenks)</option>
				<option value="standardDeviation">Standard Deviation</option>
				<option value="prettyBreaks">Pretty Breaks</option>	
			</select>
			<br />
			<input type="number" min="0" max="20" step="1" value="5" name="classes"/>
			<br />
			<input name="startColor" class="color"> - <input name="endColor" class="color">
			<input type="submit" name="submitStyle">
		</form>
		<hr>
			<IMG SRC=<?php echo $image_url; ?> >
		<hr>
	</body>
</html>