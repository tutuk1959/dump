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
$selectProduk = $db->table("SELECT a.idProduk, a.namaProduk FROM produk AS a ORDER BY a.idProduk,a.idJenisProduk");

//jenisProduk
$jenisProduk = $db->table("SELECT a.idJenis, a.jenisProduk FROM jenisproduk AS a ORDER BY a.idJenis");

//sizeProduk
$sizeProduk = $db->table("SELECT a.idSize, a.size FROM sizeproduk AS a ORDER BY a.idSize");

//insertMode
if ($_REQUEST['modes'] == 'insert' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['toko'], $_REQUEST['tanggalSupply'], $error);
	$tanggalymd = substr($_REQUEST['tanggalSupply'], 6,8).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-'.substr($_REQUEST['tanggalSupply'], 3,2);
	$tanggalymd1 = substr($_REQUEST['tanggalSupply'], 6,8).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-%';
	$satuBulanSebelumTanggalymd = date('Y-m', strtotime("$tanggalymd -1 month")).'-%';
	if (!$error){
		$cekPasokMaster = $db->row("SELECT COUNT(a.idSupply) AS jumlah, a.tanggalSupply, a.idSupply
			FROM supply AS a
			WHERE a.tanggalSupply LIKE '%s'",$tanggalymd1);
		if ($cekPasokMaster->jumlah > 0){
			$db->exec("UPDATE supply SET tanggalSupply = '%s',idOutlet = '%s' WHERE idSupply = '%s'", $tanggalymd, $_REQUEST['toko'],$cekPasokMaster->idOpname);
		} else {
			$db->exec("INSERT INTO supply(tanggalSupply,idOutlet) VALUES ('%s','%s')", $tanggalymd, $_REQUEST['toko']);
			$supplyMasterId = $db->insertID();
		}
		$supplyProduk = array('idProduk' => $_REQUEST['produk'],'idJenis' => $_REQUEST['jenisProduk'], 'idSize' => $_REQUEST['sizeProduk'], 'qty' => $_REQUEST['qty']);
		foreach($supplyProduk['idProduk'] as $k=>$v){
			$cek = $db->row("SELECT COUNT(a.idOpname) AS jumlah, a.idOpname
				FROM opname AS a 
				WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s'",$tanggalymd1,$_REQUEST['toko']);
			if ($cek->jumlah > 0 ){
				$cekPernahOpname = $db->row("SELECT COUNT(b.idOpname) AS jumlah, b.idOpname,a.idProduk,a.idSize,a.prevQty,a.qty,b.tanggalOpname,b.idOutlet
				FROM opnamedetail AS a 
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd1,$_REQUEST['toko'],$supplyProduk['idProduk'] [$k],$supplyProduk['idSize'][$k]);
				if ($cekPernahOpname->jumlah > 0){
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $supplyProduk['idProduk'][$k], $supplyProduk['idSize'][$k], $cekPernahOpname->tanggalOpname, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k]);
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$supplyProduk['qty'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$supplyProduk['qty'][$k],$supplyMasterId);
					}
					
					$db->exec("UPDATE opname SET idOutlet = '%s', tanggalOpname = '%s' WHERE idOpname = '%s'",$_REQUEST['toko'],$tanggalymd,$cek->idOpname);
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
						",$bulanOpname, $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname,$bulanOpname,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $supplyProduk['qty'][$k] + $cekStok->qty + $selish->totalPenjualan;
						} else {
							$prevQty = $supplyProduk['qty'][$k] + $cekStok->qty;
						}
						$qty = $supplyProduk['qty'][$k] + $cekStok->qty;
						$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname,$prevQty,$qty,$cekOpname->idOpname,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k]);
					} else {
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
						",$bulanOpname, $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname,$bulanOpname,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $supplyProduk['qty'][$k] + $cekStok->qty + $selish->totalPenjualan;
						} else {
							$prevQty = $supplyProduk['qty'][$k] + $cekStok->qty;
						}
						$qty = $supplyProduk['qty'][$k] + $cekStok->qty;
						$db->exec("UPDATE opnamedetail SET idProduk = '%s',idSize='%s',idOpname='%s',prevQty = '%s',qty='%s' WHERE idOpname = '%s' AND idProduk = '%s' AND idSize = '%s'",$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname,$prevQty,$qty,$cek->idOpname,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k]);
					}
				} else {
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $supplyProduk['idProduk'][$k], $supplyProduk['idSize'][$k], $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
					$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k]);
					if ($cekPasokDetail->jumlah > 0){
						$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$supplyProduk['qty'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
					} else {
						$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$supplyProduk['qty'][$k],$supplyMasterId);
					}
					$db->exec("UPDATE opname SET idOutlet = '%s', tanggalOpname = '%s' WHERE idOpname = '%s'",$_REQUEST['toko'],$tanggalymd,$cek->idOpname);
					if ($cekStok->qty > 0){
						$bulanOpname = date('Y-m', strtotime("$cek->tanggalOpname")).'-%';
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
						",$bulanOpname, $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname,$bulanOpname,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname);
						if ($selisih->totalPenjualan > 0){
							$prevQty = $supplyProduk['qty'][$k] + $cekStok->qty + $selish->totalPenjualan;
						} else {
							$prevQty = $supplyProduk['qty'][$k] + $cekStok->qty;
						}
						$qty = $supplyProduk['qty'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname,$prevQty,$qty);
					} else {
						$qty = $supplyProduk['qty'][$k] + $cekStok->qty;
						$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cek->idOpname,$qty,$qty);
					}
				}
			} else {
				$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
						FROM opnamedetail AS a
						LEFT JOIN opname AS b ON a.idOpname = b.idOpname
						WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' ORDER BY b.tanggalOpname DESC LIMIT 1", $supplyProduk['idProduk'][$k], $supplyProduk['idSize'][$k], $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
				$cekPasokDetail = $db->row("SELECT COUNT(b.idSupply) AS jumlah, b.tanggalSupply, b.idSupply, a.idProduk, a.idSize
						FROM supplydetail AS a
						LEFT JOIN supply AS b ON a.idSupply = b.idSupply
						WHERE b.tanggalSupply LIKE '%s' AND idProduk = '%s' AND idSize = '%s'",$tanggalymd1,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k]);
				if ($cekPasokDetail->jumlah > 0){
					$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply = '%s' WHERE idSupply = '%s'", $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$supplyProduk['qty'][$k],$cekPasokMaster->idSupply,$cekPasokMaster->idSupply);
				} else {
					$db->exec("INSERT INTO supplydetail(idProduk,idSize,qty,idSupply) VALUES ('%s','%s','%s','%s')", $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$supplyProduk['qty'][$k],$supplyMasterId);
				}
				$db->exec("INSERT INTO opname(idOutlet,tanggalOpname) VALUES ('%s','%s')",$_REQUEST['toko'],$tanggalymd);
				$opnameMasterBaruId = $db->insertID();
				if ($cekStok->qty > 0){
					$bulanOpname = date('Y-m', strtotime("$cekOpname->tanggalOpname")).'-%';
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
					",$bulanOpname, $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cekStok->idOpname,$bulanOpname,$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cekStok->idOpname);
					if ($selisih->totalPenjualan > 0){
						$prevQty = $supplyProduk['qty'][$k] + $cekStok->qty + $selish->totalPenjualan;
					} else {
						$prevQty = $supplyProduk['qty'][$k] + $cekStok->qty;
					}
					$qty = $supplyProduk['qty'][$k] + $cekStok->qty;
					$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$opnameMasterBaruId,$prevQty,$qty);
				} else {
					$qty = $supplyProduk['qty'][$k] + $cekStok->qty;
					$db->exec("INSERT INTO opnamedetail(idProduk,idSize,idOpname,prevQty,qty) VALUES ('%s','%s','%s','%s','%s')",$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$opnameMasterBaruId,$qty,$qty);
				} 
			}
			$message = 'Data Pasok Berhasil Ditambah..';
			$prefillSupply = $db->row("SELECT a.idOutlet, a.tanggalSupply FROM supply AS a WHERE a.idSupply = '%s'",$supplyMasterId);
			$prefillSupplyDetail = $db->table("SELECT a.idProduk, a.idSize, a.qty,b.namaProduk, c.size
			FROM supplydetail AS a 
			LEFT JOIN produk AS b ON a.idProduk = b.idProduk
			LEFT JOIN sizeproduk AS c ON c.idSize = a.idSize
			LEFT JOIN supply AS d ON a.idSupply = d.idSupply
			WHERE a.idSupply = '%s' AND d.tanggalSupply LIKE '%s' AND d.idOutlet = '%s'",$supplyMasterId,$tanggalymd,$_REQUEST['toko']);
		}
	} else {
		$error[] = 'Gagal Menambah Data Produk..';
	}
}
	
	//prefillEdit
	if ($_REQUEST['mode'] == 'edit'){
		$prefillSupply = $db->row("SELECT a.idOutlet, a.tanggalSupply FROM supply AS a WHERE a.idSupply = '%s'",$_REQUEST['idPasok']);
		$prefillSupplyDetail = $db->table("SELECT a.idProduk, a.idSize, a.qty,b.namaProduk, c.size,a.idSupplyDetail
		FROM supplydetail AS a 
		LEFT JOIN produk AS b ON a.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS c ON c.idSize = a.idSize
		LEFT JOIN supply AS d ON a.idSupply = d.idSupply
		WHERE a.idSupply = '%s' AND d.tanggalSupply LIKE '%s' AND d.idOutlet = '%s'",$_REQUEST['idPasok'],$_REQUEST['tanggalSupply'],$_REQUEST['idOutlet']);
	}
	//editMode
	if ($_REQUEST['modes'] == 'edit' && $_REQUEST['submit'] == 'Simpan'){
		_validation($_REQUEST['toko'], $_REQUEST['tanggalSupply'], $error);
		$tanggalymd = substr($_REQUEST['tanggalSupply'], 6,8).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-'.substr($_REQUEST['tanggalSupply'], 3,2);
		$satuBulanSebelumTanggalymd = date('Y-m', strtotime("$tanggalymd -1 month")).'-%';
		if (!$error){
			$db->exec("UPDATE supply SET tanggalSupply = '%s', idOutlet='%s' WHERE idSupply = '%s'", $tanggalymd, $_REQUEST['toko'],$_REQUEST['idSupply']);
			$supplyProduk = array('idProduk' => $_REQUEST['produk'],'idJenis' => $_REQUEST['jenisProduk'], 'idSize' => $_REQUEST['sizeProduk'], 'qty' => $_REQUEST['qty'],'idSupplyDetail' => $_REQUEST['idSupplyDetail']);
			foreach($supplyProduk['idProduk'] as $k=>$v){
				$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $supplyProduk['idProduk'][$k], $supplyProduk['idSize'][$k], $satuBulanSebelumTanggalymd, $_REQUEST['toko']);
				if ($cekStok->qty > 0){
					$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply='%s' WHERE idSupplyDetail = '%s'", $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$supplyProduk['qty'][$k],$_REQUEST['idSupply'],$supplyProduk['idSupplyDetail'][$k]);
					$cekOpname = $db->row("SELECT COUNT(a.idOpname) AS jumlah, a.idOpname FROM opname AS a WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s'",$tanggalymd,$_REQUEST['toko']);
					if ( $cekOpname->jumlah > 0){
						$db->exec("UPDATE opname SET idOutlet = '%s', tanggalOpname = '%s' WHERE idOpname = '%s'",$_REQUEST['toko'],$tanggalymd,$cekOpname->idOpname);
						if ($cekStok->qty = $supplyProduk['qty'][$k]){
							$qty = $supplyProduk['qty'][$k];
						} else {
							$qty = $supplyProduk['qty'][$k] + $cekStok->qty;
						}
						
						$opnameDetail = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty,a.idOpnameDetail
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idOpname = '%s'", $supplyProduk['idProduk'][$k], $supplyProduk['idSize'][$k], $tanggalymd, $_REQUEST['toko'],$cekOpname->idOpname);
						$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s',idOpname = '%s', prevQty = '%s', qty = '%s' WHERE idOpnameDetail = '%s'",$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cekOpname->idOpname,$qty,$qty,$opnameDetail->idOpnameDetail);
					} 
					$message = 'Data Pasok Berhasil Diubah..';
					$prefillSupply = $db->row("SELECT a.idOutlet, a.tanggalSupply FROM supply AS a WHERE a.idSupply = '%s'",$_REQUEST['idSupply']);
					$prefillSupplyDetail = $db->table("SELECT a.idProduk, a.idSize, a.qty,b.namaProduk, c.size
					FROM supplydetail AS a 
					LEFT JOIN produk AS b ON a.idProduk = b.idProduk
					LEFT JOIN sizeproduk AS c ON c.idSize = a.idSize
					LEFT JOIN supply AS d ON a.idSupply = d.idSupply
					WHERE a.idSupply = '%s' AND d.tanggalSupply LIKE '%s' AND d.idOutlet = '%s'",$_REQUEST['idSupply'],$tanggalymd,$_REQUEST['toko']);
				} else {
					$cekStok = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty 
					FROM opnamedetail AS a
					LEFT JOIN opname AS b ON a.idOpname = b.idOpname
					WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s'", $supplyProduk['idProduk'][$k], $supplyProduk['idSize'][$k], $tanggalymd, $_REQUEST['toko']);
					$db->exec("UPDATE supplydetail SET idProduk = '%s',idSize = '%s',qty = '%s',idSupply='%s' WHERE idSupplyDetail = '%s'", $supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$supplyProduk['qty'][$k],$_REQUEST['idSupply'],$supplyProduk['idSupplyDetail'][$k]);
					$cekOpname = $db->row("SELECT COUNT(a.idOpname) AS jumlah, a.idOpname FROM opname AS a WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s'",$tanggalymd,$_REQUEST['toko']);
					if ( $cekOpname->jumlah > 0){
						$db->exec("UPDATE opname SET idOutlet = '%s', tanggalOpname = '%s' WHERE idOpname = '%s'",$_REQUEST['toko'],$tanggalymd,$cekOpname->idOpname);
						if ($cekStok->qty = $supplyProduk['qty'][$k]){
							$qty = $supplyProduk['qty'][$k];
						} else {
							$qty = $supplyProduk['qty'][$k] + $cekStok->qty;
						}
						$opnameDetail = $db->row("SELECT b.idOpname,b.tanggalOpname,a.qty,a.idOpnameDetail
							FROM opnamedetail AS a
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							WHERE a.idProduk = '%s' AND a.idSize = '%s' AND b.tanggalOpname LIKE '%s' AND b.idOutlet = '%s' AND a.idOpname = '%s'", $supplyProduk['idProduk'][$k], $supplyProduk['idSize'][$k], $tanggalymd, $_REQUEST['toko'],$cekOpname->idOpname);
						$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s',idOpname = '%s', prevQty = '%s', qty = '%s' WHERE idOpnameDetail = '%s'",$supplyProduk['idProduk'][$k],$supplyProduk['idSize'][$k],$cekOpname->idOpname,$qty,$qty,$opnameDetail->idOpnameDetail);
					} 
					$message = 'Data Pasok Berhasil Diubah..';
					$prefillSupply = $db->row("SELECT a.idOutlet, a.tanggalSupply FROM supply AS a WHERE a.idSupply = '%s'",$_REQUEST['idSupply']);
					$prefillSupplyDetail = $db->table("SELECT a.idProduk, a.idSize, a.qty,b.namaProduk, c.size
					FROM supplydetail AS a 
					LEFT JOIN produk AS b ON a.idProduk = b.idProduk
					LEFT JOIN sizeproduk AS c ON c.idSize = a.idSize
					LEFT JOIN supply AS d ON a.idSupply = d.idSupply
					WHERE a.idSupply = '%s' AND d.tanggalSupply LIKE '%s' AND d.idOutlet = '%s'",$_REQUEST['idSupply'],$tanggalymd,$_REQUEST['toko']);
				}
			}
		} else {
	$error[] = 'Gagal Menambah Data Produk...';
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
			<a style="float: left;margin-bottom: 10px !important;" href="formPasok.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Pasok</button>
			</a>
			<a style="float: left;margin-left:5px !important;margin-bottom: 10px !important;" target="_blank"href="laporan-pasok.php?mode=report&tanggalSupply=<?=($_REQUEST['tanggalSupply']) ? $_REQUEST['tanggalSupply'] : date('Y-m',strtotime("now"));?>&idOutlet=<?=($_REQUEST['toko']) ? $_REQUEST['toko'] : 1;?>">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan</button>
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
												$tanggaldmy = substr($_REQUEST['tanggalSupply'],5,2).'/'.substr($_REQUEST['tanggalSupply'],8,2).'/'.substr($_REQUEST['tanggalSupply'],0,4);
											}
										?>
										<input name="tanggalSupply" type="text" class="form-control pull-right" value="<?=($tanggaldmy) ? $tanggaldmy : $_REQUEST['tanggalSupply'];?>" id="datepicker">
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
									<?php if ($_REQUEST['mode'] == 'insert' || $_REQUEST['modes'] == 'insert'):?>
									
										<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
											<thead>
												<tr role="row">
													<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Produk</th>
													<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Size</th>
													<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Qty</th>
													<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">
													<?php if ($_REQUEST['mode'] == 'insert'):?>
														<button type="button" data-id="addRow" class="btn btn-default">+</button>
													<?php endif;?>
													</th>
												</tr>
											</thead>
											
											<tbody>
												<?php if ($prefillSupply->idOutlet) :?>
													<?php foreach($prefillSupplyDetail as $k=>$v):?>
														
														<tr role="row">
															<td class="sorting_1">
																<div>
																	
																	<select name="produk[]" class="form-control">
																		<?php foreach($selectProduk as $x=>$y) :?>
																			<option value="<?=$v->idProduk;?>" <?=($v->idProduk == $y->idProduk) ? 'selected' : '';?>><?=$y->namaProduk;?></option>
																		<?php endforeach;?>
																	</select>
																</div>
															</td>
															<td class="sorting_1">
																<div >
																	<select name="sizeProduk[]"class="form-control">
																		<?php foreach ($sizeProduk as $x=>$y):?>
																			<option value="<?=$v->idSize; ?>" <?=($v->idSize == $y->idSize) ? 'selected' : '';?>><?=$y->size; ?></option>
																		<?php endforeach ;?>
																	</select>
																</div>
															</td>
														
															<td class="sorting_1">
																<div >
																	<input type="text" name="qty[]" value="<?=($v->qty) ? $v->qty : '';?>"class="form-control">
																</div>
															</td>

														</tr>
													<?php endforeach;?>
												<?php else :?>
													<tr role="row" id="temp-fdasfas">
														<td class="sorting_1">
															<div>
																<select name="produk[]" class="form-control">
																	<?php foreach($selectProduk as $k=>$v) :?>
																		<option value="<?=$v->idProduk;?>"><?=$v->namaProduk;?></option>
																	<?php endforeach;?>
																</select>
															</div>
														</td>
														<td class="sorting_1">
															<div >
																<select name="sizeProduk[]"class="form-control">
																	<?php foreach ($sizeProduk as $k=>$v):?>
																		<option value="<?=$v->idSize; ?>"><?=$v->size; ?></option>
																	<?php endforeach ;?>
																</select>
															</div>
														</td>
														
														<td class="sorting_1">
															<div >
																<input type="text" name="qty[]" class="form-control">
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
									<?php elseif ($_REQUEST['mode'] == 'edit' || $_REQUEST['modes'] == 'edit'):?>
										<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
											<thead>
												<tr role="row">
													<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Produk</th>
													<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Size</th>
													<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Qty</th>
													
												</tr>
											</thead>
											<tbody>
												<input type="hidden" name="idSupply" value="<?=$_REQUEST['idPasok'];?>"/>
												<?php if ($prefillSupply->idOutlet) :?>
													<?php foreach($prefillSupplyDetail as $k=>$v):?>
														<tr role="row">
															<td class="sorting_1">
																<div>
																	<input type="hidden" name="idSupplyDetail[]" value="<?=$v->idSupplyDetail;?>"/>
																	<select name="produk[]" class="form-control">
																		<?php foreach($selectProduk as $x=>$y) :?>
																			<option value="<?=$v->idProduk;?>" <?=($v->idProduk == $y->idProduk) ? 'selected' : '';?>><?=$y->namaProduk;?></option>
																		<?php endforeach;?>
																	</select>
																</div>
															</td>
															<td class="sorting_1">
																<div >
																	<select name="sizeProduk[]"class="form-control">
																		<?php foreach ($sizeProduk as $x=>$y):?>
																			<option value="<?=$v->idSize; ?>" <?=($v->idSize == $y->idSize) ? 'selected' : '';?>><?=$y->size; ?></option>
																		<?php endforeach ;?>
																	</select>
																</div>
															</td>
														
															<td class="sorting_1">
																<div >
																	<input type="text" name="qty[]" value="<?=($v->qty) ? $v->qty : '';?>"class="form-control">
																</div>
															</td>
														</tr>
													<?php endforeach;?>
												<?php else :?>
													<tr role="row" id="temp-fdasfas">
														<td class="sorting_1">
															<div>
																<select name="produk[]" class="form-control">
																	<?php foreach($selectProduk as $k=>$v) :?>
																		<option value="<?=$v->idProduk;?>"><?=$v->namaProduk;?></option>
																	<?php endforeach;?>
																</select>
															</div>
														</td>
														<td class="sorting_1">
															<div >
																<select name="sizeProduk[]"class="form-control">
																	<?php foreach ($sizeProduk as $k=>$v):?>
																		<option value="<?=$v->idSize; ?>"><?=$v->size; ?></option>
																	<?php endforeach ;?>
																</select>
															</div>
														</td>
														
														<td class="sorting_1">
															<div >
																<input type="text" name="qty[]" class="form-control">
															</div>
														</td>
													</tr>
												<?php endif;?>
											</tbody>
										</table>
										<?php endif; ?>
									</div>
								</div>
							</div>
							
							<!-- /.box-body -->
							<div class="box-footer">
								<?php if ($prefillSupply->idOutlet && !$_REQUEST['mode'] == 'edit' && !$_REQUEST['modes']== 'edit') :?>
									<a href="produk.php" class="btn btn-info pull-left">Back</a>
								<?php else :?>
									<a href="produk.php" class="btn btn-info pull-left">Back</a>
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