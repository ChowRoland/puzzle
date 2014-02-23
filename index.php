<?php
	// MultiDimensional Array Sort
	function customsort1($a, $b) { 
			return $a["packingStart"] - $b["packingStart"];
	}
	function customsort2($a, $b) { 
			return $a["orderId"] - $b["orderId"];
	}

	function getpostvar($varname, $defaultvar = '') {
		if (isset($_POST[$varname])) {
			$rtnvar	= $_POST[$varname];
		}
		else {
			$rtnvar	= isset($_GET[$varname])	? $_GET[$varname] 	: $defaultvar;
		}
		return $rtnvar;
	}

	$errormsg  = array();

	$isFormPost	= getpostvar("isFormPost");
	$maxwidth	= getpostvar("canvasWidth",800);
	$maxsecond	= getpostvar("canvasHeight",600);
	
	if (!$isFormPost) {
		$order = array(
					array("orderId" => 1, "packingStart" => 224, 	"duration" => 69, 	"Overlap" => 0, "Group" => 0, "left" => 0, "width" => 0),
					array("orderId" => 2, "packingStart" => 335, 	"duration" => 91, 	"Overlap" => 0, "Group" => 0, "left" => 0, "width" => 0),
					array("orderId" => 3, "packingStart" => 23, 	"duration" => 47, 	"Overlap" => 0, "Group" => 0,  "left" => 0, "width" => 0),
					array("orderId" => 4, "packingStart" => 130, 	"duration" => 52, 	"Overlap" => 0, "Group" => 0, "left" => 0, "width" => 0),
					array("orderId" => 5, "packingStart" => 5, 		"duration" => 183, 	"Overlap" => 0, "Group" => 0, "left" => 0, "width" => 0),
					array("orderId" => 6, "packingStart" => 253, 	"duration" => 71, 	"Overlap" => 0, "Group" => 0, "left" => 0, "width" => 0),
					array("orderId" => 7, "packingStart" => 41, 	"duration" => 68, 	"Overlap" => 0, "Group" => 0, "left" => 0, "width" => 0)
				);
	}
	else {
		$order = array();
		$PostOrderId = 1;
		for ($i = 1; $i < 11; $i++) {
			$PostPackingStart 	= getpostvar("Start$i",0);
			$PostDuration 		= getpostvar("Duration$i",0);
			// If the Period of time to pack the order is longer than the canvas size, then throw error for the order
			if ($PostPackingStart > 0 && $PostDuration > 0) {
				$packingPeriod 	= $PostPackingStart + $PostDuration;
				if ($packingPeriod > $maxsecond) {
					$errormsg[] = array (
						"orderId" 		=> $PostOrderId++,
						"message" 		=> "Packing Period is out of bound"
					);
				}
				else {
					$order[] = array (
						"orderId" 		=> $PostOrderId++,
						"packingStart" 	=> $PostPackingStart,
						"duration"		=> $PostDuration,
						"Overlap" 		=> 0, 
						"Group" 		=> 0, 
						"left" 			=> 0, 
						"width" 		=> 0
					);
				}
			}
			else {
				if ($PostPackingStart > 0 || $PostDuration > 0) {
					$errormsg[] = array (
						"orderId" 		=> $PostOrderId++,
						"message" 		=> "Invalid PackingStart or Duration"
					);
				}
			}
		}
	}
	
	// Sort the order Array
	usort($order, "customsort1");
	
	// Create a new Array for checking Overlap
	$overlapCheck = array();

	for ($i = 0; $i < count($order); $i++) {
		// Get the Start time and Period of time to pack the order
		$startPeriod 	= $order[$i]["packingStart"];
		$packingPeriod 	= $startPeriod + $order[$i]["duration"];
		// Initialize the variable to create new OverLapCheck Array
		$createnew = true;
		// Loop thru OverLapCheck Array to compare if order if within the OverLapCheck Array
		for ($j = 0; $j < count($overlapCheck); $j++) {
			// Check is Order in within the Overlap Start
			if ($startPeriod < $overlapCheck[$j]['Start'] && $packingPeriod > $overlapCheck[$j]['Start']) {
				$overlapCheck[$j]['Start'] = $startPeriod;
				if ($packingPeriod > $overlapCheck[$j]['End']) {
					$overlapCheck[$j]['End'] = $packingPeriod;
				}
				$createnew = false;
			}
			elseif ($startPeriod >= $overlapCheck[$j]['Start'] && $startPeriod <= $overlapCheck[$j]['End']) {
				if ($packingPeriod > $overlapCheck[$j]['End']) {
					$overlapCheck[$j]['End'] = $packingPeriod;
				}
				$createnew = false;
			}
			elseif ($packingPeriod > $overlapCheck[$j]['Start'] && $packingPeriod <= $overlapCheck[$j]['End']) {
				$createnew = false;
			}
			if (!$createnew) {
				//Assign the Overlap Array Number to the Order
				$order[$i]["Overlap"] = $j;
				// Set grouping in the Overlap Array so that conflict orders will have equal width based on the number of grouping in the Overlap
				$createnewGroup = true;
				$NumberOfGroup = count($overlapCheck[$j]["Group"]);
				for ($k = 0; $k < $NumberOfGroup; $k++) {
					if ($overlapCheck[$j]["Group"][$k]['GroupEnd'] < $startPeriod) {
						$overlapCheck[$j]["Group"][$k]['GroupEnd'] = $packingPeriod;
						$createnewGroup = false;
						$order[$i]["Group"] = $overlapCheck[$j]["Group"][$k]['Number'];
						break;
					}
				}
				if ($createnewGroup) {
					$order[$i]["Group"] = ++$NumberOfGroup;
					$overlapCheck[$j]["Group"][] = array (
						"Number" 		=> $NumberOfGroup,
						"GroupStart" 	=> $startPeriod,
						"GroupEnd"	 	=> $packingPeriod,
					);
				}
			}
		};
		// Order is not in any OverLapCheck Array, so create a new OverLapCheck Array
		if ($createnew) {
			//Assign the Overlap Array Number to the Order
			$order[$i]["Overlap"] = $j;
			$order[$i]["Group"] = 1;
			$overlapCheck[] = array (
				"Start" => $startPeriod,
				"End"	=> $packingPeriod,
				"Group" => array (
					array (
						"Number" 		=> 1,
						"GroupStart" 	=> $startPeriod,
						"GroupEnd"	 	=> $packingPeriod
					)
				)
			);
		}
	};
	// Set the Left and Width parameter of the order
	for ($i = 0; $i < count($order); $i++) {
		$numberOfOverlap = count($overlapCheck[$order[$i]["Overlap"]]["Group"]);
		$orderWidth = floor(($maxwidth - $numberOfOverlap) / $numberOfOverlap);
		$orderGroup = $order[$i]["Group"];
		$orderLeft  = ($orderGroup - 1) * $orderWidth + $orderGroup;
		if ($orderGroup == $numberOfOverlap) {
			$orderWidth = $maxwidth - $orderLeft - 1;
		}
		$order[$i]["width"] = $orderWidth + 1;
		$order[$i]["left"]  = $orderLeft;
	};
	// Re-order Array by orderId
	usort($order, "customsort2");

//	echo json_encode($order);
?>

<html>
  <head>
    <title>Puzzle 1</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="Content-language" content="en"/>
	<meta name="description" content=""/>
	<meta name=""/>
	<link rel="stylesheet" href="style.css" type="text/css" media="all" />
    <script type="text/javascript">
      function draw(){
        var canvas = document.getElementById('puzzle');
        if (canvas.getContext){
			var sw = canvas.getContext('2d');
			sw.font = "10pt Helvetica";
			<?php
				for ($i = 0; $i < count($order); $i++) {
					echo "sw.fillStyle = '#3c882d';\n";
					$left = $order[$i]['left'];
					$width = $order[$i]['width'];
					echo "sw.fillRect(" .$left. "," .$order[$i]['packingStart']. "," .$width. "," .$order[$i]['duration']. ");\n";
					echo "sw.fillStyle = '#fff';\n";
					$left = $left + 1;
					$width = $width - 2;
					echo "sw.fillRect(" .$left. "," .$order[$i]['packingStart']. "," .$width. "," .$order[$i]['duration']. ");\n";
					echo "sw.fillStyle = '#000';\n";
					$text_x = $order[$i]['left'] + 5;
					$text_y = $order[$i]['packingStart'] + 15;
					echo "sw.fillText('# " .$order[$i]['orderId']. "', " .$text_x. ", " .$text_y. ");\n";
				}

//				for ($i = 1; $i < 10; $i++) {
//					$y = $i * 60;
//					echo "sw.moveTo(-5," .$y. ");";
//					echo "sw.lineTo(5," .$y. ");";
//					echo "sw.stroke();\n";
//				}
			?>
        }
      }
    </script>
  </head>
  <body onload="draw();">
	<div class="wrapper">
		<div class="wrapper_left">
			<div class="wrapper_title">Order Fulfillment</div>
			<form method="post">
			<input type="hidden" name="isFormPost" value="true">
			<div class="wrapper_form">
				<div>
					<span class="formcell">&nbsp;</span>
					<span class="formcell">Width</span>
					<span class="formcell">Height</span>
				</div>
				<div>
					<span class="formcell">Canvas</span>
					<span class="formcell"><input type="text" name="canvasWidth"  id="canvasWidth"  value="<?php echo $maxwidth ?>"  class="input_50"></span>
					<span class="formcell"><input type="text" name="canvasHeight" id="canvasHeight" value="<?php echo $maxsecond ?>" class="input_50"></span>
				</div>
				<div class="divider">&nbsp;</div>
				<div>
					<span class="formcell">ID</span>
					<span class="formcell">Start (sec)</span>
					<span class="formcell">Duration (sec)</span>
				</div>
				<?php for ($i = 0,$j = 1; $i < count($order); $i++, $j++) { ?>
				<div>
					<span class="formcell"><?php echo $j ?></span>
					<span class="formcell"><input type="text" name="Start<?php echo $j ?>" id="Start<?php echo $j ?>" value="<?php echo $order[$i]["packingStart"] ?>" class="input_50"></span>
					<span class="formcell"><input type="text" name="Duration<?php echo $j ?>" id="Duration<?php echo $j ?>" value="<?php echo $order[$i]["duration"] ?>" class="input_50"></span>
				</div>
				<?php } ?>
				<?php for ($i = count($order); $i < 10; $i++, $j++) { ?>
				<div>
					<span class="formcell"><?php echo $j ?></span>
					<span class="formcell"><input type="text" name="Start<?php echo $j ?>" id="Start<?php echo $j ?>" value="" class="input_50"></span>
					<span class="formcell"><input type="text" name="Duration<?php echo $j ?>" id="Duration<?php echo $j ?>" value="" class="input_50"></span>
				</div>
				<?php } ?>
				<div class="divider">&nbsp;</div>
				<div style="text-align: center">
					<input type="submit" name="Generator" id="Generator" value="Draw Canvas" class="submit_button">
				</div>
			</div>
			</form>
		</div>
		<div class="wrapper_right">
			<div class="wrapper_result">
				<?php
				echo "<strong>JSON Result:</strong><br>";
				echo "[";
				for ($i = 0; $i < count($order); $i++) {
					if ($i == 0) {
						echo "{";
					}
					else {
						echo ",<br>{";
					}
					echo '  "orderId": ' 		.$order[$i]['orderId'];
					echo ', "packingStart": ' 	.$order[$i]['packingStart'];
					echo ', "duration": ' 		.$order[$i]['duration'];
					echo ', "left": ' 			.$order[$i]['left'];
					echo ', "width": ' 			.$order[$i]['width']. '}';
				}
				if (count($errormsg)) {
					echo ",<br>";
					echo "<strong>Error:</strong><br>";
					for ($i = 0; $i < count($errormsg); $i++) {
						if ($i == 0) {
							echo "{";
						}
						else {
							echo ",<br>{";
						}
						echo '  "orderId": ' 		.$errormsg[$i]['orderId'];
						echo ', "packingStart": ' 	.$order[$i]['packingStart'];
						echo ', "duration": ' 		.$order[$i]['duration'];
						echo ', "left": ';
						echo ', "width": ';
						echo ', "message": ' 	.$errormsg[$i]['message']. '}';
					}
				}
				echo "]";
				?>
			</div>
			<div class="scale">
				<ul>
				<?php
					$interval = floor($maxsecond/60);
					for ($i = 0; $i <= $interval; $i++) {
						echo "<li>" .$i. ":00 -<li>";
					}
				?>
				</ul>
			</div>
			<div style="margin-top: 20px;">
				<canvas id="puzzle" width="<?php echo $maxwidth ?>" height="<?php echo $maxsecond ?>"></canvas>
			</div>
		</div>
		<div class="cl">&nbsp;</div>
	</div>
  </body>
</html>