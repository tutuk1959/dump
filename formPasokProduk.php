<?php
session_start();
include "classes/db.class.php";
include  "classes/hak-akses.inc.php";
include "classes/template.class.php";
include "classes/class.file.php";
include "pagination.php";
include "pasok.function.php";
$db = new DB();
$file = new File();

if ($_REQUEST['mode'] == 'insert'){
	kickInsert($hakAkses[$_SESSION['hak']]);
} else if ($_REQUEST['mode'] == 'edit'){
	kickManage($hakAkses[$_SESSION['hak']]);
}

//selectBoxToko
$selectToko = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");

//selectBoxProduk
$selectProduk = $db->table("SELECT a.idProduk, a.namaProduk,a.idJenisProduk FROM produk AS a ORDER BY a.idJenisProduk,a.idProduk");

//jenisProduk
$jenisProduk = $db->table("SELECT a.idJenis, a.jenisProduk FROM jenisproduk AS a ORDER BY a.idJenis");

//sizeProduk
$sizeProduk = $db->table("SELECT a.idSize, a.size,a.tipeUkur FROM sizeproduk AS a ORDER BY a.idSize");

//insertMode
if ($_REQUEST['modes'] == 'insert' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['toko'], $_REQUEST['tanggalSupply'], $error);
	$tanggalymd = substr($_REQUEST['tanggalSupply'], 6,8).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-'.substr($_REQUEST['tanggalSupply'], 3,2);
	$tanggalymd1 = substr($_REQUEST['tanggalSupply'], 6,8).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-%';
	$satuBulanSebelumTanggalymd = date('Y-m', strtotime("$tanggalymd -1 month")).'-%';
	if (!$error){
		$cekPasokMaster = $db->row("SELECT COUNT(a.idSupply) AS jumlah, a.tanggalSupply, a.idSupply
			FROM supply AS a
			WHERE a.tanggalSupply LIKE '%s' AND a.idOutlet = '%s'",$tanggalymd, $_REQUEST['toko']);
		if ($cekPasokMaster->jumlah > 0){
			$db->exec("UPDATE supply SET tanggalSupply = '%s',idOutlet = '%s' WHERE idSupply = '%s'", $tanggalymd, $_REQUEST['toko'],$cekPasokMaster->idSupply);
		} elseif ($cekPasokMaster->jumlah == 0){
			$db->exec("INSERT INTO supply(tanggalSupply,idOutlet) VALUES ('%s','%s')", $tanggalymd, $_REQUEST['toko']);
			$supplyMasterId = $db->insertID();
		}
		
		
		foreach($_REQUEST['produk'] as $k=>$v){
			$cek = $db->row("SELECT COUNT(a.idOpname) AS jumlah, a.idOpname
				FROM opname AS a 
				WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s'",$tanggalymd1,$_REQUEST['toko']);
			if ($cek->jumlah > 0 ){
				$db->exec("UPDATE opname SET idOutlet = '%s', tanggalOpname = '%s' WHERE idOpname = '%s'",$_REQUEST['toko'],$tanggalymd,$cek->idOpname);
				if ($_REQUEST['tipeUkur'][$k] == 1){
					//size1proses-------------------------------------------------------------------------------------------------------
					$cekPernahOpname1 = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],16);
					if ($cekPernahOpname1->jumlah > 0){
						$cekStok1 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 16, $cekPernahOpname1->tanggalOpname, $_REQUEST['toko']);

						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],16,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,16);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok1->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname1->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],16,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],16,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size1'][$k] + ($cekStok1->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size1'][$k] + ($cekStok1->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size1'][$k] + ($cekStok1->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],16,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],16);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],16);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,16);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname1->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],16,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],16,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size1'][$k] + ($cekStok1->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size1'][$k] + ($cekStok1->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size1'][$k] + ($cekStok1->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],16,$cek->idOpname,$prevQty,$qty);
					}
					
					//size2proses-------------------------------------------------------------------------------------------------------
					
					$cekPernahOpname2 = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],17);
					if ($cekPernahOpname2->jumlah > 0){
						
						$cekStok2 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 17, $cekPernahOpname2->tanggalOpname, $_REQUEST['toko']);
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],17,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,17);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok2->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname2->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],17,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],17,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size2'][$k] + ($cekStok2->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size2'][$k] + ($cekStok2->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size2'][$k] + ($cekStok2->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],17,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],17);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],17);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,17);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname2->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],17,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],17,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size2'][$k] + ($cekStok2->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size2'][$k] + ($cekStok2->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size2'][$k] + ($cekStok2->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],17,$cek->idOpname,$prevQty,$qty);
					}
					
					//size3proses-------------------------------------------------------------------------------------------------------
					
					$cekPernahOpname3 = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],18);
					if ($cekPernahOpname3->jumlah > 0){
						
						$cekStok3 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 18, $cekPernahOpname3->tanggalOpname, $_REQUEST['toko']);
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],18,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,18);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok3->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname3->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],18,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],18,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size3'][$k] + ($cekStok3->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size3'][$k] + ($cekStok3->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size3'][$k] + ($cekStok3->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],18,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],18);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],18);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,18);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname3->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],18,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],18,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size3'][$k] + ($cekStok3->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size3'][$k] + ($cekStok3->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size3'][$k] + ($cekStok3->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],18,$cek->idOpname,$prevQty,$qty);
					}
					
					//size4proses-------------------------------------------------------------------------------------------------------
					
					$cekPernahOpname4 = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],19);
					if ($cekPernahOpname4->jumlah > 0){
						
						$cekStok4 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 19, $cekPernahOpname4->tanggalOpname, $_REQUEST['toko']);
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],19,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,19);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok4->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname4->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],19,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],19,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size4'][$k] + ($cekStok4->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size4'][$k] + ($cekStok4->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size4'][$k] + ($cekStok4->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],19,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],19);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],19);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,19);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname4->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],19,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],19,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size4'][$k] + ($cekStok4->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size4'][$k] + ($cekStok4->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size4'][$k] + ($cekStok4->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],19,$cek->idOpname,$prevQty,$qty);
					}
					
					//size5proses-------------------------------------------------------------------------------------------------------
					
					$cekPernahOpname5 = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],20);
					if ($cekPernahOpname5->jumlah > 0){
						
						$cekStok5 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 20, $cekPernahOpname5->tanggalOpname, $_REQUEST['toko']);
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],20,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,20);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok5->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname5->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],20,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],20,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size5'][$k] + ($cekStok5->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size5'][$k] + ($cekStok5->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size5'][$k] + ($cekStok5->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],20,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],20);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],20);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,20);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname5->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],20,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],20,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size5'][$k] + ($cekStok5->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size5'][$k] + ($cekStok5->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size5'][$k] + ($cekStok5->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],20,$cek->idOpname,$prevQty,$qty);
					}

					//size6proses-------------------------------------------------------------------------------------------------------
					
					$cekPernahOpname6 = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],21);
					if ($cekPernahOpname6->jumlah > 0){
						
						$cekStok6 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 21, $cekPernahOpname6->tanggalOpname, $_REQUEST['toko']);
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],21,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,21);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok6->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname6->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],21,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],21,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size6'][$k] + ($cekStok6->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size6'][$k] + ($cekStok6->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size6'][$k] + ($cekStok6->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],21,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],21);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],21);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,21);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname6->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],21,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],21,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size6'][$k] + ($cekStok6->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size6'][$k] + ($cekStok6->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size6'][$k] + ($cekStok6->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],21,$cek->idOpname,$prevQty,$qty);
					}
					
					//size7proses-------------------------------------------------------------------------------------------------------
					
					$cekPernahOpname7 = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],22);
					if ($cekPernahOpname7->jumlah > 0){
						
						$cekStok7 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 22, $cekPernahOpname7->tanggalOpname, $_REQUEST['toko']);
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],22,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,22);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok7->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname7->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],22,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],22,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size7'][$k] + ($cekStok7->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size7'][$k] + ($cekStok7->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size7'][$k] + ($cekStok7->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],22,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],22);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],22);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,22);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname7->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],22,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],22,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size7'][$k] + ($cekStok7->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size7'][$k] + ($cekStok7->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size7'][$k] + ($cekStok7->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],22,$cek->idOpname,$prevQty,$qty);
					}
						
				} elseif ($_REQUEST['tipeUkur'][$k] == 2){
					//size38proses-------------------------------------------------------------------------------------------------------
					
					$cekPernahOpname38 = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],23);
					if ($cekPernahOpname38->jumlah > 0){
						
						$cekStok38 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 23, $cekPernahOpname38->tanggalOpname, $_REQUEST['toko']);
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],23,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,23);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok38->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname38->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],23,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],23,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size1'][$k] + ($cekStok38->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size1'][$k] + ($cekStok38->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size1'][$k] + ($cekStok38->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],23,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],23);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],23);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,23);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname38->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],23,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],23,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size1'][$k] + ($cekStok38->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size1'][$k] + ($cekStok38->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size1'][$k] + ($cekStok38->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],22,$cek->idOpname,$prevQty,$qty);
					}
					
					//size39proses-------------------------------------------------------------------------------------------------------
					$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],24);
					if ($cekPernahOpname->jumlah > 0){
						$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 24, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],24);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,24);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok->qty > 0){
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],24,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],24,$cek->idOpname);
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
							} else {
								$prevQty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							}
							$qty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],24,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],24);
						}
					} else{
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],24);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,24);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply);
							}
						}
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],24,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],24,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],24,$cek->idOpname,$prevQty,$qty);
					}
					
					//size40proses-------------------------------------------------------------------------------------------------------
					$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],25);
					if ($cekPernahOpname->jumlah > 0){
						$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 25, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],25);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,25);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok->qty > 0){
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],25,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],25,$cek->idOpname);
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size3'][$k] +($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
							} else {
								$prevQty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							}
							$qty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],25,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],25);
						}
					} else{
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],25);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,25);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply);
							}
						}
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],25,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],25,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],25,$cek->idOpname,$prevQty,$qty);
					}
					
					//size41proses-------------------------------------------------------------------------------------------------------
					$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],26);
					if ($cekPernahOpname->jumlah > 0){
						$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k],26, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],26);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,26);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok->qty > 0){
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],26,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],26,$cek->idOpname);
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
							} else {
								$prevQty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							}
							$qty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],26,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],26);
						}
					} else{
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],26);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,26);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply);
							}
						}
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],26,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],26,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],26,$cek->idOpname,$prevQty,$qty);
					}
					
					//size42proses-------------------------------------------------------------------------------------------------------
					$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],27);
					if ($cekPernahOpname->jumlah > 0){
						$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k],27, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],27);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,27);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok->qty > 0){
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],27,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],27,$cek->idOpname);
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
							} else {
								$prevQty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							}
							$qty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],27,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],27);
						}
					} else{
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],27);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,27);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply);
							}
						}
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],12,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],27,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],27,$cek->idOpname,$prevQty,$qty);
					}
					
					//size43proses-------------------------------------------------------------------------------------------------------
					$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],28);
					if ($cekPernahOpname->jumlah > 0){
						$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k],28, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],28);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,28);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStok->qty > 0){
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],28,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],28,$cek->idOpname);
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
							} else {
								$prevQty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							}
							$qty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],28,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],28);
						}
					} else{
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],28);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,28);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply);
							}
						}
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],28,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],28,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],28,$cek->idOpname,$prevQty,$qty);
					}
						//siz44proses-------------------------------------------------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],29);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k],29, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
								FROM supplydetail AS a
								LEFT JOIN supply AS b ON a.idSupply = b.idSupply
								WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],29);
							if ($cekPasokDetail->jumlah > 0){
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,29);
							} else {
								if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$supplyMasterId);
								} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply);
								}
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],29,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],29,$cek->idOpname);
								if ($selisih->totalPenjualan > 0){
									$prevQty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								} else {
									$prevQty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								}
								$qty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],29,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],29);
							}
						} else{
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
								FROM supplydetail AS a
								LEFT JOIN supply AS b ON a.idSupply = b.idSupply
								WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],29);
							if ($cekPasokDetail->jumlah > 0){
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,29);
							} else {
								if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$supplyMasterId);
								} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply);
								}
							}
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],29,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],29,$cek->idOpname);
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
							} else {
								$prevQty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							}
							$qty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
							$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],29,$cek->idOpname,$prevQty,$qty);
						}
				}  elseif ($_REQUEST['tipeUkur'][$k] == 3){
					//sizeAllproses-------------------------------------------------------------------------------------------------------
					
					$cekPernahOpnameAll = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
					FROM opnamedetail AS a 
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],30);
					if ($cekPernahOpnameAll->jumlah > 0){
						
						$cekStokAll = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 30, $cekPernahOpnameAll->tanggalOpname, $_REQUEST['toko']);
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s' AND b.idOutlet = '%s'",$tanggalymd,$_REQUEST['produk'][$k],30,$_REQUEST['toko']);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,30);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						if ($cekStokAll->qty >= 0){
							
							$bulanOpname = date('Y-m', strtotime("$cekPernahOpnameAll->tanggalOpname")).'-%';
							
							$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
							FROM 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) x
							JOIN 
							(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
							GROUP BY a.idProduk) y
							ON x.idProduk = y.idProduk
							LEFT JOIN produk AS b ON x.idProduk = b.idProduk
							LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
							ORDER BY x.idProduk
							",$bulanOpname, $_REQUEST['produk'][$k],30,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],30,$cek->idOpname);
							
							if ($selisih->totalPenjualan > 0){
								$prevQty = $_REQUEST['size1'][$k] + ($cekStokAll->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
								
							} else {
								
								$prevQty = $_REQUEST['size1'][$k] + ($cekStokAll->qty - $cekPasokDetail->qty);
								
							}
							$qty = $_REQUEST['size1'][$k] + ($cekStokAll->qty - $cekPasokDetail->qty);
							
							$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],30,$cek->idOpname,$prevQty,$qty,$cek->idOpname,$_REQUEST['produk'][$k],30);
						}
					} else{
						
						$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],30);
						if ($cekPasokDetail->jumlah > 0){
							$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,30);
						} else {
							if ($supplyMasterId){
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$supplyMasterId);
							} else {
								$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply);
							}
						}
						
						$bulanOpname = date('Y-m', strtotime("$cekPernahOpnameAll->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],30,$cek->idOpname,$bulanOpname,$_REQUEST['produk'][$k],30,$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size1'][$k] + ($cekStokAll->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size1'][$k] + ($cekStokAll->qty - $cekPasokDetail->qty);
						}
						$qty = $_REQUEST['size1'][$k] + ($cekStokAll->qty - $cekPasokDetail->qty);
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],30,$cek->idOpname,$prevQty,$qty);
					}
				}
			
			} else {
				$db->exec("INSERT INTO opname(idOutlet,tanggalOpname) VALUES ('%s','%s')",$_REQUEST['toko'],$tanggalymd);
				$opnameMasterBaruId = $db->insertID();
				if ($_REQUEST['tipeUkur'][$k] == 1){
					//prosessize1-------------------------------------------------------------------------------------------------------
					$cekStok1 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],16, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],16);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$supplyMasterId);
					}
					
					if ($cekStok1->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok1->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],16,$cekStok1->idOpname,$bulanOpname,$_REQUEST['produk'][$k],16,$cekStok1->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size1'][$k] + $cekStok1->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						}
						$qty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],16,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],16,$opnameMasterBaruId,$qty,$qty);
					} 
					
					//prosessize2-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],17, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],17);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],17,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],17,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size2'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size2'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size2'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],17,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size2'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],17,$opnameMasterBaruId,$qty,$qty);
					} 
					
					//prosessize3-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],18, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],18);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],18,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],18,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size3'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size3'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size3'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],18,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size3'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],18,$opnameMasterBaruId,$qty,$qty);
					} 
					
					//prosessize4-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],19, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],19);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],19,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],19,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size4'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size4'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size4'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],19,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size4'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],19,$opnameMasterBaruId,$qty,$qty);
					} 
					
					//prosessize5-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],20, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],20);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],20,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],20,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size5'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size5'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size5'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],20,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size5'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],20,$opnameMasterBaruId,$qty,$qty);
					}
					
					//prosessize6-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],21, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],21);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],21,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],21,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size6'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size6'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size6'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],21,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size6'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],21,$opnameMasterBaruId,$qty,$qty);
					}
					
					//prosessize7-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],22, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],22);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],22,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],22,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size7'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size7'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size7'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],22,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size7'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],22,$opnameMasterBaruId,$qty,$qty);
					}
				} elseif($_REQUEST['tipeUkur'][$k] == 2){
					//prosessize38-------------------------------------------------------------------------------------------------------
					$cekStok1 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],23, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],23);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$supplyMasterId);
					}
					
					if ($cekStok1->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok1->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],23,$cekStok1->idOpname,$bulanOpname,$_REQUEST['produk'][$k],23,$cekStok1->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size1'][$k] + $cekStok1->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						}
						$qty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],23,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],23,$opnameMasterBaruId,$qty,$qty);
					}
					
					//prosessize39-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],24, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],24);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],24,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],24,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size2'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size2'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size2'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],24,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size2'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],24,$opnameMasterBaruId,$qty,$qty);
					}
					
					//prosessize40-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],25, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],25);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],25,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],25,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size3'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size3'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size3'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],25,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size3'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],25,$opnameMasterBaruId,$qty,$qty);
					}
					
					//prosessize41-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],26, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],26);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],26,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],26,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size4'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size4'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size4'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],26,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size4'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],26,$opnameMasterBaruId,$qty,$qty);
					}
					
					//prosessize42-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],27, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],27);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],27,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],27,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size5'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size5'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size5'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],27,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size5'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],27,$opnameMasterBaruId,$qty,$qty);
					}
					
					//prosessize43-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],28, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],28);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],28,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],28,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size6'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size6'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size6'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],28,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size6'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],28,$opnameMasterBaruId,$qty,$qty);
					}
					
					//prosessize44-------------------------------------------------------------------------------------------------------
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],29, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],29);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$supplyMasterId);
					}
					
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],29,$cekStok->idOpname,$bulanOpname,$_REQUEST['produk'][$k],29,$cekStok->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size7'][$k] + $cekStok->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size7'][$k] + $cekStok->qty;
						}
						$qty = $_REQUEST['size7'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],29,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size7'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],29,$opnameMasterBaruId,$qty,$qty);
					}
				} elseif($_REQUEST['tipeUkur'][$k] == 3){
					//prosessizeAll-------------------------------------------------------------------------------------------------------
					$cekStok1 = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['produk'][$k],30, $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$_REQUEST['produk'][$k],30);
						
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$supplyMasterId);
					}
					
					if ($cekStok1->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cekStok1->tanggalOpname")).'-%';
						$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY x.idProduk
						",$bulanOpname, $_REQUEST['produk'][$k],30,$cekStok1->idOpname,$bulanOpname,$_REQUEST['produk'][$k],30,$cekStok1->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $_REQUEST['size1'][$k] + $cekStok1->qty + $selisih->totalPenjualan;
						} else {
							$prevQty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						}
						$qty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],30,$opnameMasterBaruId,$prevQty,$qty);
					} else {
						$qty = $_REQUEST['size1'][$k] + $cekStok1->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['produk'][$k],30,$opnameMasterBaruId,$qty,$qty);
					}
				}
			}
			$message = 'Data Pasok Berhasil Ditambah..';
			$prefillSupply = $db->row("SELECT a.idOutlet, a.tanggalSupply FROM supply AS a WHERE a.idSupply = '%s'",$supplyMasterId);
			foreach ($db->table("SELECT a.idSupply,b.idSupplyDetail, a.tanggalSupply, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,e.idJenis
			FROM supply AS a
			LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
			LEFT JOIN produk AS c ON c.idProduk = b.idProduk
			LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
			LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
			WHERE a.idSupply = '%s' AND a.tanggalSupply LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ",$supplyMasterId,$prefillSupply->tanggalSupply,$prefillSupply->idOutlet) as $row){
				$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idSupplyDetail'] = $row->idSupplyDetail;
				$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
				$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
			}
		}
	}
}
	
	//prefillEdit
	if ($_REQUEST['mode'] == 'edit' && isset($_REQUEST['idPasok']) && !isset($_REQUEST['submit'])){
		$tanggalOpname = $_REQUEST['tanggalSupply'];
		$prefillSupply = $db->row("SELECT COUNT(a.idSupply) AS jumlahSupply,a.idSupply, a.idOutlet, a.tanggalSupply FROM supply AS a WHERE a.idSupply = '%s' AND a.tanggalSupply = '%s'",$_REQUEST['idPasok'],$tanggalOpname);
		if ($prefillSupply->jumlahSupply > 0){
			foreach ($db->table("SELECT a.idSupply,b.idSupplyDetail, a.tanggalSupply, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,e.idJenis
			FROM supply AS a
			LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
			LEFT JOIN produk AS c ON c.idProduk = b.idProduk
			LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
			LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
			WHERE a.idSupply = '%s' AND a.tanggalSupply LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk,c.idJenisProduk ",$_REQUEST['idPasok'],$_REQUEST['tanggalSupply'],$_REQUEST['idOutlet']) as $row){
				$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idSupplyDetail'] = $row->idSupplyDetail;
				$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['idJenis'] = $row->idJenis;
				$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
				$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
			}
		} else {
			$error[] = 'Tidak ada data pasok yang ditemukan!';
		}
}
	//editMode------------------------------------------------------------------------------------------------------------------------
	if ($_REQUEST['modes'] == 'edit' && $_REQUEST['submit'] == 'Simpan'){
		_validation($_REQUEST['toko'], $_REQUEST['tanggalSupply'], $error);
		$tanggalymd = substr($_REQUEST['tanggalSupply'], 6,8).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-'.substr($_REQUEST['tanggalSupply'], 3,2);
		$tanggalymd1 = substr($_REQUEST['tanggalSupply'], 6,8).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-%';
		$satuBulanSebelumTanggalymd = date('Y-m', strtotime("$tanggalymd -1 month")).'-%';
		if (!$error){
			$cekPasokMaster = $db->row("SELECT COUNT(a.idSupply) AS jumlah, a.tanggalSupply, a.idSupply
			FROM supply AS a
			WHERE a.idSupply =  '%s'",$_REQUEST['idPasok']);
			if ($cekPasokMaster->jumlah > 0){
				$db->exec("UPDATE supply SET tanggalSupply = '%s',idOutlet = '%s' WHERE idSupply = '%s'", $tanggalymd, $_REQUEST['toko'],$cekPasokMaster->idSupply);
			} else {
				$error[] = "Data master supply tidak ditemukan";
			}
			foreach($_REQUEST['produk']as $k=>$v){
				$cek = $db->row("SELECT COUNT(a.idOpname) AS jumlah, a.idOpname
				FROM opname AS a 
				WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s'",$tanggalymd1,$_REQUEST['toko']);
				if ($cek->jumlah > 0 ){
					$db->exec("UPDATE opname SET idOutlet = '%s', tanggalOpname = '%s' WHERE idOpname = '%s'",$_REQUEST['toko'],$tanggalymd,$cek->idOpname);
					if ($_REQUEST['tipeUkur'][$k] == 1){
						//Size XXS--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],16);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 16, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],16);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size1'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size1'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size1'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,16,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok1->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname1->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],16,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],16,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size1'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size1'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],16,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],16);
							}
						} else {
							$error[] = 'Data opname untuk size XXS tersebut tidak ditemukan!';
						}
						
						//Size XS--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],17);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 17, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],17);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size2'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size2'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size2'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,17,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok2->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname2->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],17,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],17,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size2'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size2'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size2'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size2'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size2'][$k];
								}
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],17,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],17);
							}
						} else {
							$error[] = 'Data opname untuk size XS tersebut tidak ditemukan!';
						}
						
						//Size S--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],18);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 18, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],18);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size3'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size3'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size3'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,18,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok3->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname3->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],18,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],18,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size3'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size3'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size3'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size3'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size3'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],18,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],18);
							}
						} else {
							$error[] = 'Data opname untuk size S tersebut tidak ditemukan!';
						}
						
						//Size M--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],19);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 19, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],19);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size4'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size4'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size4'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,19,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname4->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],19,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],19,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size4'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size4'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size4'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size4'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size4'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],19,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],19);
							}
						} else {
							$error[] = 'Data opname untuk size M tersebut tidak ditemukan!';
						}
						
						//Size L--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],20);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 20, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],20);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size5'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size5'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size5'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,20,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],20,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],20,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size5'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size5'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size5'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size5'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size5'][$k];
								}
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],20,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],20);
							}
						} else {
							$error[] = 'Data opname untuk size L tersebut tidak ditemukan!';
						}
						
						//Size XL--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],21);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 21, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],21);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size6'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size6'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size6'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,21,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],21,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],21,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size6'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size6'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size6'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size6'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size6'][$k];
								}
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],21,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],21);
							}
						} else {
							$error[] = 'Data opname untuk size XL tersebut tidak ditemukan!';
						}
						
						//Size XXL--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],22);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 22, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],22);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size7'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size7'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size7'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,22,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],22,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],22,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size7'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size7'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size7'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size7'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size7'][$k];
								}
							
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],22,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],22);
							}
						} else {
							$error[] = 'Data opname untuk size XXL tersebut tidak ditemukan!';
						}
					} elseif ($_REQUEST['tipeUkur'][$k] == 2){
						//Size 38--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],23);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 23, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],23);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size1'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size1'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size1'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,23,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],23,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],23,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size1'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size1'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],23,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],23);
							}
						} else {
							$error[] = 'Data opname untuk size 39 tersebut tidak ditemukan!';
						}
						
						//Size 39--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],24);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 24, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],24);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size2'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size2'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size2'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,24,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],24,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],24,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size2'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size2'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size2'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size2'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size2'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size2'][$k];
								}
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],24,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],24);
							}
						} else {
							$error[] = 'Data opname untuk size 39 tersebut tidak ditemukan!';
						}
					
						//Size 40--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],25);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 25, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],25);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size3'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size3'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size3'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
							
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,25,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],25,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],25,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size3'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size3'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size3'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size3'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size3'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size3'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],25,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],25);
							}
						} else {
							$error[] = 'Data opname untuk size 40 tersebut tidak ditemukan!';
						}
						
						//Size 41--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],26);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 26, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],26);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size4'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size4'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								echo $t.'=$t';
								if ($_REQUEST['size4'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
								echo $s.'=$s';
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,26,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],26,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],26,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size4'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size4'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size4'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size4'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size4'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size4'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],26,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],26);
							}
						} else {
							$error[] = 'Data opname untuk size 41 tersebut tidak ditemukan!';
						}
						
						//Size 42--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],27);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 27, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],27);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size1'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size5'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								echo $t.'=$t';
								if ($_REQUEST['size5'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
								echo $s.'=$s';
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,27,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],27,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],27,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size5'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size5'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size5'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size5'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size5'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size5'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],27,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],27);
							}
						} else {
							$error[] = 'Data opname untuk size 42 tersebut tidak ditemukan!';
						}
						
						//Size 43--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],28);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 28, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],28);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size6'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size6'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								echo $t.'=$t';
								if ($_REQUEST['size6'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
								echo $s.'=$s';
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,28,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],28,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],28,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size6'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size6'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size6'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size6'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size6'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size6'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],28,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],28);
							}
						} else {
							$error[] = 'Data opname untuk size 43 tersebut tidak ditemukan!';
						}
						
						//Size 44--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],29);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 29, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],29);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size7'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size7'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								echo $t.'=$t';
								if ($_REQUEST['size7'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
								echo $s.'=$s';
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,29,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],29,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],29,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size7'][$k] + $selisih->totalPenjualan;
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size7'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size7'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size7'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size7'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size7'][$k];
								}
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],29,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],29);
							}
						} else {
							$error[] = 'Data opname untuk size 44 tersebut tidak ditemukan!';
						}
					} elseif ($_REQUEST['tipeUkur'][$k] == 3){
						//Size All--------------------------------------------------------------
						$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
						FROM opnamedetail AS a 
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['toko'],$_REQUEST['produk'][$k],30);
						if ($cekPernahOpname->jumlah > 0){
							$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $_REQUEST['produk'][$k], 30, $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
						
							$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize,a.qty
							FROM supplydetail AS a
							LEFT JOIN supply AS b ON a.idSupply = b.idSupply
							WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],30);
							if ($cekPasokDetail->jumlah > 0){
								if ($_REQUEST['size1'][$k] < $cekPasokDetail->qty){
									$t = true;
								} elseif ($_REQUEST['size1'][$k] > $cekPasokDetail->qty){
									$t = false;
								} else {
									$t = false;
								}
								
								if ($_REQUEST['size1'][$k] == $cekPasokDetail->qty){
									$s = true;
								} else {
									$s = false;
								}
								
								$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s' AND idSize = '%s' AND idProduk = '%s'", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply,30,$_REQUEST['produk'][$k]);
							} else {
								$error[] = "Tidak bisa merubah data detail pasok..";
							}
							
							if ($cekStok->qty > 0){
								$bulanOpname = date('Y-m', strtotime("$cekPernahOpname->tanggalOpname")).'-%';
								$selisih =  $db->row("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
								FROM 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE'%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) x
								JOIN 
								(SELECT a.idOpname,a.idProduk,a.idSize, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idOpname = '%s'
								GROUP BY a.idProduk) y
								ON x.idProduk = y.idProduk
								LEFT JOIN produk AS b ON x.idProduk = b.idProduk
								LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
								ORDER BY x.idProduk
								",$bulanOpname, $_REQUEST['produk'][$k],30,$cekPernahOpname->idOpname,$bulanOpname,$_REQUEST['produk'][$k],30,$cekPernahOpname->idOpname);
								
								if ($selisih->totalPenjualan > 0){
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k] + $selisih->totalPenjualan;
										
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty) + $selisih->totalPenjualan;
										
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
										
									} 
								
								} else {
									if ($t == true && $s == false){
										$prevQty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k];
									} elseif ($t == false && $s == false) {
										$prevQty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
									} elseif ($t == false && $s == true){
										$prevQty = $cekStok->qty;
									} 
									
								}
								
								if ($t == true && $s == false){
									$qty = ($cekStok->qty - $cekPasokDetail->qty) + $_REQUEST['size1'][$k];
								} elseif ($t == false && $s == false) {
									$qty = $_REQUEST['size1'][$k] + ($cekStok->qty - $cekPasokDetail->qty);
								} elseif ($t == false && $s == false){
									$qty = $_REQUEST['size1'][$k];
								} elseif ($t == false && $s == true){
									$qty = $_REQUEST['size1'][$k];
								}
								
								$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$_REQUEST['produk'][$k],30,$cekPernahOpname->idOpname,$prevQty,$qty,$cekPernahOpname->idOpname,$_REQUEST['produk'][$k],30);
							}
						} else {
							$error[] = 'Data opname untuk All Size tersebut tidak ditemukan!';
						}
					}
					$message = 'Data Pasok Berhasil Diubah..';
					$prefillSupply = $db->row("SELECT a.idOutlet, a.tanggalSupply FROM supply AS a WHERE a.idSupply = '%s'",$_REQUEST['idPasok']);
					foreach ($db->table("SELECT a.idSupply,b.idSupplyDetail, a.tanggalSupply, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,e.idJenis
					FROM supply AS a
					LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
					LEFT JOIN produk AS c ON c.idProduk = b.idProduk
					LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
					LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
					WHERE a.idSupply = '%s' AND a.tanggalSupply LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ",$_REQUEST['idPasok'],$prefillSupply->tanggalSupply,$prefillSupply->idOutlet) as $row){
						$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
						$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idSupplyDetail'] = $row->idSupplyDetail;
						$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
						$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
						$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
						$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
						$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] = $row->jumlahproduk;
						$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
					}
				} else {
					$error[] = 'Data pasok tidak dapat dirubah !';
				}
			}
		}
	}
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<?php ($_REQUEST['mode'] == 'insert')? $msg = 'Tambah Pasok Baru' :$msg='Ubah Data Pasok';?>
			<h1 style="float: left;margin-right: 10px !important;">
				<?=$msg;?>
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formPasokProduk.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Pasok</button>
			</a>
			<a style="float: right;margin-bottom: 10px !important;" href="laporan-invoice-pasok.php?mode=report&tanggalSupply=<?=$_REQUEST['tanggalSupply'];?>&idOutlet=<?=$_REQUEST['idOutlet'];?>">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan Detail Pasok</button>
			</a>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-md-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Form Pasok</h3>
						</div>
						<form method="post" class="form-horizontal">
							<div class="box-body">
								<div class="row" style="margin-bottom:10px !important;">
									<div class="col-sm-6">
										<input type="hidden" name="modes" value="<?= ($_REQUEST['mode'] == 'insert') ? 'insert' : 'edit'?>"/>
										<select name="toko" class="form-control">
											<?php foreach($selectToko as $k=>$v) :?>
												<option value="<?=$v->idOutlet;?>" <?=($prefillSupply->idOutlet == $v->idOutlet || $_REQUEST['idOutlet'] == $v->idOutlet) ? 'selected' : '';?> ><?=$v->namaOutlet;?></option>
											<?php endforeach;?>
										</select>
									</div>
									<div class="col-sm-6">
										<div class="input-group date">
										<div class="input-group-addon">
											<i class="fa fa-calendar"></i>
										</div>
										<?php
											if ($prefillSupply->tanggalSupply){
												$tanggaldmy = substr($prefillSupply->tanggalSupply,5,2).'/'.substr($prefillSupply->tanggalSupply,8,2).'/'.substr($prefillSupply->tanggalSupply,0,4);
											} elseif ($_REQUEST['tanggalSupply']){
												$tanggaldmy = $_REQUEST['tanggalSupply'];
											}
										?>
										<input name="tanggalSupply" type="text" class="form-control pull-right" value="<?=($tanggaldmy) ? $tanggaldmy : $_REQUEST['tanggalSupply'];?>" id="datepicker">
										<?php if (isset($_REQUEST['idPasok']) && $_REQUEST['mode'] == 'edit'):?>
											<input type="hidden" name="idPasok" value = "<?=$_REQUEST['idPasok'];?>" />
										<?php endif;?>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
									<?php if ($_REQUEST['mode'] == 'insert' || $_REQUEST['modes'] == 'insert'):?>
										<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
											<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
												<thead>
													<tr role="row">
														<th style="vertical-align:middle !important;" class="sorting" rowspan="3">Produk</th>
														<th style="vertical-align:middle !important;" rowspan="3">Jenis</th>
														<th>1</th>
														<th>XSS</th>
														<th>XS</th>
														<th>S</th>
														<th>M</th>
														<th>L</th>
														<th>XL</th>
														<th>XXL</th>
														<th rowspan="3" style="text-align:center;">
															<?php if ($_REQUEST['mode'] == 'insert'):?>
																<button type="button" data-id="addRow" class="btn btn-default">+</button>
															<?php endif;?>
														</th>
													</tr>
													<tr role="row">
														<!--td rowspan="3"--><!--td rowspan="3"-->
														<th>2</th>
														<th>38</th>
														<th>39</th>
														<th>40</th>
														<th>41</th>
														<th>42</th>
														<th>43</th>
														<th>44</th>
														
													</tr>
													<tr role="row">
														<!--td rowspan="3"--><!--td rowspan="3"-->
														<th>3</th>
														<th>All</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														
													</tr>
												</thead>
											
											<tbody>
												<?php if ($prefillSupply->idOutlet) :?>
													<?php foreach($data as $k=>$v):?>
														<?php foreach($v as $kk=>$row):?>
															<?php if ($row['tipeUkur']==1) :?>
																<tr role="row">
																	<td style="width:20%;" class="sorting_1">
																		<div>
																			<select name="produk['<?=$row['idProduk'];?>']" class="form-control">
																				<?php foreach($selectProduk as $x=>$y) :?>
																					<option value="<?=$y->idProduk;?>" <?=($row['idProduk'] == $y->idProduk) ? 'selected' : '';?>><?=$y->namaProduk;?></option>
																				<?php endforeach;?>
																			</select>
																		</div>
																	</td>
																	<td class="sorting_1">
																		<div >
																			<select name="jenisProduk['<?=$row['idProduk'];?>']"class="form-control">
																				<?php foreach ($jenisProduk as $x=>$y):?>
																					<option value="<?=$y->idJenis; ?>" <?=($row['idJenis'] == $y->idJenis) ? 'selected' : '';?>><?=$y->jenisProduk; ?></option>
																				<?php endforeach ;?>
																			</select>
																		</div>
																	</td>
																	<td class="sorting_1">
																		<div >
																			<select name="tipeUkur['<?=$row['idProduk'];?>']"class="form-control">
																				<option value="1" <?=($row['tipeUkur'] == 1) ? 'selected' : '';?>>1</option>
																				<option value="2"  <?=($row['tipeUkur'] == 2) ? 'selected' :' ';?>>2</option>
																				<option value="3" <?=($row['tipeUkur'] == 3) ? 'selected' :' ';?>>3</option>
																			</select>
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size1['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXS']['XXS'] <= 0)? '0' : $row['XXS']['XXS'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size2['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XS']['XS'] <= 0)? '0' : $row['XS']['XS'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size3['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['S']['S'] <= 0)? '0' : $row['S']['S'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size4['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['M']['M'] <= 0)? '0' : $row['M']['M'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size5['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['L']['L'] <= 0)? '0' : $row['L']['L'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size6['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XL']['XL'] <= 0)? '0' : $row['XL']['XL'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size7['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXL']['XXL'] <= 0)? '0' : $row['XXL']['XXL'];?>" >
																		</div>
																	</td>
																	<td class="sorting_1">
																		
																	</td>
																</tr>
															<?php elseif($row['tipeUkur'] == 2): ?>
																<tr role="row">
																	<td class="sorting_1">
																		<div>
																			<select name="produk['<?=$row['idProduk'];?>']" class="form-control">
																				<?php foreach($selectProduk as $x=>$y) :?>
																					<option value="<?=$y->idProduk;?>" <?=($row['idProduk'] == $y->idProduk) ? 'selected' : '';?>><?=$y->namaProduk;?></option>
																				<?php endforeach;?>
																			</select>
																		</div>
																	</td>
																	<td class="sorting_1">
																		<div >
																			<select name="jenisProduk['<?=$row['idProduk'];?>']"class="form-control">
																				<?php foreach ($jenisProduk as $x=>$y):?>
																					<option value="<?=$y->idJenis; ?>" <?=($row['idJenis'] == $y->idJenis) ? 'selected' : '';?>><?=$y->jenisProduk; ?></option>
																				<?php endforeach ;?>
																			</select>
																		</div>
																	</td>
																	<td class="sorting_1">
																		<div >
																			<select name="tipeUkur['<?=$row['idProduk'];?>']"class="form-control">
																				<option value="1" <?=($row['tipeUkur'] == 1) ? 'selected' : '';?>>1</option>
																				<option value="2"  <?=($row['tipeUkur'] == 2) ? 'selected' :' ';?>>2</option>
																				<option value="3" <?=($row['tipeUkur'] == 3) ? 'selected' :' ';?>>3</option>
																			</select>
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size1['<?=$row->idProduk?>']" type="text" class="form-control" value="<?= ($row['38']['38'] <= 0)? '0' : $row['38']['38'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size2['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['39']['39'] <= 0)? '0' : $row['39']['39'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size3['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['40']['40'] <= 0)? '0' : $row['40']['40'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size4['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['41']['41'] <= 0)? '0' : $row['41']['41'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size5['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['42']['42'] <= 0)? '0' : $row['42']['42'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size6['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['43']['43'] <= 0)? '0' : $row['43']['43'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size7['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['44']['44'] <= 0)? '0' : $row['44']['44'];?>" >
																		</div>
																	</td>
																	<td class="sorting_1">
																		
																	</td>
																</tr>
															<?php elseif($row['tipeUkur'] == 3): ?>
																<tr role="row">
																	<td style="width:20%;" class="sorting_1">
																		<div>
																			<select name="produk['<?=$row['idProduk'];?>']" class="form-control">
																				<?php foreach($selectProduk as $x=>$y) :?>
																					<option value="<?=$y->idProduk;?>" <?=($row['idProduk'] == $y->idProduk) ? 'selected' : '';?>><?=$y->namaProduk;?></option>
																				<?php endforeach;?>
																			</select>
																		</div>
																	</td>
																	<td class="sorting_1">
																		<div >
																			<select name="jenisProduk['<?=$row['idProduk'];?>']"class="form-control">
																				<?php foreach ($jenisProduk as $x=>$y):?>
																					<option value="<?=$y->idJenis; ?>" <?=($row['idJenis'] == $y->idJenis) ? 'selected' : '';?>><?=$y->jenisProduk; ?></option>
																				<?php endforeach ;?>
																			</select>
																		</div>
																	</td>
																	<td class="sorting_1">
																		<div >
																			<select name="tipeUkur['<?=$row['idProduk'];?>']"class="form-control">
																				<option value="1" <?=($row['tipeUkur'] == 1) ? 'selected' : '';?>>1</option>
																				<option value="2"  <?=($row['tipeUkur'] == 2) ? 'selected' :' ';?>>2</option>
																				<option value="3" <?=($row['tipeUkur'] == 3) ? 'selected' :' ';?>>3</option>
																			</select>
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size1['<?=$row->idProduk?>']" type="text" class="form-control" value="<?= ($row['All']['All'] <= 0)? '0' : $row['All']['All'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size2['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size3['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size4['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['41']['41'] <= 0)? '0' : $row['41']['41'];?>" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size5['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size6['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																		</div>
																	</td>
																	<td>
																		<div >
																			<input style="width:40px;" name="size7['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																		</div>
																	</td>
																	<td class="sorting_1">
																		
																	</td>
																</tr>
															<?php endif;?>
														<?php endforeach;?>
													<?php endforeach;?>
												<?php else :?>
													<tr role="row" id="temp-fdasfas">
														<td style="width:20%;" class="sorting_1">
															<div>
																<select  name="produk[]" class="form-control">
																	<?php foreach($selectProduk as $x=>$y) :?>
																		<option value="<?=$y->idProduk;?>"><?=$y->namaProduk;?></option>
																	<?php endforeach;?>
																</select>
															</div>
														</td>
														<td class="sorting_1">
															<div >
																<select name="jenisProduk[]"class="form-control">
																	<?php foreach ($jenisProduk as $x=>$y):?>
																		<option value="<?=$y->idJenis; ?>"><?=$y->jenisProduk; ?></option>
																	<?php endforeach ;?>
																</select>
															</div>
														</td>
														<td class="sorting_1">
															<div >
																<select name="tipeUkur[]"class="form-control">
																	<option value="1">1</option>
																	<option value="2">2</option>
																	<option value="3">3</option>
																</select>
															</div>
														</td>
														<td>
															<div >
																<input style="width:60px;" name="size1[]" type="text" class="form-control">
															</div>
														</td>
														<td>
															<div >
																<input style="width:60px;" name="size2[]" type="text" class="form-control">
															</div>
														</td>
														<td>
															<div >
																<input style="width:60px;" name="size3[]" type="text" class="form-control">
															</div>
														</td>
														<td>
															<div >
																<input style="width:60px;" name="size4[]" type="text" class="form-control" >
															</div>
														</td>
														<td>
															<div >
																<input style="width:60px;" name="size5[]" type="text" class="form-control"  >
															</div>
														</td>
														<td>
															<div >
																<input style="width:60px;" name="size6[]" type="text" class="form-control">
															</div>
														</td>
														<td>
															<div >
																<input style="width:60px;" name="size7[]" type="text" class="form-control">
															</div>
														</td>
														<td class="sorting_1">
															<button type="button" data-id="addRow" class="btn btn-default">+</button>
															<button type="button" data-id="remRow" class="btn btn-default">-</button>
														</td>
													</tr>
													<?php endif;?>
											</tbody>
											<script>
												$(function(){
													var temp = $('#temp-fdasfas'); // template
													var tbody = temp.parent('tbody');
													var table = tbody.parent('table');
													temp.detach();
													table.on('click', '[data-id="addRow"]', function(){
														var tr = $(this).parents('tr').first(); // tr of addRow
														if (tr.parent().is('tbody')){ // addRow on tbody
															temp.clone().insertAfter(tr);
														} else { // addRow on thead
															temp.clone().prependTo(tbody);
														}
													}).on('click', '[data-id="remRow"]', function(){
														var tr = $(this).parents('tr').first(); // tr of addRow
														tr.remove();
													});
												});
											</script>
										</table>
									</div>
									<?php elseif ($_REQUEST['mode'] == 'edit' || $_REQUEST['modes'] == 'edit'):?>
										<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
											<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
												<thead>
													<tr role="row">
														<th style="vertical-align:middle !important;width:20%;" class="sorting" rowspan="3">Produk</th>
														<th style="vertical-align:middle !important;" rowspan="3">Jenis</th>
														<th>1</th>
														<th>XSS</th>
														<th>XS</th>
														<th>S</th>
														<th>M</th>
														<th>L</th>
														<th>XL</th>
														<th>XXL</th>
														<th rowspan="3">
															
														</th>
													</tr>
													<tr role="row">
														<!--td rowspan="3"--><!--td rowspan="3"-->
														<th>2</th>
														<th>38</th>
														<th>39</th>
														<th>40</th>
														<th>41</th>
														<th>42</th>
														<th>43</th>
														<th>44</th>
														
													</tr>
													<tr role="row">
														<!--td rowspan="3"--><!--td rowspan="3"-->
														<th>3</th>
														<th>All</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														<th>&nbsp;</th>
														
													</tr>
												</thead>
											
												<tbody>
													<?php if ($prefillSupply->idOutlet) :?>
														<?php foreach($data as $k=>$v):?>
															<?php foreach($v as $kk=>$row):?>
																<?php if ($row['tipeUkur']==1) :?>
																	<tr role="row">
																		<td style="width:20%;" class="sorting_1">
																			<div>
																				<select  name="produk['<?=$row['idProduk'];?>']" class="form-control">
																					<?php foreach($selectProduk as $x=>$y) :?>
																						<option value="<?=$y->idProduk;?>" <?=($row['idProduk'] == $y->idProduk) ? 'selected' : '';?>><?=$y->namaProduk;?></option>
																					<?php endforeach;?>
																				</select>
																			</div>
																		</td>
																		<td class="sorting_1">
																			<div >
																				<select name="jenisProduk['<?=$row['idProduk'];?>']"class="form-control">
																					<?php foreach ($jenisProduk as $x=>$y):?>
																						<option value="<?=$y->idJenis; ?>" <?=($row['idJenis'] == $y->idJenis) ? 'selected' : '';?>><?=$y->jenisProduk; ?></option>
																					<?php endforeach ;?>
																				</select>
																			</div>
																		</td>
																		<td class="sorting_1">
																			<div >
																				<select name="tipeUkur['<?=$row['idProduk'];?>']"class="form-control">
																					<option value="1" <?=($row['tipeUkur'] == 1) ? 'selected' : '';?>>1</option>
																					<option value="2"  <?=($row['tipeUkur'] == 2) ? 'selected' :' ';?>>2</option>
																					<option value="3" <?=($row['tipeUkur'] == 3) ? 'selected' :' ';?>>3</option>
																				</select>
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size1['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXS']['XXS'] <= 0)? '0' : $row['XXS']['XXS'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size2['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XS']['XS'] <= 0)? '0' : $row['XS']['XS'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size3['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['S']['S'] <= 0)? '0' : $row['S']['S'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size4['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['M']['M'] <= 0)? '0' : $row['M']['M'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size5['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['L']['L'] <= 0)? '0' : $row['L']['L'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size6['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XL']['XL'] <= 0)? '0' : $row['XL']['XL'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size7['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXL']['XXL'] <= 0)? '0' : $row['XXL']['XXL'];?>" >
																			</div>
																		</td>
																		<td class="sorting_1">
																			
																		</td>
																	</tr>
																<?php elseif($row['tipeUkur'] == 2): ?>
																	<tr role="row">
																		<td style="width:20%;"  class="sorting_1">
																			<div>
																				<select name="produk['<?=$row['idProduk'];?>']" class="form-control">
																					<?php foreach($selectProduk as $x=>$y) :?>
																						<option value="<?=$y->idProduk;?>" <?=($row['idProduk'] == $y->idProduk) ? 'selected' : '';?>><?=$y->namaProduk;?></option>
																					<?php endforeach;?>
																				</select>
																			</div>
																		</td>
																		<td class="sorting_1">
																			<div >
																				<select name="jenisProduk['<?=$row['idProduk'];?>']"class="form-control">
																					<?php foreach ($jenisProduk as $x=>$y):?>
																						<option value="<?=$y->idJenis; ?>" <?=($row['idJenis'] == $y->idJenis) ? 'selected' : '';?>><?=$y->jenisProduk; ?></option>
																					<?php endforeach ;?>
																				</select>
																			</div>
																		</td>
																		<td class="sorting_1">
																			<div >
																				<select name="tipeUkur['<?=$row['idProduk'];?>']"class="form-control">
																					<option value="1" <?=($row['tipeUkur'] == 1) ? 'selected' : '';?>>1</option>
																					<option value="2"  <?=($row['tipeUkur'] == 2) ? 'selected' :' ';?>>2</option>
																					<option value="3" <?=($row['tipeUkur'] == 3) ? 'selected' :' ';?>>3</option>
																				</select>
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size1['<?=$row->idProduk?>']" type="text" class="form-control" value="<?= ($row['38']['38'] <= 0)? '0' : $row['38']['38'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size2['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['39']['39'] <= 0)? '0' : $row['39']['39'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size3['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['40']['40'] <= 0)? '0' : $row['40']['40'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size4['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['41']['41'] <= 0)? '0' : $row['41']['41'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size5['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['42']['42'] <= 0)? '0' : $row['42']['42'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size6['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['43']['43'] <= 0)? '0' : $row['43']['43'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size7['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['44']['44'] <= 0)? '0' : $row['44']['44'];?>" >
																			</div>
																		</td>
																		<td class="sorting_1">
																			
																		</td>
																	</tr>
																<?php elseif($row['tipeUkur'] == 3): ?>
																	<tr role="row">
																		<td style="width:20%;" class="sorting_1">
																			<div>
																				<select name="produk['<?=$row['idProduk'];?>']" class="form-control">
																					<?php foreach($selectProduk as $x=>$y) :?>
																						<option value="<?=$y->idProduk;?>" <?=($row['idProduk'] == $y->idProduk) ? 'selected' : '';?>><?=$y->namaProduk;?></option>
																					<?php endforeach;?>
																				</select>
																			</div>
																		</td>
																		<td class="sorting_1">
																			<div >
																				<select name="jenisProduk['<?=$row['idProduk'];?>']"class="form-control">
																					<?php foreach ($jenisProduk as $x=>$y):?>
																						<option value="<?=$y->idJenis; ?>" <?=($row['idJenis'] == $y->idJenis) ? 'selected' : '';?>><?=$y->jenisProduk; ?></option>
																					<?php endforeach ;?>
																				</select>
																			</div>
																		</td>
																		<td class="sorting_1">
																			<div >
																				<select name="tipeUkur['<?=$row['idProduk'];?>']"class="form-control">
																					<option value="1" <?=($row['tipeUkur'] == 1) ? 'selected' : '';?>>1</option>
																					<option value="2"  <?=($row['tipeUkur'] == 2) ? 'selected' :' ';?>>2</option>
																					<option value="3" <?=($row['tipeUkur'] == 3) ? 'selected' :' ';?>>3</option>
																				</select>
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size1['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['All']['All'] <= 0)? '0' : $row['All']['All'];?>" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size2['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size3['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size4['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size5['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size6['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																			</div>
																		</td>
																		<td>
																			<div >
																				<input style="width:40px;" name="size7['<?=$row['idProduk'];?>']" type="text" class="form-control" value="" >
																			</div>
																		</td>
																		<td class="sorting_1">
																			
																		</td>
																	</tr>
																<?php endif;?>
															<?php endforeach;?>
														<?php endforeach;?>
													<?php else :?>
														<tr role="row" id="temp-fdasfas">
															<td class="sorting_1">
																<div>
																	<select name="produk[]" class="form-control">
																		<?php foreach($selectProduk as $x=>$y) :?>
																			<option value="<?=$y->idProduk;?>"><?=$y->namaProduk;?></option>
																		<?php endforeach;?>
																	</select>
																</div>
															</td>
															<td class="sorting_1">
																<div >
																	<select name="jenisProduk[]"class="form-control">
																		<?php foreach ($jenisProduk as $x=>$y):?>
																			<option value="<?=$y->idJenis; ?>"><?=$y->jenisProduk; ?></option>
																		<?php endforeach ;?>
																	</select>
																</div>
															</td>
															<td class="sorting_1">
																<div >
																	<select name="tipeUkur[]"class="form-control">
																		<option value="1">1</option>
																		<option value="2">2</option>
																		<option value="3">3</option>
																	</select>
																</div>
															</td>
															<td>
																<div >
																	<input style="width:40px;" name="size1[]" type="text" class="form-control">
																</div>
															</td>
															<td>
																<div >
																	<input style="width:40px;" name="size2[]" type="text" class="form-control">
																</div>
															</td>
															<td>
																<div >
																	<input style="width:40px;" name="size3[]" type="text" class="form-control">
																</div>
															</td>
															<td>
																<div >
																	<input style="width:40px;" name="size4[]" type="text" class="form-control" >
																</div>
															</td>
															<td>
																<div >
																	<input style="width:40px;" name="size5[]" type="text" class="form-control"  >
																</div>
															</td>
															<td>
																<div >
																	<input style="width:40px;" name="size6[]" type="text" class="form-control">
																</div>
															</td>
															<td>
																<div >
																	<input style="width:40px;" name="size7[]" type="text" class="form-control">
																</div>
															</td>
															<td class="sorting_1">
															
															</td>
														</tr>
														<?php endif;?>
												</tbody>
											</table>
										</div>
										<?php endif;?>
									</div>
								</div>
							</div>
							<!-- /.box-body -->
							<div class="box-footer">
								<?php if ($prefillSupply->idOutlet && !$_REQUEST['mode'] == 'edit' && !$_REQUEST['modes']== 'edit') :?>
									<a href="pasok.php" class="btn btn-info pull-left">Back</a>
								<?php else :?>
									<a href="pasok.php" class="btn btn-info pull-left">Back</a>
									<button type="submit" value="Simpan" name="submit"class="btn btn-info pull-right">Simpan</button>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>
			</div>
		</section>
	</div>
	<script>
		//Date picker
		$('#datepicker').datepicker({
			autoclose: true,
			dateFormat : 'yy-mm-dd'
		});
		

</script>

<?php Template::foot();?>