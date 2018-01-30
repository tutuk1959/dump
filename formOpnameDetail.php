<?php
set_time_limit(1200);
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "opname.function.php";
$db = new DB();
$file = new File();
if ($_REQUEST['mode'] == 'insert'){
	kickInsert($hakAkses[$_SESSION['hak']]);
} else {
	kickManage($hakAkses[$_SESSION['hak']]);
}

//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;

if ($_REQUEST['mode'] == 'insert' && isset($_REQUEST['toko']) && isset($_REQUEST['tanggalOpname'])){
	$bulanOpname = substr($_REQUEST['tanggalOpname'], 6,10).'-'.substr($_REQUEST['tanggalOpname'], 0,2).'-'.substr($_REQUEST['tanggalOpname'], 3,2);
	$bulanOpnameSebelumnya = date('Y-m', strtotime("$bulanOpname -1 MONTH")).'-%';
	$bulanOpnameNow = substr($_REQUEST['tanggalOpname'], 6,10).'-'.substr($_REQUEST['tanggalOpname'], 0,2).'-%';
	$countNow = $db->row("SELECT COUNT(a.idOpname) AS jumlahOpname, a.tanggalOpname FROM opname AS a 
	WHERE a.tanggalOpname LIKE '%s' AND idOutlet = '%s'  ORDER BY a.tanggalOpname DESC LIMIT 1", $bulanOpnameNow,$_REQUEST['toko']);
	$countPast = $db->row("SELECT COUNT(a.idOpname) AS jumlahOpname, a.tanggalOpname FROM opname AS a 
	WHERE a.tanggalOpname LIKE '%s' AND idOutlet = '%s' ORDER BY a.tanggalOpname DESC LIMIT 1", $bulanOpnameSebelumnya,$_REQUEST['toko']);
	if ($countNow->jumlahOpname > 0){
		foreach ($db->table("SELECT a.idOpname, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
		FROM opname AS a
		LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ORDER BY c.idJenisProduk", $countNow->tanggalOpname,$_REQUEST['toko']) as $row){
		
		$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
		$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]  += $row->jumlahproduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
		}
	} else {
		if ($countPast->jumlahOpname > 0){
			echo "Bulan lalu ada";
			$tanggalym = date('Y-m', strtotime($countPast->tanggalOpname)).'-%';
			echo $tanggalym;
			foreach ($db->table("SELECT a.idOpname, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
			FROM opname AS a
			LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
			LEFT JOIN produk AS c ON c.idProduk = b.idProduk
			LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
			LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
			WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ORDER BY c.idJenisProduk", $tanggalym,$_REQUEST['toko']) as $row){
			$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
			$data[ $row->idProduk ][ $row->jenisProduk ][$row->size] += $row->jumlahproduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
			}
		} else {
			foreach ($db->table("SELECT a.idOpname, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
			FROM opname AS a
			LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
			LEFT JOIN produk AS c ON c.idProduk = b.idProduk
			LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
			LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
			WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ORDER BY c.idJenisProduk", $bulanOpnameNow,$_REQUEST['toko']) as $row){
			$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
			$data[ $row->idProduk ][ $row->jenisProduk ][$row->size] += $row->jumlahproduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
			}
		}
	}
	
} elseif($_REQUEST['mode'] == 'edit' && isset($_REQUEST['toko']) && isset($_REQUEST['tanggalOpname']) && isset($_REQUEST['idOpname'])){
	$bulanOpname = substr($_REQUEST['tanggalOpname'], 6,10).'-'.substr($_REQUEST['tanggalOpname'], 0,2).'-'.substr($_REQUEST['tanggalOpname'], 3,2);
	$bulOpname = substr($_REQUEST['tanggalOpname'], 6,10).'-'.substr($_REQUEST['tanggalOpname'], 0,2).'-%';
	$bulOpnameSebelum = date('Y-m', strtotime("$bulanOpname -1month")).'-%';
	$count = $db->row("SELECT COUNT(a.idOpname) AS jumlahOpname, a.tanggalOpname FROM opname AS a 
	WHERE a.tanggalOpname LIKE '%s' ORDER BY a.tanggalOpname DESC LIMIT 1", $bulOpname);
	$countSebelum = $db->row("SELECT COUNT(a.idOpname) AS jumlahOpname, a.tanggalOpname FROM opname AS a 
	WHERE a.tanggalOpname LIKE '%s' ORDER BY a.tanggalOpname DESC LIMIT 1", $bulOpnameSebelum);
	if ($count->jumlahOpname > 0){
		$tanggalymd = date('Y-m-d', strtotime($count->tanggalOpname));
		$tanggalymdSebelum = date('Y-m-d', strtotime("$count->tanggalOpname -1month"));
		foreach ($db->table("SELECT a.idOpname,b.idOpnameDetail, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
		FROM opname AS a
		LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ",$tanggalymd,$_REQUEST['toko']) as $row){
		$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idOpnameDetail'] = $row->idOpnameDetail;
		$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
		$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
		}
	} elseif ($count->jumlahOpname > 0 && !$countSebelum->jumlahOpname > 0){
		$tanggalymd = date('Y-m-d', strtotime($count->tanggalOpname));
		foreach ($db->table("SELECT a.idOpname,b.idOpnameDetail, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
		FROM opname AS a
		LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' AND b.idOpname = '%s' GROUP BY b.idSize,b.idProduk ",$tanggalymd,$_REQUEST['toko'],$_REQUEST['idOpname']) as $row){
		$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idOpnameDetail'] = $row->idOpnameDetail;
		$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
		$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
		}
	} else {
		$error[] = 'Tidak ada data opname yang ditemukan!';
	}
}
//insertMode
if ($_REQUEST['modes'] == 'insert' || $_REQUEST['opname'] == 'Simpan'){
	echo "Executing";
	_validation($_REQUEST['outlet'],$_REQUEST['tanggalOpnameBaru'], $error);
	if (!$error){
		$countNow = $db->row("SELECT COUNT(a.idOpname) AS jumlahOpname, a.tanggalOpname,a.idOpname FROM opname AS a 
		WHERE a.tanggalOpname LIKE '%s' AND idOutlet = '%s' ORDER BY a.tanggalOpname DESC LIMIT 1", $_REQUEST['tanggalOpnameBaru'],$_REQUEST['outlet']);
		echo $countNow->jumlahOpname;
		if ($countNow->jumlahOpname > 0){
			echo "Ada data opname now";
			$bulBaru = $_REQUEST['tanggalOpnameBaru'];
			$db->exec("DELETE FROM opnamedetail WHERE idOpname = '%s'",$countNow->idOpname);
			foreach($_REQUEST['idProduk'] as $k=>$v){
				if ($_REQUEST['tipeukur'][$k] == 1){
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],16,$countNow->idOpname,$_REQUEST['prevXXS'][$k],$_REQUEST['XXS'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],17,$countNow->idOpname,$_REQUEST['prevXS'][$k],$_REQUEST['XS'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],18,$countNow->idOpname,$_REQUEST['prevS'][$k],$_REQUEST['S'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],19,$countNow->idOpname,$_REQUEST['prevM'][$k],$_REQUEST['M'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],20,$countNow->idOpname,$_REQUEST['prevL'][$k],$_REQUEST['L'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],21,$countNow->idOpname,$_REQUEST['prevXL'][$k],$_REQUEST['XL'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],22,$countNow->idOpname,$_REQUEST['prevXXL'][$k],$_REQUEST['XXL'][$k]);
				} elseif ($_REQUEST['tipeukur'][$k] == 2){
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],23,$countNow->idOpname,$_REQUEST['prevs38'][$k],$_REQUEST['s38'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],24,$countNow->idOpname,$_REQUEST['prevs39'][$k],$_REQUEST['s39'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],25,$countNow->idOpname,$_REQUEST['prevs40'][$k],$_REQUEST['s40'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],26,$countNow->idOpname,$_REQUEST['prevs41'][$k],$_REQUEST['s41'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],27,$countNow->idOpname,$_REQUEST['prevs42'][$k],$_REQUEST['s42'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],28,$countNow->idOpname,$_REQUEST['prevs43'][$k],$_REQUEST['s43'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],29,$countNow->idOpname,$_REQUEST['prevs44'][$k],$_REQUEST['s44'][$k]);
				} elseif ($_REQUEST['tipeukur'][$k] == 3){
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],30,$countNow->idOpname,$_REQUEST['prevall'][$k],$_REQUEST['all'][$k]);
				}
			}
			$message = 'Data produk Opname ditambah.';
			$disabledbutton = 'disabled';
			foreach ($db->table("SELECT a.idOpname, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
			FROM opname AS a
			LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
			LEFT JOIN produk AS c ON c.idProduk = b.idProduk
			LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
			LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
			WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ", $bulBaru,$_REQUEST['outlet']) as $row){
				$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
				$data[ $row->idProduk ][ $row->jenisProduk ][$row->size] += $row->jumlahproduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
			}
		} else{
			echo "Ada data opname lalu";
			$bulBaru = $_REQUEST['tanggalOpnameBaru'];
			$db->exec("INSERT INTO opname(tanggalOpname,idOutlet) VALUES ('%s','%s')",$_REQUEST['tanggalOpnameBaru'],$_REQUEST['outlet']);
			$idOpnameBaru = $db->insertID();
			foreach($_REQUEST['idProduk'] as $k=>$v){
				if ($_REQUEST['tipeukur'][$k] == 1){
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],16,$idOpnameBaru,$_REQUEST['prevXXS'][$k],$_REQUEST['XXS'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],17,$idOpnameBaru,$_REQUEST['prevXS'][$k],$_REQUEST['XS'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],18,$idOpnameBaru,$_REQUEST['prevS'][$k],$_REQUEST['S'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],19,$idOpnameBaru,$_REQUEST['prevM'][$k],$_REQUEST['M'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],20,$idOpnameBaru,$_REQUEST['prevL'][$k],$_REQUEST['L'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],21,$idOpnameBaru,$_REQUEST['prevXL'][$k],$_REQUEST['XL'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],22,$idOpnameBaru,$_REQUEST['prevXXL'][$k],$_REQUEST['XXL'][$k]);
				} elseif ($_REQUEST['tipeukur'][$k] == 2){
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],23,$idOpnameBaru,$_REQUEST['prevs38'][$k],$_REQUEST['s38'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],24,$idOpnameBaru,$_REQUEST['prevs39'][$k],$_REQUEST['s39'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],25,$idOpnameBaru,$_REQUEST['prevs40'][$k],$_REQUEST['s40'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],26,$idOpnameBaru,$_REQUEST['prevs41'][$k],$_REQUEST['s41'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],27,$idOpnameBaru,$_REQUEST['prevs42'][$k],$_REQUEST['s42'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],28,$idOpnameBaru,$_REQUEST['prevs43'][$k],$_REQUEST['s43'][$k]);
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],29,$idOpnameBaru,$_REQUEST['prevs44'][$k],$_REQUEST['s44'][$k]);
				} elseif ($_REQUEST['tipeukur'][$k] == 3){
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],30,$idOpnameBaru,$_REQUEST['prevall'][$k],$_REQUEST['all'][$k]);
					
				
				}
			}
			$message = 'Data produk Opname ditambah.';
			$disabledbutton = 'disabled';
			echo $bulBaru;
			foreach ($db->table("SELECT a.idOpname, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
			FROM opname AS a
			LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
			LEFT JOIN produk AS c ON c.idProduk = b.idProduk
			LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
			LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
			WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ", $bulBaru,$_REQUEST['outlet']) as $row){
				$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
				$data[ $row->idProduk ][ $row->jenisProduk ][$row->size] += $row->jumlahproduk;
				$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
			}
		} 
	} else {
		$error[] = 'Gagal menambah data opname';
	}
}
//editMode
	if ($_REQUEST['modes'] == 'edit' && $_REQUEST['submit'] == 'Simpan'){
	
	_validation($_REQUEST['outlet'],$_REQUEST['tanggalOpnameBaru'], $error);
	if (!$error){
		$bulBaru = $_REQUEST['tanggalOpnameBaru'];
		$db->exec("UPDATE opname SET tanggalOpname = '%s', idOutlet = '%s' WHERE idOpname = '%s'",$_REQUEST['tanggalOpnameBaru'],$_REQUEST['outlet'], $_REQUEST['idOpname']);
		foreach($_REQUEST['idProduk'] as $k=>$v){
			if ($_REQUEST['tipeukur'][$k] == 1){
				$selXXS = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],16);
				if ($selXXS->jumlah > 0 ){
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize='%s'",$_REQUEST['idProduk'][$k],16,$_REQUEST['idOpname'],$_REQUEST['prevXXS'][$k],$_REQUEST['XXS'][$k],$_REQUEST['OpnameDetail']['XXS'][$k],16);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],16,$_REQUEST['idOpname'],$_REQUEST['prevXXS'][$k],$_REQUEST['XXS'][$k]);
				}
				
				$selXS = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],17);
				if ($selXS->jumlah > 0 ){
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],17,$_REQUEST['idOpname'],$_REQUEST['prevXS'][$k],$_REQUEST['XS'][$k],$_REQUEST['OpnameDetail']['XS'][$k],17);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],17,$_REQUEST['idOpname'],$_REQUEST['prevXS'][$k],$_REQUEST['XS'][$k]);
				}
				
				$selS = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],18);
				if ($selS->jumlah > 0 ){
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize='%s'",$_REQUEST['idProduk'][$k],18,$_REQUEST['idOpname'],$_REQUEST['prevS'][$k],$_REQUEST['S'][$k], $_REQUEST['OpnameDetail']['S'][$k],18);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],18,$_REQUEST['idOpname'],$_REQUEST['prevS'][$k],$_REQUEST['S'][$k]);
				}
				
				$selM = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],19);
				if ($selM->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize='%s'",$_REQUEST['idProduk'][$k],19,$_REQUEST['idOpname'],$_REQUEST['prevM'][$k],$_REQUEST['M'][$k],$_REQUEST['OpnameDetail']['M'][$k],19);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],19,$_REQUEST['idOpname'],$_REQUEST['prevM'][$k],$_REQUEST['M'][$k]);
				}
				
				$selL = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],20);
				if ($selL->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize='%s'",$_REQUEST['idProduk'][$k],20,$_REQUEST['idOpname'],$_REQUEST['prevL'][$k],$_REQUEST['L'][$k],$_REQUEST['OpnameDetail']['L'][$k],20);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],20,$_REQUEST['idOpname'],$_REQUEST['prevL'][$k],$_REQUEST['L'][$k]);
				}
				
				$selXL = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],21);
				if ($selXL->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize='%s'",$_REQUEST['idProduk'][$k],21,$_REQUEST['idOpname'],$_REQUEST['prevXL'][$k],$_REQUEST['XL'][$k],$_REQUEST['OpnameDetail']['XL'][$k],21);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],21,$_REQUEST['idOpname'],$_REQUEST['prevXL'][$k],$_REQUEST['XL'][$k]);
				}
				
				
				$selXXL = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],22);
				if ($selXXL->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],22,$_REQUEST['idOpname'],$_REQUEST['prevXXL'][$k],$_REQUEST['XXL'][$k],$_REQUEST['OpnameDetail']['XXL'][$k],22);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],22,$_REQUEST['idOpname'],$_REQUEST['prevXXL'][$k],$_REQUEST['XXL'][$k]);
				}
				
				
				
			} elseif ($_REQUEST['tipeukur'][$k] == 2){
				$sel38 = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],23);
				if ($sel38->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],23,$_REQUEST['idOpname'],$_REQUEST['prevs38'][$k],$_REQUEST['s38'][$k],$_REQUEST['OpnameDetail']['s38'][$k],23);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],23,$_REQUEST['idOpname'],$_REQUEST['prevs38'][$k],$_REQUEST['s38'][$k]);
				}
				
				$sel39 = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],24);
				if ($sel39->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],24,$_REQUEST['idOpname'],$_REQUEST['prevs39'][$k],$_REQUEST['s39'][$k],$_REQUEST['OpnameDetail']['s39'][$k],24);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],24,$_REQUEST['idOpname'],$_REQUEST['prevs39'][$k],$_REQUEST['s39'][$k]);
				}
				
				$sel40 = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],25);
				if ($sel40->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],25,$_REQUEST['idOpname'],$_REQUEST['prevs40'][$k],$_REQUEST['s40'][$k],$_REQUEST['OpnameDetail']['s40'][$k],25);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],25,$_REQUEST['idOpname'],$_REQUEST['prevs40'][$k],$_REQUEST['s40'][$k]);
				}
				
				$sel41 = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],26);
				if ($sel41->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],26,$_REQUEST['idOpname'],$_REQUEST['prevs41'][$k],$_REQUEST['s41'][$k],$_REQUEST['OpnameDetail']['s41'][$k],26);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],26,$_REQUEST['idOpname'],$_REQUEST['prevs41'][$k],$_REQUEST['s41'][$k]);
				}
				
				$sel42 = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],27);
				if ($sel42->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],27,$_REQUEST['idOpname'],$_REQUEST['prevs42'][$k],$_REQUEST['s42'][$k],$_REQUEST['OpnameDetail']['s42'][$k],27);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],27,$_REQUEST['idOpname'],$_REQUEST['prevs42'][$k],$_REQUEST['s42'][$k]);
				}
				
				$sel43 = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],28);
				if ($sel43->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],28,$_REQUEST['idOpname'],$_REQUEST['prevs43'][$k],$_REQUEST['s43'][$k],$_REQUEST['OpnameDetail']['s43'][$k],28);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],28,$_REQUEST['idOpname'],$_REQUEST['prevs43'][$k],$_REQUEST['s43'][$k]);
				}
				
				$sel44 = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],29);
				if ($sel44->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],29,$_REQUEST['idOpname'],$_REQUEST['prevs44'][$k],$_REQUEST['s44'][$k],$_REQUEST['OpnameDetail']['s44'][$k],29);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],29,$_REQUEST['idOpname'],$_REQUEST['prevs44'][$k],$_REQUEST['s44'][$k]);
				}
				
				
			} elseif ($_REQUEST['tipeukur'][$k] == 3){
				$selAll = $db->row("SELECT COUNT(a.idOpnameDetail) AS jumlah,idOpnameDetail
				FROM opnamedetail AS a
				LEFT JOIN opname AS b ON a.idOpname = b.idOpname
				WHERE b.idOpname = '%s' AND a.idSize = '%s'",$_REQUEST['idOpname'],30);
				if ($selAll->jumlah > 0 ){
					
					$db->exec("UPDATE opnamedetail SET idProduk='%s', idSize='%s', idOpname='%s',prevQty='%s',qty='%s' WHERE idOpnameDetail='%s' AND idSize = '%s'",$_REQUEST['idProduk'][$k],30,$_REQUEST['idOpname'],$_REQUEST['prevall'][$k],$_REQUEST['all'][$k],$_REQUEST['OpnameDetail']['all'][$k],30);
				} else {
					$db->exec("INSERT INTO opnamedetail(idProduk, idSize, idOpname, prevQty, Qty) VALUES ('%s', '%s', '%s', '%s', '%s')",$_REQUEST['idProduk'][$k],30,$_REQUEST['idOpname'],$_REQUEST['prevall'][$k],$_REQUEST['all'][$k]);
				}
				
				
			}
		}
		$message = 'Data Opname Diubah.';
		$disabledbutton = 'disabled';
		foreach ($db->table("SELECT a.idOpname,b.idOpnameDetail, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,b.qty
		FROM opname AS a
		LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' AND a.idOpname = '%s' GROUP BY b.idSize,b.idProduk ",  $bulBaru,$_REQUEST['outlet'],$_REQUEST['idOpname']) as $row){
			$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idOpnameDetail'] = $row->idOpnameDetail;
			$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
			$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] = $row->qty;
			$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
		}
	} else {
		$error[] = 'Gagal menambah data opname';
	}
}

//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$cariproduk = $db->table("SELECT a.namaProduk, a.idProduk
		FROM produk AS a
		WHERE a.namaProduk LIKE '%$key%'
		ORDER BY a.namaProduk");
	die(json_encode($cariproduk));
}

//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$cariproduk = $db->table("SELECT a.namaProduk, a.idProduk
		FROM produk AS a
		WHERE a.namaProduk LIKE '%$key%'
		ORDER BY a.namaProduk");
	die(json_encode($cariproduk));
}

//selectBoxToko
$selectToko = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Perubahan Stok Produk
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formOpname.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Opname</button>
			</a>
			
			<form class="col-md-6 pull-right" method="post">
				<div class="col-sm-5 form-group">
					<input type="hidden" name="mode" value="<?= ($_REQUEST['mode'] == 'insert') ? 'insert' : 'edit'?>"/>
					<select name="toko" class="form-control" disabled>
						<?php foreach($selectToko as $k=>$v) :?>
							<option value="<?=$v->idOutlet;?>" <?=($_REQUEST['toko'] == $v->idOutlet) ? 'selected' : '';?> ><?=$v->namaOutlet;?></option>
						<?php endforeach;?>
					</select>
				</div>
				<div class="col-sm-5 form-group">
					<div class="input-group date">
					<div class="input-group-addon">
						<i class="fa fa-calendar"></i>
					</div>
					<input name="tanggalOpname" type="text" class="form-control pull-right" value="<?=($_REQUEST['tanggalOpname']) ? Template::format($_REQUEST['tanggalOpname'],"date") : Template::format($_REQUEST['tanggalOpnameBaru'],"date");?>" id="datepicker">
					</div>
				</div>
				<button class="col-sm-2 btn btn-default" type="submit" value="Sort" name="submit">Sort</button>
			</form>
			<div class="row">
				<div class="col-lg-8 pull-left">
				</div>
				<div class="col-lg-4 pull-right">
					<form style="margin-bottom:10px !important;"action="produk.php" method="post">
						<div class="input-group input-group-sm">
							
							<input style="width:100% !important;" placeholder="Search Produk"name="produk" id="produk-autocomplete" type="text" class="form-control">
							<span class="input-group-btn">
								<button name="searchProduk" value ="cari" type="button" class="btn btn-info btn-flat">Cari</button>
							</span>
							<div data-id="produk-template" id="results">
								<div data-id="search-produk" class="item"><div data-id="produk-krik"></div></div>
							</div>
							<input type="hidden" name="idproduk" data-id="idproduk-krik"/>
						</div>
					</form>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="box">
						<div class="box-header">
							<h3 class="box-title">Data Produk</h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							
							<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
								<table width="100%" id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
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
										<form method="post">
											<input type="hidden" name="modes" value="<?= ($_REQUEST['mode'] == 'insert') ? 'insert' : 'edit'?>"/>
											<input type="hidden" name="outlet" value="<?=$_REQUEST['toko'];?>"/>
											<input type="hidden" name="tanggalOpnameBaru" value="<?=$bulanOpname;?>"/>
											<?php if ($_REQUEST['mode'] == 'edit') :?>
											<input type="hidden" name="idOpname" value="<?=$_REQUEST['idOpname'];?>"/>
											<?php endif; ?>
											<?php foreach ($data as $k=>$v) :?> 
												<?php foreach ($v as $kk=>$row) :?>
													<?php if ($row['tipeUkur']==1) :?>
														<tr role="row">
															<td>
																<div class="form-group">
																	
																	<input type="hidden" name="idProduk['<?=$row['idProduk'];?>']" value="<?=$row['idProduk'];?>"/>
																	<input type="text" class="form-control" readonly name="namaproduk[]" value="<?=$row['kodeProduk'];?>" />
																</div>
															</td>
															<td>
																<div class="form-group">
																	<input readonly name="jenisProduk['<?=$row['idProduk'];?>']"type="text" class="form-control" value="<?=$row['jenisProduk'];?>" >
																</div>
															</td>
															<td>
																<input type="hidden" name="tipeukur['<?=$row['idProduk'];?>']" value="<?=$row['tipeUkur'];?>"/>
																<?=$row['tipeUkur'];?>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[XXS]['<?=$row['idProduk'];?>']" value="<?=$row['XXS']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevXXS['<?=$row['idProduk'];?>']" value="<?= ($row['XXS']['XXS'] <= 0)? '0' : $row['XXS']['XXS'];?>"/>
																		<input style="width:60px;" name="XXS['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXS']['XXS'] <= 0)? '0' : $row['XXS']['XXS'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevXXS['<?=$row['idProduk'];?>']" value="<?= ($row['XXS'] <= 0)? '0' : $row['XXS'];?>"/>
																		<input style="width:60px;" name="XXS['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXS'] <= 0)? '0' : $row['XXS'];?>" >
																	<?php else :?>
																		<input type="hidden" name="prevXXS['<?=$row['idProduk'];?>']" value="<?= ($row['XXS'] <= 0)? '0' : $row['XXS'];?>"/>
																		<input style="width:60px;" name="XXS['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXS'] <= 0)? '0' : $row['XXS'];?>" >
																	<?php endif;?>
																	
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[XS]['<?=$row['idProduk'];?>']" value="<?=$row['XS']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevXS['<?=$row['idProduk'];?>']" value="<?= ($row['XS']['XS'] <= 0)? '0' : $row['XS']['XS'];?>"/>
																		<input style="width:60px;" name="XS['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XS']['XS'] <= 0)? '0' : $row['XS']['XS'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevXS['<?=$row['idProduk'];?>']" value="<?= ($row['XS'] <= 0)? '0' : $row['XS'];?>"/>
																		<input style="width:60px;" name="XS['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XS'] <= 0)? '0' : $row['XS'];?>" >
																	<?php else:?>
																		<input type="hidden" name="prevXS['<?=$row['idProduk'];?>']" value="<?= ($row['XS'] <= 0)? '0' : $row['XS'];?>"/>
																		<input style="width:60px;" name="XS['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XS'] <= 0)? '0' : $row['XS'];?>" >
																	<?php endif;?>
																	
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[S]['<?=$row['idProduk'];?>']" value="<?=$row['S']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevS['<?=$row['idProduk'];?>']" value="<?= ($row['S']['S'] <= 0)? '0' : $row['S']['S'];?>"/>
																		<input style="width:60px;" name="S['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['S']['S'] <= 0)? '0' : $row['S']['S'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevS['<?=$row['idProduk'];?>']" value="<?= ($row['S'] <= 0)? '0' : $row['S'];?>"/>
																		<input style="width:60px;" name="S['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['S'] <= 0)? '0' : $row['S'];?>" >
																	<?php else:?>
																		<input type="hidden" name="prevS['<?=$row['idProduk'];?>']" value="<?= ($row['S'] <= 0)? '0' : $row['S'];?>"/>
																		<input style="width:60px;" name="S['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['S'] <= 0)? '0' : $row['S'];?>" >
																	<?php endif;?>
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[M]['<?=$row['idProduk'];?>']" value="<?=$row['M']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevM['<?=$row['idProduk'];?>']" value="<?= ($row['M']['M'] <= 0)? '0' : $row['M']['M'];?>"/>
																		<input style="width:60px;" name="M['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['M']['M'] <= 0)? '0' : $row['M']['M'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevM['<?=$row['idProduk'];?>']" value="<?= ($row['M'] <= 0)? '0' : $row['M'];?>"/>
																		<input style="width:60px;" name="M['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['M'] <= 0)? '0' : $row['M'];?>" >
																	<?php else:?>
																		<input type="hidden" name="prevM['<?=$row['idProduk'];?>']" value="<?= ($row['M'] <= 0)? '0' : $row['M'];?>"/>
																		<input style="width:60px;" name="M['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['M'] <= 0)? '0' : $row['M'];?>" >
																	<?php endif;?>
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[L]['<?=$row['idProduk'];?>']" value="<?=$row['L']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevL['<?=$row['idProduk'];?>']" value="<?= ($row['L']['L'] <= 0)? '0' : $row['L']['L'];?>"/>
																		<input style="width:60px;" name="L['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['L']['L'] <= 0)? '0' : $row['L']['L'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevL['<?=$row['idProduk'];?>']" value="<?= ($row['L'] <= 0)? '0' : $row['L'];?>"/>
																		<input style="width:60px;" name="L['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['L'] <= 0)? '0' : $row['L'];?>" >
																	<?php else:?>
																		<input type="hidden" name="prevL['<?=$row['idProduk'];?>']" value="<?= ($row['L'] <= 0)? '0' : $row['L'];?>"/>
																		<input style="width:60px;" name="L['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['L'] <= 0)? '0' : $row['L'];?>" >
																	<?php endif;?>
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[XL]['<?=$row['idProduk'];?>']" value="<?=$row['XL']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevXL['<?=$row['idProduk'];?>']" value="<?= ($row['XL']['XL'] <= 0)? '0' : $row['XL']['XL'];?>"/>
																		<input style="width:60px;" name="XL['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XL']['XL'] <= 0)? '0' : $row['XL']['XL'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevXL['<?=$row['idProduk'];?>']" value="<?= ($row['XL'] <= 0)? '0' : $row['XL'];?>"/>
																		<input style="width:60px;" name="XL['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XL'] <= 0)? '0' : $row['XL'];?>" >
																	<?php else:?>
																		<input type="hidden" name="prevXL['<?=$row['idProduk'];?>']" value="<?= ($row['XL'] <= 0)? '0' : $row['XL'];?>"/>
																		<input style="width:60px;" name="XL['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XL'] <= 0)? '0' : $row['XL'];?>" >
																	<?php endif;?>
																</div>
															</td>
															<td>
																<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[XXL]['<?=$row['idProduk'];?>']" value="<?=$row['XXL']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevXXL['<?=$row['idProduk'];?>']" value="<?= ($row['XXL']['XXL'] <= 0)? '0' : $row['XXL']['XXL'];?>"/>
																		<input style="width:60px;" name="XXL['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXL']['XXL'] <= 0)? '0' : $row['XXL']['XXL'];?>" >
																<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevXXL['<?=$row['idProduk'];?>']" value="<?= ($row['XXL'] <= 0)? '0' : $row['XXL'];?>"/>
																		<input style="width:60px;" name="XXL['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXL'] <= 0)? '0' : $row['XXL'];?>" >
																<?php else:?>
																		<input type="hidden" name="prevXXL['<?=$row['idProduk'];?>']" value="<?= ($row['XXL'] <= 0)? '0' : $row['XXL'];?>"/>
																		<input style="width:60px;" name="XXL['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['XXL'] <= 0)? '0' : $row['XXL'];?>" >
																<?php endif;?>
															</td>
															<td></td>
														</tr>
													<?php elseif ($row['tipeUkur']==2) :?>
														<tr role="row">
															<td>
																<div class="form-group">
																	<input type="hidden" name="idProduk['<?=$row['idProduk'];?>']" value="<?=$row['idProduk'];?>"/>
																	<input type="text" class="form-control" readonly name="namaproduk[]" value="<?=$row['kodeProduk'];?>" />
																</div>
															</td>
															<td>
																<div class="form-group">
																	<input readonly style="width:60px;" name="idJenisProduk['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?=$row['jenisProduk'];?>">
																</div>
															</td>
															<td>
																<input type="hidden" name="tipeukur['<?=$row['idProduk'];?>']" value="<?=$row['tipeUkur'];?>"/>
																<?=$row['tipeUkur'];?>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[s38]['<?=$row['idProduk'];?>']" value="<?=$row['38']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevs38['<?=$row['idProduk'];?>']" value="<?= ($row['38']['38'] <= 0)? '0' : $row['38']['38'];?>"/>
																		<input style="width:60px;" name="s38['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['38']['38'] <= 0)? '0' : $row['38']['38'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevs38['<?=$row['idProduk'];?>']" value="<?= ($row['38'] <= 0)? '0' : $row['38'];?>"/>
																		<input style="width:60px;" name="s38['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['38'] <= 0)? '0' : $row['38'];?>" >
																	<?php else:?>
																		<input type="hidden" name="prevs38['<?=$row['idProduk'];?>']" value="<?= ($row['38'] <= 0)? '0' : $row['38'];?>"/>
																		<input style="width:60px;" name="s38['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['38'] <= 0)? '0' : $row['38'];?>" >	
																	<?php endif;?>
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[s39]['<?=$row['idProduk'];?>']" value="<?=$row['39']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevs39['<?=$row['idProduk'];?>']" value="<?= ($row['39']['39'] <= 0)? '0' : $row['39']['39'];?>"/>
																		<input style="width:60px;" name="s39['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['39']['39'] <= 0)? '0' : $row['39']['39'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevs39['<?=$row['idProduk'];?>']" value="<?= ($row['39'] <= 0)? '0' : $row['39'];?>"/>
																		<input style="width:60px;" name="s39['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['39'] <= 0)? '0' : $row['39'];?>" >
																	<?php else:?>
																		<input type="hidden" name="prevs39['<?=$row['idProduk'];?>']" value="<?= ($row['39'] <= 0)? '0' : $row['39'];?>"/>
																		<input style="width:60px;" name="s39['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['39'] <= 0)? '0' : $row['39'];?>" >
																	<?php endif;?>
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[s40]['<?=$row['idProduk'];?>']" value="<?=$row['40']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevs40['<?=$row['idProduk'];?>']" value="<?= ($row['40']['40'] <= 0)? '0' : $row['40']['40'];?>"/>
																		<input style="width:60px;" name="s40['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['40']['40'] <= 0)? '0' : $row['40']['40'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevs40['<?=$row['idProduk'];?>']" value="<?= ($row['40'] <= 0)? '0' : $row['40'];?>"/>
																		<input style="width:60px;" name="s40['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['40'] <= 0)? '0' : $row['40'];?>" />
																		<?php else:?>
																		<input type="hidden" name="prevs40['<?=$row['idProduk'];?>']" value="<?= ($row['40'] <= 0)? '0' : $row['40'];?>"/>
																		<input style="width:60px;" name="s40['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['40'] <= 0)? '0' : $row['40'];?>" />
																	<?php endif;?>
																	
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[s41]['<?=$row['idProduk'];?>']" value="<?=$row['41']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevs41['<?=$row['idProduk'];?>']" value="<?= ($row['41']['41'] <= 0)? '0' : $row['41']['41'];?>"/>
																		<input style="width:60px;" name="s41['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['41']['41'] <= 0)? '0' : $row['41']['41'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevs41['<?=$row['idProduk'];?>']" value="<?= ($row['41'] <= 0)? '0' : $row['41'];?>"/>
																		<input style="width:60px;" name="s41['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['41'] <= 0)? '0' : $row['41'];?>" />
																	<?php else:?>
																		<input type="hidden" name="prevs41['<?=$row['idProduk'];?>']" value="<?= ($row['41'] <= 0)? '0' : $row['41'];?>"/>
																		<input style="width:60px;" name="s41['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['41'] <= 0)? '0' : $row['41'];?>" />
																	<?php endif;?>
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[s42]['<?=$row['idProduk'];?>']" value="<?=$row['42']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevs42['<?=$row['idProduk'];?>']" value="<?= ($row['42']['42'] <= 0)? '0' : $row['42']['42'];?>"/>
																		<input style="width:60px;" name="s42['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['42']['42'] <= 0)? '0' : $row['42']['42'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevs42['<?=$row['idProduk'];?>']" value="<?= ($row['42'] <= 0)? '0' : $row['42'];?>"/>
																		<input style="width:60px;" name="s42['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['42'] <= 0)? '0' : $row['42'];?>" />
																	<?php else:?>
																		<input type="hidden" name="prevs42['<?=$row['idProduk'];?>']" value="<?= ($row['42'] <= 0)? '0' : $row['42'];?>"/>
																		<input style="width:60px;" name="s42['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['42'] <= 0)? '0' : $row['42'];?>" />
																	<?php endif;?>
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[s43]['<?=$row['idProduk'];?>']" value="<?=$row['43']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevs43['<?=$row['idProduk'];?>']" value="<?= ($row['43']['43'] <= 0)? '0' : $row['43']['43'];?>"/>
																		<input style="width:60px;" name="s43['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['43']['43'] <= 0)? '0' : $row['43']['43'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevs43['<?=$row['idProduk'];?>']" value="<?= ($row['43'] <= 0)? '0' : $row['43'];?>"/>
																		<input style="width:60px;" name="s43['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['43'] <= 0)? '0' : $row['43'];?>" >
																	<?php else:?>
																		<input type="hidden" name="prevs43['<?=$row['idProduk'];?>']" value="<?= ($row['43'] <= 0)? '0' : $row['43'];?>"/>
																		<input style="width:60px;" name="s43['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['43'] <= 0)? '0' : $row['43'];?>" >
																	<?php endif;?>
																	
																</div>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[s44]['<?=$row['idProduk'];?>']" value="<?=$row['44']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevs44['<?=$row['idProduk'];?>']" value="<?= ($row['44']['44'] <= 0)? '0' : $row['44']['44'];?>"/>
																		<input style="width:60px;" name="s44['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['44']['44'] <= 0)? '0' : $row['44']['44'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevs44['<?=$row['idProduk'];?>']" value="<?= ($row['44'] <= 0)? '0' : $row['44'];?>"/>
																		<input style="width:60px;" name="s44[<?=$row['idProduk'];?>]" type="text" class="form-control" value="<?= ($row['44'] <= 0)? '0' : $row['44'];?>" />
																	<?php else:?>
																		<input type="hidden" name="prevs44['<?=$row['idProduk'];?>']" value="<?= ($row['44'] <= 0)? '0' : $row['44'];?>"/>
																		<input style="width:60px;" name="s44[<?=$row['idProduk'];?>]" type="text" class="form-control" value="<?= ($row['44'] <= 0)? '0' : $row['44'];?>" />
																	<?php endif;?>
																	
																</div>
															</td>
															<td></td>
														</tr>
													<?php elseif ($row['tipeUkur']==3) :?>
														<tr role="row">
															<td>
																<div class="form-group">
																	<input type="hidden" name="idProduk['<?=$row['idProduk'];?>']" value="<?=$row['idProduk'];?>"/>
																	<input type="text" class="form-control" readonly name="namaproduk[<?=$row['idProduk'];?>]" value="<?=$row['kodeProduk'];?>" />
																</div>
															</td>
															<td>
																<div class="form-group">
																	<input readonly name="idJenisProduk['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?=$row['jenisProduk'];?>" /c>
																</div>
															</td>
															<td>
																<input type="hidden" name="tipeukur['<?=$row['idProduk'];?>']" value="<?=$row['tipeUkur'];?>"/>
																<?=$row['tipeUkur'];?>
															</td>
															<td>
																<div class="form-group">
																	<?php if ($_REQUEST['mode'] == 'edit'):?>
																		<input type="hidden" name="OpnameDetail[all]['<?=$row['idProduk'];?>']" value="<?=$row['All']['idOpnameDetail'];?>"/>
																		<input type="hidden" name="prevall['<?=$row['idProduk'];?>']" value="<?= ($row['All']['All'] <= 0)? '0' : $row['All']['All'];?>"/>
																		<input style="width:60px;" name="all['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['All']['All']<= 0)? '0' : $row['All']['All'];?>" >
																	<?php elseif ($_REQUEST['mode'] == 'insert'):?>
																		<input type="hidden" name="prevall['<?=$row['idProduk'];?>']" value="<?= ($row['All'] <= 0)? '0' : $row['All'];?>"/>
																		<input style="width:60px;" name="all['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['All'] <= 0)? '0' : $row['All'];?>" />
																	<?php else:?>
																		<input type="hidden" name="prevall['<?=$row['idProduk'];?>']" value="<?= ($row['All'] <= 0)? '0' : $row['All'];?>"/>
																		<input style="width:60px;" name="all['<?=$row['idProduk'];?>']" type="text" class="form-control" value="<?= ($row['All'] <= 0)? '0' : $row['All'];?>" />
																	<?php endif;?>
																	
																</div>
															</td>
						
														</tr>
													<?php endif?>
												<?php endforeach; ?>
											<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="box-footer">
								<a href="opname.php"><button type="button" class="btn btn-default">Back</button></a>
							
								<button type="submit" name="opname" value="Simpan" class="btn btn-info pull-right" <?=($disabledbutton == 'disabled') ? $disabledbutton : '';?>>Simpan</button>
							</form>
						</div>
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
	
	//Search Produk
	var MIN_LENGTH = 1;
	var idproduk = "";
	$(function() {
		
		var template_produk_parent = $('[data-id="search-produk"]').parent();
		var template_produk = $('[data-id="search-produk"]').detach();
		$("#produk-autocomplete").keyup(function() {
			var keyword = $("#produk-autocomplete").val();
			if (keyword.length >= MIN_LENGTH) {
				$.getJSON('produk.php', {cari:keyword}, function(d){
					$(template_produk_parent).show().html('');
					if (d.length != 0) {
						$.each(d, function(k,v){
							var row = $(template_produk).clone().appendTo(template_produk_parent);
							row.find('[data-id="produk-krik"]').html(v.namaProduk);
							row.find('[data-id="idproduk-krik"]').val(v.idProduk);
							kodewilayah = v.id;
						});
						
						$('.item').click(function() {
							var text = $(this).find('[data-id="produk-krik"]').html();
							$('#produk-autocomplete').val(text);
							$('[data-id="idproduk-krik"]').val(kodewilayah);
							$(template_produk_parent).hide();
						});
					} else {
						var row = $(template_produk).clone().appendTo(template_produk_parent);
						row.find('[data-id="produk-krik"]').html('Data Produk Tidak Ditemukan');
						$('.item').click(function() {
							$(template_produk_parent).hide();
						});
					}
			});
			}
		});
	});
	</script>
<?php Template::foot();?>