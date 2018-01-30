<?php

session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "ekstraksiPelatihan.function.php";
include_once "kuda.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
kickManage($hakAkses[$_SESSION['hak']]);
$selectBoxJenis = $db->table("SELECT a.idJenis, a.jenisProduk FROM jenisproduk AS a ORDER BY a.idJenis");
$selectBoxOutlet = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
$selectBoxSize = $db->table("SELECT a.idSize, a.size FROM sizeproduk AS a ORDER BY a.idsize");


if ($_REQUEST['mode'] == 'insert' && $_REQUEST['submit'] == 'Ekstrak') {
	
	_validation($_REQUEST['tanggalDari'], $_REQUEST['tanggalSampai'], $error);
	if (!$error){
		$tanggalDari = substr($_REQUEST['tanggalDari'],6,4).'-'.substr($_REQUEST['tanggalDari'],0,2).'-'.substr($_REQUEST['tanggalDari'],3,2);
		$tanggalSampai = substr($_REQUEST['tanggalSampai'],6,4).'-'.substr($_REQUEST['tanggalSampai'],0,2).'-'.substr($_REQUEST['tanggalSampai'],3,2);
		$tanggalKemarinAwal = date('Y-m-01', strtotime("$tanggalDari -1 MONTH"));
		$tanggalKemarinAkhir = date('Y-m-d', strtotime("$tanggalSampai -1 MONTH"));
		$idJenisProduk = $db->row("SELECT a.idJenis,a.idSize FROM pelatihan AS a WHERE a.idPelatihan = '%s'",$_REQUEST['idPel']);
		foreach ($db->table("SELECT a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7) bulan, Year(c.`tanggalOpname`) ye,  Month(c.`tanggalOpname`) mo, 
				SUM(a.`hargaAsli`* (b.`prevQty` - b.`qty`))/SUM(b.`prevQty` - b.`qty`) harga,
				SUM(b.`prevQty` - b.`qty`) terjual, SUM(b.`prevQty`) stokAwal
				FROM `produk` a
				LEFT JOIN `opnamedetail` b ON a.`idProduk`=b.`idProduk`
				LEFT JOIN `opname` c ON b.`idOpname`=c.`idOpname` AND c.`tanggalOpname` BETWEEN '%s' AND '%s'
				WHERE c.idOpname IS NOT NULL AND a.idJenisProduk = '%s' AND b.idSize = '%s'
				GROUP BY a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7)",$tanggalDari, $tanggalSampai,$idJenisProduk->idJenis,$idJenisProduk->idSize) as $row)
			$data[$row->idJenisProduk.".".($row->ye *12 + $row->mo)] = $row;
		// print_r($data); echo "### $tanggalDari, $tanggalSampai, $tanggalKemarinAwal, $tanggalKemarinAkhir ###";
		
		foreach ($db->table("SELECT a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7) bulan, Year(c.`tanggalOpname`) ye,  Month(c.`tanggalOpname`) mo, 
				SUM(a.`hargaAsli`* (b.`prevQty` - b.`qty`))/SUM(b.`prevQty` - b.`qty`) harga,
				SUM(b.`prevQty` - b.`qty`) terjuallalu, SUM(b.`prevQty`) stokAwal
				FROM `produk` a
				LEFT JOIN `opnamedetail` b ON a.`idProduk`=b.`idProduk`
				LEFT JOIN `opname` c ON b.`idOpname`=c.`idOpname` AND c.`tanggalOpname` BETWEEN '%s' AND '%s'
				WHERE c.idOpname IS NOT NULL AND a.idJenisProduk = '%s' AND b.idSize = '%s'
				GROUP BY a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7)",$tanggalKemarinAwal,$tanggalKemarinAkhir,$idJenisProduk->idJenis,$idJenisProduk->idSize) as $row)
			if (isset($data[$row->idJenisProduk.".".($row->ye *12 + $row->mo + 1)])){
				$data[$row->idJenisProduk.".".($row->ye *12 + $row->mo + 1)]->terjualLalu = $row->terjuallalu;
			}
		//$data = array(
		// ?idJenisProduct => object(
		//   idJenisProduct => 
		//   bulan => ?
		//   terjual => ?
		//   harga => ?
		//   stokAwal => ?
		//   terjualLalu => ?
		// ),
		//)
		
	}
}

if ($_REQUEST['mode'] == 'insert' && $_REQUEST['simpan'] == 'Simpan'){
	$db->exec("INSERT INTO pengujian(idPelatihan) VALUES ('%s')",$_REQUEST['idPelatihan']);
	$idPengujian = $db->insertID();
	foreach($_REQUEST['bulan'] as $k=>$v){
		//echo "(({$_REQUEST['bulan'][$k]}, {$_REQUEST['terjualLaluNorm'][$k]}, {$_REQUEST['hargaNorm'][$k]},
				//{$_REQUEST['stokAwalNorm'][$k]}, {$_REQUEST['terjualNorm'][$k]}))";
		_validateResult($_REQUEST['bulan'][$k], $_REQUEST['terjualLaluNorm'][$k], $_REQUEST['hargaNorm'][$k],
		$_REQUEST['stokAwalNorm'][$k], $_REQUEST['terjualNorm'][$k], $error);
		if (! $error){
			$db->exec("INSERT INTO inputpengujian(idPelatihan,idPengujian, x1,x2,x3,y,x1n,x2n,x3n,yn) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
				$_REQUEST['idPelatihan'],$idPengujian,$_REQUEST['terjualLalu'][$k], $_REQUEST['harga'][$k], $_REQUEST['stokAwal'][$k],
				$_REQUEST['terjual'][$k], $_REQUEST['terjualLaluNorm'][$k], $_REQUEST['hargaNorm'][$k],
				$_REQUEST['stokAwalNorm'][$k], $_REQUEST['terjualNorm'][$k]);
			$message = "Data parameter pengujian berhasil dimasukan";
		}
	}
	$tanggalDari = $_REQUEST['tanggalDari'];
	$tanggalSampai = $_REQUEST['tanggalSampai'];
	$tanggalKemarinAwal = date('Y-m-01', strtotime("$tanggalDari -1 MONTH"));
	$tanggalKemarinAkhir = date('Y-m-d', strtotime("$tanggalSampai -1 MONTH"));
	$idJenisProduk = $db->row("SELECT a.idJenis,a.idSize FROM pelatihan AS a WHERE a.idPelatihan = '%s'",$_REQUEST['idPelatihan']);
	foreach ($db->table("SELECT a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7) bulan, Year(c.`tanggalOpname`) ye,  Month(c.`tanggalOpname`) mo, 
				SUM(a.`hargaAsli`* (b.`prevQty` - b.`qty`))/SUM(b.`prevQty` - b.`qty`) harga,
				SUM(b.`prevQty` - b.`qty`) terjual, SUM(b.`prevQty`) stokAwal
				FROM `produk` a
				LEFT JOIN `opnamedetail` b ON a.`idProduk`=b.`idProduk`
				LEFT JOIN `opname` c ON b.`idOpname`=c.`idOpname` AND c.`tanggalOpname` BETWEEN '%s' AND '%s'
				WHERE c.idOpname IS NOT NULL AND a.idJenisProduk = '%s' AND b.idSize = '%s'
				GROUP BY a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7)",$tanggalDari, $tanggalSampai,$idJenisProduk->idJenis,$idJenisProduk->idSize) as $row)
			$data[$row->idJenisProduk.".".($row->ye *12 + $row->mo)] = $row;
	// print_r($data); echo "### $tanggalDari, $tanggalSampai, $tanggalKemarinAwal, $tanggalKemarinAkhir ###";
	
	foreach ($db->table("SELECT a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7) bulan, Year(c.`tanggalOpname`) ye,  Month(c.`tanggalOpname`) mo, 
			SUM(a.`hargaAsli`* (b.`prevQty` - b.`qty`))/SUM(b.`prevQty` - b.`qty`) harga,
			SUM(b.`prevQty` - b.`qty`) terjuallalu, SUM(b.`prevQty`) stokAwal
			FROM `produk` a
			LEFT JOIN `opnamedetail` b ON a.`idProduk`=b.`idProduk`
			LEFT JOIN `opname` c ON b.`idOpname`=c.`idOpname` AND c.`tanggalOpname` BETWEEN '%s' AND '%s'
			WHERE c.idOpname IS NOT NULL AND a.idJenisProduk = '%s' AND b.idSize = '%s'
			GROUP BY a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7)",$tanggalKemarinAwal,$tanggalKemarinAkhir,$idJenisProduk->idJenis,$idJenisProduk->idSize) as $row)
	if (isset($data[$row->idJenisProduk.".".($row->ye *12 + $row->mo + 1)])){
		$data[$row->idJenisProduk.".".($row->ye *12 + $row->mo + 1)]->terjualLalu = $row->terjuallalu;
	}
}
if ($_REQUEST['mode'] == 'pembobotan' && isset($_REQUEST['idPelatihan'])) {
	$pembobotanPrefill = $db->row("SELECT a.tanggalPelatihan, a.MSETotal, a.hiddenNeuron, a.idProduk, a.idSize, a.idOutlet, a.idJenis, a.learningRate, b.neuronFrom, b.neuronTo, b.weight
	FROM pelatihan AS a
	LEFT JOIN bobotpelatihan AS b ON a.idPelatihan = b.idPelatihan
	WHERE a.idPelatihan = '%s'",$_REQUEST['idPelatihan']);
}
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Pelatihan Jaringan Syaraf Tiruan
			</h1>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="nav-tabs-custom">
						<ul class="nav nav-tabs">
							<li ><a href="parameter-pengujian.php?jenisProduk=<?=$_REQUEST['idJenis'];?>&size=<?=$_REQUEST['idSize'];?>&outlet=<?=$_REQUEST['idOutlet']?>">Parameter</a></li>
							<li class="active"><a href="#ekstraksi-pelatihan" data-toggle="tab">Ekstraksi</a></li>
							<li><a  href="hasil-pengujian.php?idPelatihan=<?=($_REQUEST['idPelatihan']) ? $_REQUEST['idPelatihan'] : ''?>&idPengujian=<?=$idPengujian?>">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="ekstraksi-pelatihan">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Ekstraksi dari data Opname berdasarkan Jenis Produk</h3>
									</div>
									
										<div class="box-body">
											<section>
												<form method="post" class="form-horizontal" enctype="multipart/form-data">
													<div class="col-lg-4 form-group">
														<div class="input-group date">
														<div class="input-group-addon">
															<i class="fa fa-calendar"></i>
														</div>
														<input type="hidden" class="form-control" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
														<input type="hidden" name="idPel" value="<?=$_REQUEST['idPelatihan'];?>"/>
														<input name="tanggalDari" type="text" class="form-control pull-right" value="" id="tanggalDari">
														</div>
													</div>
													<p style="text-align:center;"class="col-lg-2">s/d</p>
													<div class="col-lg-4 form-group">
														<div class="input-group date">
														<div class="input-group-addon">
															<i class="fa fa-calendar"></i>
														</div>
														<input name="tanggalSampai" type="text" class="form-control pull-right" value="" id="tanggalSampai">
														</div>
													</div>
													<button style="float:left;margin:0px 0px 0px 10px !important;"class="btn btn-default" type="submit" value="Ekstrak" name="submit">Ekstrak</button>
												</form>
											</section>
											<div class="clearfix"></div>
											<form method="post" enctype="multipart/form-data">
												<input type="hidden" class="form-control" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
												<input type="hidden" class="form-control" name="tanggalDari" value="<?=$tanggalDari;?>"/>
												<input type="hidden" class="form-control" name="tanggalSampai" value="<?=$tanggalSampai;?>"/>
												<table class="table table-bordered table-striped dataTable"  border="1">
													<thead>
														<tr>
															<th>Bulan</th>
															<th>Produk Terjual pada T-1</th>
															<th>Harga Produk</th>
															<th>Stok pada T-1</th>
															<th>Produk Terjual</th>
														</tr>
														
													</thead>
													<tbody>
														<?php $normalized = normalized($data);?>
														<?php foreach((array)$normalized as $k=>$v):?>
															
															<tr>
																
																<td>
																	
																	<input type="text" name="bulan[]" value="<?=substr($data[$k]->bulan,5,2).'-'.substr($data[$k]->bulan,0,4);?>" class="form-control"/></td>
																<td>
																	<input type="hidden" name="terjualLaluNorm[]" value="<?=htmlentities($v->terjualLalu);?>"/>
																	<input type="text" name="terjualLalu[]" value="<?=($data[$k]->terjualLalu > 0 || $data[$k]->terjualLalu != NULL) ? htmlentities($data[$k]->terjualLalu): 0;?>" class="form-control"/></td>
																<td>
																	<input type="hidden" name="hargaNorm[]" value="<?=htmlentities($v->harga);?>"/>
																	<input type="text" name="harga[]" value="<?=htmlentities($data[$k]->harga)?>" class="form-control"/></td>
																<td>
																	<input type="hidden" name="stokAwalNorm[]" value="<?=htmlentities($v->stokAwal);?>"/>
																	<input type="text" name="stokAwal[]" value="<?=htmlentities($data[$k]->stokAwal)?>" class="form-control"/></td>
																<td>
																	<input type="hidden" name="terjualNorm[]" value="<?=htmlentities($v->terjual);?>"/>
																	<input type="text" name="terjual[]" value="<?=htmlentities($data[$k]->terjual)?>" class="form-control"/></td>
															</tr>
														<?php endforeach?>
													</tbody>
												</table>
												
												<div class="box-footer">
													<a href="parameter-pengujian-mainpage.php" class="btn btn-info pull-left">Back</a>
													<?php if ($normalized):?>
														<div class="pull-right">
															<button style="margin-right:5px;" type="submit" value="Simpan" name="simpan"class="btn btn-info">Simpan</button>
															<a href="backprop-pengujian.php?idPelatihan=<?=$_REQUEST['idPelatihan'];?>&idPengujian=<?=$idPengujian;?>" target="_blank"><button type="button" value="Proses"class="btn btn-info">Proses</button></a>
														</div>
														
													<?php endif;?>
												</div>
											</form>
										</div>
								</div>
							</div>
						</div> 
					</div>
				</div>
			</div>
		</section>
	</div>
<script type="text/javascript">
	//Date picker
	$('#tanggalDari').datepicker({
		autoclose: true,
		dateFormat : 'yy-mm-dd'
	});
	$('#tanggalSampai').datepicker({
		autoclose: true,
		dateFormat : 'yy-mm-dd'
	});
</script>
<?php Template::foot();?>