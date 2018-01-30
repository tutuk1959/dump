<?php
	include_once 'classes/db.class.php';
	$db = new DB();
	$tahun = date('Y');
	$tanggalOpnamePertama = $db->cell("SELECT a.tanggalOpname 
	FROM opname AS a 
	ORDER BY a.tanggalOpname ASC LIMIT 1", $tahun);
	$tanggalOpnameTerakhir = $db->cell("SELECT a.tanggalOpname 
	FROM opname AS a 
	ORDER BY a.tanggalOpname DESC LIMIT 1", $tahun);
	
	for ($tanggal=date("Y-m-01", strtotime("$tanggalOpnamePertama +1 MONTH"));
		$tanggal<=date("Y-m-01", strtotime($tanggalOpnameTerakhir));
		$tanggal=date("Y-m-01", strtotime("$tanggal +1 MONTH"))){
		$penjualan = $db->cell("SELECT 
			(SELECT SUM(a.prevQty) FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s')
			-
			(SELECT SUM(a.qty) FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s')
			selisih", date('Y-m', strtotime("$tanggal")).'-%', date('Y-m', strtotime("$tanggal")).'-%');
		$a[] = array('y' => date('Y-m', strtotime($tanggal)), 'totalPenjualan' => $penjualan);
	}
	header("Content-Type: text/javascript");
?>
	/* Morris.js Charts */
	// Sales chart
	var area = new Morris.Area({
	element: 'penjualan-chart',
	resize: true,
	data: <?php
		echo json_encode($a);
	?>,
	xkey: 'y',
	ykeys: ['totalPenjualan'],
	labels: ['Total Penjualan'],
	lineColors: ['#a0d0e0', '#3c8dbc'],
	hideHover: 'auto'
	});
	var line = new Morris.Line({
	element: 'line-chart',
	resize: true,
	data: <?php
		echo json_encode($a);
	?>,
	xkey: 'y',
	ykeys: ['totalPenjualan'],
	labels: ['Total Penjualan'],
	lineColors: ['#efefef'],
	lineWidth: 2,
	hideHover: 'auto',
	gridTextColor: "#fff",
	gridStrokeWidth: 0.4,
	pointSize: 4,
	pointStrokeColors: ["#efefef"],
	gridLineColor: "#efefef",
	gridTextFamily: "Open Sans",
	gridTextSize: 10
	});