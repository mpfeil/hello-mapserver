<?php
	$map = new mapObj("/var/www/2213/chapter02/shape.map");

	$layers = $map->getAllLayerNames();

	function runMyFunction() {
    	echo 'I just ran a php function';
    	//$map->set("NAME","asdf");
  	}

	if (isset($_GET['hello'])) {
		runMyFunction();
	}
?>
<html>
	<head>
		<title>First PHP/MapScript Action</title>
	</head>
	<body>
		<h1>Layers</h1>
		<select name="layers" onchange="">
			<?php 
				$layers = $map->getAllLayerNames();

				foreach($layers as $layer) {
					echo "<option value='$layer'>" . $layer . "</option>";
				}	
			?>
		</select>
		<hr>
		<h1>Fields</h1>
		
			<?php 
				$layer = $map->getLayerByName('germany_adm3');
				
				$class = $layer->getClass(0);

				echo $class->name;
			?>
		
		<hr>

		<a href='hello.php?hello=true'>Run PHP Function</a>
	</body>
</html>