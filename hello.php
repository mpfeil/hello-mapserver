<?php

	require_once('statistics.php');
	require_once('functions.php');
	require_once('MapScriptHelper.php');

	echo "/var/www/2213/chapter02/DEU_adm1.map <br/>";
	echo "/var/www/2213/chapter02/lines.map <br/>";
	echo "/var/www/2213/chapter02/points.map";

	if (isset($_POST["applyNewStyle"])) {

		if (isset($_POST["size_list"])) {
			updateStyles($_POST["mapfileLocation"],$_POST["cbLayers"],$_POST["size_list"],$_POST["color_list"]);
		}

		$map = new mapObj($_POST["mapfileLocation"]);
		$image = $map->draw();
    	$image_url = $image->saveWebImage();
    	$legend = $map->drawLegend();
    	$legend_url = $legend->saveWebImage();
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
							
							if ($style == "graduatedSymbol") {
								$attributes = getLayerAttributes($layerData,$layerName,true);
								foreach ($attributes as $key => $fieldName) {
									echo "<option value='$fieldName'>" . $fieldName . "</option>";	
								}	
							} else {
								$attributes = getLayerAttributes($layerData,$layerName);
								foreach ($attributes as $key => $fieldName) {
									echo "<option value='$fieldName'>" . $fieldName . "</option>";	
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
			<hr>
			<table>
				<thead>
					<tr>
						<th>Symbol</th>
						<th></th>
						<th>Value</th>
					</tr>
				</thead>
				<tbody>
					<?php
						if ($_POST["submitStyle"] || $_POST["applyNewStyle"]) {
							// $symbols = "";
							// $symbolSet = new SymbolSet($map->symbolsetfilename);

							// for ($i=0; $i < count($symbolSet->getSymbols()); $i++) {
							// 	$symbolName = $symbolSet->getSymbols()[$i]->getName();
							// 	$symbols = $symbols . "<option value='$symbolName'>$symbolName</option>";
							// }

							for ($i=0; $i < $layer->numclasses; $i++) { 
								$class = $layer->getClass($i);
								$styleOfClass = $class->getStyle(0);
								$color = array($styleOfClass->color->red,$styleOfClass->color->green,$styleOfClass->color->blue);
								$color = rgb2hex($color);
								if ($layer->type == 0) { //Point
									$size = $styleOfClass->size;	
								} else if ($layer->type == 1) { //Line
									$size = $styleOfClass->width;
								} else if ($layer->type == 2) { //Polygon
									$size = $styleOfClass->width;
								}
								echo "<tr>";
								echo "<td><input type='number' min='0' max='20' step='any' value='$size' name='size_list[]'/></td><td><input name='color_list[]' class='color' value='$color'></td><td><input type='text' value='$class->name' name='exp_list[]' readonly></td>";
								// echo "<td><select name='selectsymbol'>$symbols</select></td><td>$class->name</td>";
								echo "</tr>";
							}
						}
						
					?>
				</tbody>
			</table>
			<input type="submit" name="applyNewStyle" value="Apply">
		</form>
		<hr>
		<div style="float:left;width:600px;">
			<IMG SRC=<?php echo $image_url; ?> >	
		</div>
		<div style="float:left;margin:50px 50px;">
			<IMG SRC=<?php echo $legend_url; ?> >	
		</div>
	</body>
</html>