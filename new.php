<?php
if (isset($_REQUEST['toko']) && isset($_REQUEST['urut']) && isset ($_REQUEST['tanggalSupply'])){
	$tanggalSupply = substr($_REQUEST['tanggalSupply'], 6,10).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-%';
	if ($_REQUEST['urut'] == 'namaProduk'){
		$paginationrecords = "SELECT a.idSupply, a.tanggalSupply, a.idOutlet, b.idProduk, b.idSize, b.qty, c.kodeProduk, c.namaProduk, c.HargaAsli, d.size, d.tipeUkur, (SELECT SUM(a1.qty)
			FROM supplydetail AS a1
			LEFT JOIN supply AS b1 ON a1.idSupply = b1.idSupply
			WHERE b1.tanggalSupply LIKE '2016-12-%' AND b1.`idOutlet` = 1 AND a1.idProduk = b.idProduk ORDER BY a1.idProduk) AS jumlah
		FROM supply AS a
		LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON b.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		WHERE a.tanggalSupply LIKE '".$tanggalSupply."' AND a.idOutlet = '".$_SESSION['idOutlet']."'
		ORDER BY c.namaProduk";
	} elseif ($_REQUEST['urut'] == 'kodeProduk'){
		$paginationrecords = "SELECT a.idSupply, a.tanggalSupply, a.idOutlet, b.idProduk, b.idSize, b.qty, c.kodeProduk, c.namaProduk, c.HargaAsli, d.size, d.tipeUkur, (SELECT SUM(a1.qty)
			FROM supplydetail AS a1
			LEFT JOIN supply AS b1 ON a1.idSupply = b1.idSupply
			WHERE b1.tanggalSupply LIKE '2016-12-%' AND b1.`idOutlet` = 1 AND a1.idProduk = b.idProduk ORDER BY a1.idProduk) AS jumlah
		FROM supply AS a
		LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON b.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		WHERE a.tanggalSupply LIKE '".$tanggalSupply."' AND a.idOutlet = '".$_SESSION['idOutlet']."'
		ORDER BY c.kodeProduk";
	} elseif($_REQUEST['urut'] == 'hargaAsli'){
		$paginationrecords = "SELECT a.idSupply, a.tanggalSupply, a.idOutlet, b.idProduk, b.idSize, b.qty, c.kodeProduk, c.namaProduk, c.HargaAsli, d.size, d.tipeUkur, (SELECT SUM(a1.qty)
			FROM supplydetail AS a1
			LEFT JOIN supply AS b1 ON a1.idSupply = b1.idSupply
			WHERE b1.tanggalSupply LIKE '2016-12-%' AND b1.`idOutlet` = 1 AND a1.idProduk = b.idProduk ORDER BY a1.idProduk) AS jumlah
		FROM supply AS a
		LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON b.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		WHERE a.tanggalSupply LIKE '".$tanggalSupply."' AND a.idOutlet = '".$_SESSION['idOutlet']."'
		ORDER BY c.hargaAsli";
	} else {
		$paginationrecords = "SELECT a.idSupply, a.tanggalSupply, a.idOutlet, b.idProduk, b.idSize, b.qty, c.kodeProduk, c.namaProduk, c.HargaAsli, d.size, d.tipeUkur, (SELECT SUM(a1.qty)
			FROM supplydetail AS a1
			LEFT JOIN supply AS b1 ON a1.idSupply = b1.idSupply
			WHERE b1.tanggalSupply LIKE '".$tanggalSupply."'AND b1.`idOutlet` = '".$_SESSION['idOutlet']."' AND a1.idProduk = b.idProduk ORDER BY a1.idProduk) AS jumlah
		FROM supply AS a
		LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON b.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		WHERE a.tanggalSupply LIKE '".$tanggalSupply."' AND a.idOutlet = '".$_SESSION['idOutlet']."'
		ORDER BY b.idProduk";
	}
	
}
?> 