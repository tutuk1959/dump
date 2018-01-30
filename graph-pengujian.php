<?php
	session_start();
	include_once 'classes/db.class.php';
	$db = new DB();
	$count = 0;
	foreach ($db->table("SELECT a.idGraphPengujian,a.asli,a.ramal FROM graphpengujian AS a WHERE a.idPengujian = '%s' ORDER BY a.idGraphPengujian ASC",$_SESSION['idPengujian']) as $k=>$row){;
		$count += 1;
		$a[] = array('index' => $count, 'hasil' => $row->asli, 'ramal' => $row->ramal);
	}
	header("Content-Type: text/javascript");
?>
	/* Morris.js Charts */
	// Sales chart
	var bar = new Morris.Bar({
	element: 'perbandingan-chart',
	resize: true,
	data: <?php
		echo json_encode($a);
	?>,
	xkey: 'index',
	ykeys: ['ramal','hasil'],
	labels: ['ramal','hasil'],
	barColors: ['#a0d0e0', '#dd4b39'],
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