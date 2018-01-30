<?php
session_start();
include "classes/db.class.php";
include  "classes/hak-akses.inc.php";
include "classes/template.class.php";
include "classes/class.file.php";
include "pagination.php";
include "produksi.function.php";
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
$selectProduk = $db->table("SELECT a.idProduk, a.namaProduk FROM produk AS a ORDER BY a.idProduk");

//jenisProduk
$jenisProduk = $db->table("SELECT a.idJenis, a.jenisProduk FROM jenisproduk AS a ORDER BY a.idJenis");

//sizeProduk
$sizeProduk = $db->table("SELECT a.idSize, a.size,a.tipeUkur FROM sizeproduk AS a ORDER BY a.idSize");

//insertMode
if ($_REQUEST['modes'] == 'insert' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['tanggalSelesaiProduksi'],$error);
	if(!$error){
		$tanggalymd = substr($_REQUEST['tanggalSelesaiProduksi'], 6,8).'-'.substr($_REQUEST['tanggalSelesaiProduksi'], 0,2).'-'.substr($_REQUEST['tanggalSelesaiProduksi'], 3,2);
		$cekPasokMaster = $db->row("SELECT COUNT(a.idProduksi) AS jumlah, a.tanggalSelesaiProduksi, a.idProduksi
			FROM produksi AS a
			WHERE a.tanggalSelesaiProduksi LIKE '%s'",$tanggalymd);
		if ($cekPasokMaster->jumlah > 0){
			$db->exec("UPDATE produksi SET tanggalSelesaiProduksi = '%s',keterangan = '%s' WHERE idProduksi = '%s'", $tanggalymd, $_REQUEST['keterangan'],$cekPasokMaster->idProduksi);
			$supplyMasterId = $cekPasokMaster->idProduksi;
		} else {
			$db->exec("INSERT INTO produksi(tanggalSelesaiProduksi,keterangan) VALUES ('%s','%s')", $tanggalymd, $_REQUEST['keterangan']);
			$supplyMasterId = $db->insertID();
		}
		
		foreach($_REQUEST['produk'] as $k=>$v){
			if ($_REQUEST['tipeUkur'][$k] == 1){
				//sizeXXSproses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],16);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,16);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$supplyMasterId);
				}
				
				//sizeXSproses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],17);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,17);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$supplyMasterId);
				}
				
				//sizeSproses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],18);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,18);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$supplyMasterId);
				}
				
				//sizeMproses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],19);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,19);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$supplyMasterId);
				}
				
				//sizeLproses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],20);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,20);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$supplyMasterId);
				}
				
				//sizeXLproses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],21);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,21);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$supplyMasterId);
				}
				
				//sizeXXLproses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],22);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,22);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$supplyMasterId);
				}
			} if ($_REQUEST['tipeUkur'][$k] == 2){
				//size38proses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],23);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,23);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$supplyMasterId);
				}
				
				//size39proses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],24);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,24);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$supplyMasterId);
				}
				
				//size40proses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],25);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,25);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$supplyMasterId);
				}
				
				//size41proses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],26);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,26);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$supplyMasterId);
				}
				
				//size42proses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],27);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,27);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$supplyMasterId);
				}
				
				//size43proses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],28);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,28);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],29,$_REQUEST['size6'][$k],$supplyMasterId);
				}
				
				//size44proses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],29);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,29);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$supplyMasterId);
				}
			} if ($_REQUEST['tipeUkur'][$k] == 3){
				//sizeAllproses--------------------------------------------------------------------------------------------------------
				$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
					FROM produksidetail AS a
					LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
					WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s'",$tanggalymd,$_REQUEST['produk'][$k],30);
				if ($cekProduksiDetail->jumlah > 0){
					$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,30);
				} else {
					$db->exec("INSERT INTO produksidetail(idProduk,idSize,qty,idProduksi) VALUES ('%s','%s','%s','%s')", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$supplyMasterId);
				}
			}
			$message = "Berhasil memasukkan data produksi";
			$prefillProduksi = $db->row("SELECT a.idProduksi, a.tanggalSelesaiProduksi, a.keterangan FROM produksi AS a WHERE a.idProduksi = '%s'",$supplyMasterId);
			foreach ($db->table("SELECT a.idProduksi,b.idProduksiDetail, a.tanggalSelesaiProduksi,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,e.idJenis
			FROM produksi AS a
			LEFT JOIN produksidetail AS b ON a.idProduksi = b.idProduksi
			LEFT JOIN produk AS c ON c.idProduk = b.idProduk
			LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
			LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
			WHERE a.idProduksi = '%s' AND a.tanggalSelesaiProduksi LIKE '%s' GROUP BY b.idSize,b.idProduk ",$supplyMasterId,$prefillProduksi->tanggalSelesaiProduksi) as $row){
				$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idProduksiDetail'] = $row->idProduksiDetail;
				$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
				$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
			}
		}
	}else{
		$error[] = "Gagal memasukan data produksi";
	}
}
//prefillEdit
if ($_REQUEST['mode'] == 'edit' && isset($_REQUEST['idProduksi']) && !isset($_REQUEST['submit'])){
	$tanggalProduksi = $_REQUEST['tanggalSelesaiProduksi'];
	$prefillProduksi = $db->row("SELECT COUNT(a.idProduksi) AS jumlahProduksi,a.idProduksi, a.tanggalSelesaiProduksi, a.keterangan FROM produksi AS a WHERE a.idProduksi = '%s' AND a.tanggalSelesaiProduksi = '%s'",$_REQUEST['idProduksi'],$tanggalProduksi);
	if ($prefillProduksi->jumlahProduksi > 0){
		foreach ($db->table("SELECT a.idProduksi,b.idProduksiDetail, a.tanggalSelesaiProduksi,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,e.idJenis
		FROM produksi AS a
		LEFT JOIN produksidetail AS b ON a.idProduksi = b.idProduksi
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		WHERE a.idProduksi = '%s' AND a.tanggalSelesaiProduksi LIKE '%s' GROUP BY b.idSize,b.idProduk ",$_REQUEST['idProduksi'],$_REQUEST['tanggalSelesaiProduksi']) as $row){
			$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idProduksiDetail'] = $row->idProduksiDetail;
			$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
			$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
		}
	} else {
		$error[] = 'Tidak ada data produksi yang ditemukan!';
	}
}

//EditMode
if ($_REQUEST['modes'] == 'edit' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['tanggalSelesaiProduksi'],$error);
	if(!$error){
		$tanggalymd = substr($_REQUEST['tanggalSelesaiProduksi'], 6,8).'-'.substr($_REQUEST['tanggalSelesaiProduksi'], 0,2).'-'.substr($_REQUEST['tanggalSelesaiProduksi'], 3,2);
		$cekPasokMaster = $db->row("SELECT COUNT(a.idProduksi) AS jumlah, a.tanggalSelesaiProduksi, a.idProduksi
			FROM produksi AS a
			WHERE a.tanggalSelesaiProduksi LIKE '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['idProduksi']);
		if ($cekPasokMaster->jumlah > 0){
			$db->exec("UPDATE produksi SET tanggalSelesaiProduksi = '%s',keterangan = '%s' WHERE idProduksi = '%s'", $tanggalymd, $_REQUEST['keterangan'],$cekPasokMaster->idProduksi);
			$supplyMasterId = $cekPasokMaster->idProduksi;
			
			foreach($_REQUEST['produk']as $k=>$v){
				if ($_REQUEST['tipeUkur'][$k] == 1){
					//sizeXXSproses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],16,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],16,$_REQUEST['size1'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,16);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//sizeXSproses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],17,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],17,$_REQUEST['size2'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,17);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//sizeSproses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],18,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],18,$_REQUEST['size3'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,18);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//sizeMproses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],19,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],19,$_REQUEST['size4'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,19);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//sizeLproses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],20,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],20,$_REQUEST['size5'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,20);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//sizeXLproses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],21,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],21,$_REQUEST['size6'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,21);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//sizeXXLproses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],22,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],22,$_REQUEST['size7'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,22);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
				} if ($_REQUEST['tipeUkur'][$k] == 2){
					//size38proses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],23,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],23,$_REQUEST['size1'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,23);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//size39proses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],24,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],24,$_REQUEST['size2'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,24);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//size40proses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],25,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],25,$_REQUEST['size3'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,25);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//size41proses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],26,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],26,$_REQUEST['size4'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,26);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//size42proses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],27,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],27,$_REQUEST['size5'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,27);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//size43proses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],28,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],28,$_REQUEST['size6'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,28);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
					
					//size44proses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],29,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],29,$_REQUEST['size7'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,29);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
				} if ($_REQUEST['tipeUkur'][$k] == 3){
					//sizeAllproses--------------------------------------------------------------------------------------------------------
					$cekProduksiDetail = $db->row("SELECT COUNT(b.idProduksi) AS jumlah, b.tanggalSelesaiProduksi, b.idProduksi, a.idProduk, a.idSize,a.qty
						FROM produksidetail AS a
						LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
						WHERE b.tanggalSelesaiProduksi LIKE '%s' AND a.idProduk = '%s' AND a.idSize = '%s' AND a.idProduksi = '%s'",$tanggalymd,$_REQUEST['produk'][$k],30,$cekPasokMaster->idProduksi);
					if ($cekProduksiDetail->jumlah > 0){
						$db->exec("UPDATE produksidetail SET idProduk = '%s',idSize = '%s',qty = '%s',idProduksi = '%s' WHERE idProduksi = '%s' AND idSize = '%s'", $_REQUEST['produk'][$k],30,$_REQUEST['size1'][$k],$cekProduksiDetail->idProduksi,$cekProduksiDetail->idProduksi,30);
					} else {
						$error[] = "Data produksi detail size XXS tersebut tidak ditemukan";
					}
				}
				
				$message = "Berhasil mengubah data produksi";
				$prefillProduksi = $db->row("SELECT a.idProduksi, a.tanggalSelesaiProduksi, a.keterangan FROM produksi AS a WHERE a.idProduksi = '%s'",$cekPasokMaster->idProduksi);
				foreach ($db->table("SELECT a.idProduksi,b.idProduksiDetail, a.tanggalSelesaiProduksi,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,e.idJenis
				FROM produksi AS a
				LEFT JOIN produksidetail AS b ON a.idProduksi = b.idProduksi
				LEFT JOIN produk AS c ON c.idProduk = b.idProduk
				LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
				LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
				WHERE a.idProduksi = '%s' AND a.tanggalSelesaiProduksi LIKE '%s' GROUP BY b.idSize,b.idProduk ",$cekPasokMaster->idProduksi,$prefillProduksi->tanggalSelesaiProduksi) as $row){
					$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
					$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idProduksiDetail'] = $row->idProduksiDetail;
					$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
					$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
					$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
					$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
					$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
					$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
				}
			}
		} else {
			$error[] = "Data produksi tanggal tersebut tidak ditemukan";
		}
	}
}
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<?php ($_REQUEST['mode'] == 'insert')? $msg = 'Tambah Produksi Baru' :$msg='Ubah Data Produksi';?>
			<h1 style="float: left;margin-right: 10px !important;">
				<?=$msg;?>
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formProduksi.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Produksi</button>
			</a>
			<a style="float: right;margin-bottom: 10px !important;" href="">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan</button>
			</a>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-md-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Form Produksi</h3>
						</div>
						<form method="post" class="form-horizontal">
							<div class="box-body">
								<div class="row" style="margin-bottom:10px !important;">
									<input type="hidden" name="modes" value="<?= ($_REQUEST['mode'] == 'insert') ? 'insert' : 'edit'?>"/>
									<div class="col-sm-6">
										<input type="text" class="form-control" name="keterangan" placeholder="<?=($prefillProduksi->keterangan) ? $prefillProduksi->keterangan : '(Kosong)'?>"/>
									</div>
									<div class="col-sm-6">
										<div class="input-group date">
										<div class="input-group-addon">
											<i class="fa fa-calendar"></i>
										</div>
										<?php
											if ($prefillProduksi->tanggalSelesaiProduksi){
												$tanggaldmy = substr($prefillProduksi->tanggalSelesaiProduksi,5,2).'/'.substr($prefillProduksi->tanggalSelesaiProduksi,8,2).'/'.substr($prefillProduksi->tanggalSelesaiProduksi,0,4);
											} elseif ($_REQUEST['tanggalSelesaiProduksi']){
												$tanggaldmy = $_REQUEST['tanggalSelesaiProduksi'];
											}
										?>
										<input name="tanggalSelesaiProduksi" type="text" class="form-control pull-right" value="<?=($tanggaldmy) ? $tanggaldmy : $_REQUEST['tanggalSelesaiProduksi'];?>" id="datepicker">
										<?php if (isset($_REQUEST['idProduksi']) && $_REQUEST['mode'] == 'edit'):?>
											<input type="hidden" name="idProduksi" value = "<?=$_REQUEST['idProduksi'];?>" />
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
												<?php if ($prefillProduksi->tanggalSelesaiProduksi) :?>
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
													<?php if ($prefillProduksi->tanggalSelesaiProduksi) :?>
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
									<a href="master-produksi.php" class="btn btn-info pull-left">Back</a>
								<?php else :?>
									<a href="master-produksi.php" class="btn btn-info pull-left">Back</a>
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