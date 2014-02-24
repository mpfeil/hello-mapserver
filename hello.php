<?php
	$map = new mapObj("/var/www/2213/chapter02/DEU_adm1.map");
	// $map->set('name', 'asdfghj');
	// $map->save($map->mappath . "DEU_adm1.map");

	$layers = $map->getAllLayerNames();

	$image=$map->draw();
    $image_url=$image->saveWebImage();

    $layer = $map->getLayerByName('DEU_adm1');

	$featuresInLayer = getNumOfFeatures($map,$layer);
	
	$colors = getColors(array(128,175,27),array(255,255,140),$featuresInLayer);

	generateCategorizedStyle($map,$layer,$featuresInLayer);

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

	function generateCategorizedStyle($map,$layer,$featuresInLayer) {
		
		//remove all classes for layer
		for ($i=0; $i<$layer->numclasses; $i++)
		{
		    $oClass = $layer->getClass($i);
		    $layer->removeClass($i);
		}

		//TODO iterate over all colors and features and generate classes and styles

		//create classObject (set Name, set Expression)
		$class = new classObj($layer);
		$class->set("name","Berlin");
		$class->setExpression("('[NAME_1]' = 'Berlin')");
		
		//create styleObject
		$style = new styleObj($class);
		$style->color->setRGB(128,175,27);
		$style->outlinecolor->setRGB(0,0,0);
		$style->width = 0.26;

		//save map
		$map->save($map->mappath . "DEU_adm1.map");
	}

	function getNumOfFeatures($map,$layer) {
		$result = 0;

		$status = $layer->open();
		$status = $layer->whichShapes($map->extent);	
		while ($shape = $layer->nextShape())
		{
			$result++;
		}
		$layer->close();

		return $result;
	}
?>
<html>
	<head>
		<title>First PHP/MapScript Action</title>
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
			<input type="submit">
		</form>
		<hr>
			<IMG SRC=<?php echo $image_url; ?> >
		<hr>
		<h1>Style</h1>
		<form name="style" action="hello.php" method="POST">
			<select name="styles">
				<option value="singleSymbol">Single Symbol</option>
				<option value="categorizedSymbol">Categorized Symbol</option>
				<option value="graduatedSymbol">Graduated Symbol</option>	
			</select>
			<input type="submit">
		</form>
		<hr>
		<h1>Fields</h1>
		<select name="fields">
			<?php
				// if (isset($_POST['cbLayers'])) {
					// $layer = $map->getLayerByName($_POST['cbLayers']);	
					$layer = $map->getLayerByName("DEU_adm1");


					//open layer to work with it
					$status = $layer->open();
					echo $status;

					//read all attributes of layer
					$attributes = $layer->getItems();
					
					foreach ($attributes as $key => $value) {
						echo "<option value='$key'>" . $key . ":" . $value . "</option>";
					}
				// } 
			?>
		</select>
		<hr>
	</body>
</html>