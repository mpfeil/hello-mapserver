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
						echo $status;

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