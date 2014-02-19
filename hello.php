<?php
	$map = new mapObj("/var/www/2213/chapter02/normal.map");
	$layers = $map->getAllLayerNames();
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
				if (isset($_POST['cbLayers'])) {
					$layer = $map->getLayerByName($_POST['cbLayers']);	 	

					//open layer to work with it
					$status = $layer->open();
					
					//read all fields of layer
					$classes = $layer->getItems();
					
					foreach ($classes as $class) {
						echo "<option value='$class'>" . $class . "</option>";
					}
				} 
			?>
		</select>
		<hr>
	</body>
</html>