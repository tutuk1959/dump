<?php
	session_start();
	include_once 'classes/db.class.php';
	$db = new DB();
	foreach ($db->table("SELECT a.MSE,a.epoch FROM msepelatihan AS a WHERE a.idPelatihan = '%s' AND Mod(a.epoch, 1000)=0 AND a.epoch>10000 ORDER BY a.epoch ASC",$_SESSION['idPelatihan']) as $row){;
		$a[] = array('epoch' => $row->epoch, 'mse' => $row->MSE);
	}
	header("Content-Type: text/javascript");
?>
	/* Morris.js Charts */
	// Sales chart
	var area = new Morris.Area({
	element: 'mse-chart',
	resize: true,
	data: <?php
		echo json_encode($a);
	?>,
	xkey: 'epoch',
	ykeys: ['mse'],
	labels: ['MSE'],
	lineColors: ['#a0d0e0', '#3c8dbc'],
	hideHover: 'auto'
	});
	var line = new Morris.Line({
	element: 'line-chart',
	resize: true,
	data: <?php
		echo json_encode($a);
	?>,
	xkey: 'epoch',
	ykeys: ['mse'],
	labels: ['MSE'],
	lineColors: ['#efefef'],
	lineWidth: 1,
	hideHover: 'auto',
	gridTextColor: "#fff",
	gridStrokeWidth: 0.1,
	pointSize: 1,
	pointStrokeColors: ["#efefef"],
	gridLineColor: "#efefef",
	gridTextFamily: "Open Sans",
	gridTextSize: 10
	});