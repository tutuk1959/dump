<?php

session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "ekstraksiPeramalan.function.php";
include_once "kuda.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
kickManage($hakAkses[$_SESSION['hak']]);
$selectBoxJenis = $db->table("SELECT a.idJenis, a.jenisProduk FROM jenisproduk AS a ORDER BY a.idJenis");
$selectBoxOutlet = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
$selectBoxSize = $db->table("SELECT a.idSize, a.size FROM sizeproduk AS a ORDER BY a.idsize");

$ekstraksiPrefill = $db->row("SELECT a.tanggalPeramalan, a.idProduk, a.idSize, a.idOutlet, a.idJenis, a.tanggalTujuan
					FROM peramalan AS a
					WHERE a.idPeramalan = '%s'", $_REQUEST['idPeramalan']);

$tanggalDari = substr($ekstraksiPrefill->tanggalTujuan,0,4).'-'.substr($ekstraksiPrefill->tanggalTujuan,6,2).'-01';
$tanggalSampai = date('Y-m-t',strtotime($ekstraksiPrefill->tanggalTujuan));
$tanggalKemarinAwal = date('Y-m-01', strtotime("$ekstraksiPrefill->tanggalTujuan -1 MONTH"));

$tanggalKemarinAkhir = date('Y-m-d', strtotime("$tanggalSampai -1 MONTH"));

$selectPelatihan = $db->row("SELECT a.idPelatihan, a.idJenis, a.idSize, b.neuronFrom,b.neuronTo, b.weight,b.jenis
					FROM pelatihan AS a
					LEFT JOIN bobotpelatihan AS b ON a.idPelatihan = b.idPelatihan
					WHERE a.idJenis = '%s' AND a.idSize = '%s'",$ekstraksiPrefill->idJenis,$ekstraksiPrefill->idSize);
foreach ($db->table("SELECT a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7) bulan, Year(c.`tanggalOpname`) ye,  Month(c.`tanggalOpname`) mo, 
				SUM(a.`hargaAsli`* (b.`prevQty` - b.`qty`))/SUM(b.`prevQty` - b.`qty`) harga,
				SUM(b.`prevQty` - b.`qty`) terjual, SUM(b.`prevQty`) stokAwal
				FROM `produk` a
				LEFT JOIN `opnamedetail` b ON a.`idProduk`=b.`idProduk`
				LEFT JOIN `opname` c ON b.`idOpname`=c.`idOpname` AND c.`tanggalOpname` BETWEEN '%s' AND '%s'
				WHERE c.idOpname IS NOT NULL AND a.idJenisProduk = '%s' AND b.idSize = '%s'
				GROUP BY a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7)",$tanggalKemarinAwal, $tanggalKemarinAkhir,$ekstraksiPrefill->idJenis,$ekstraksiPrefill->idSize) as $row)
			$data[$row->idJenisProduk.".".($row->ye *12 + $row->mo)] = $row;
if ($_REQUEST['mode'] == 'insert' && $_REQUEST['simpan'] == 'Simpan'){
	// print_r($_REQUEST);
	foreach($_REQUEST['bulan'] as $k=>$v){
		//echo "(({$_REQUEST['bulan'][$k]}, {$_REQUEST['terjualLaluNorm'][$k]}, {$_REQUEST['hargaNorm'][$k]},
				//{$_REQUEST['stokAwalNorm'][$k]}, {$_REQUEST['terjualNorm'][$k]}))";
		_validateResult($_REQUEST['bulan'][$k], $_REQUEST['terjualLalu'][$k], $_REQUEST['harga'][$k],
				$_REQUEST['stokAwal'][$k], $error);
		if (! $error){
			$db->exec("INSERT INTO inputperamalan(idPeramalan,x1,x2,x3,idPelatihan) VALUES ('%s','%s','%s','%s','%s')",
					$_REQUEST['idPeramalan'], $_REQUEST['terjualLalu'][$k], $_REQUEST['harga'][$k], $_REQUEST['stokAwal'][$k],
					$selectPelatihan->idPelatihan);
			$message = "Data parameter pelatihan berhasil dimasukan";
		}
	}
	$ekstraksiPrefill = $db->row("SELECT a.tanggalPeramalan, a.idProduk, a.idSize, a.idOutlet, a.idJenis, a.tanggalTujuan
					FROM peramalan AS a
					WHERE a.idPeramalan = '%s'", $_REQUEST['idPeramalan']);
	$tanggalDari = substr($ekstraksiPrefill->tanggalTujuan,0,4).'-'.substr($ekstraksiPrefill->tanggalTujuan,6,2).'-01';
	$tanggalSampai = date('Y-m-t',strtotime($ekstraksiPrefill->tanggalTujuan));
	$tanggalKemarinAwal = date('Y-m-01', strtotime("$ekstraksiPrefill->tanggalTujuan -1 MONTH"));

	$tanggalKemarinAkhir = date('Y-m-d', strtotime("$tanggalSampai -1 MONTH"));

	foreach ($db->table("SELECT a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7) bulan, Year(c.`tanggalOpname`) ye,  Month(c.`tanggalOpname`) mo, 
				SUM(a.`hargaAsli`* (b.`prevQty` - b.`qty`))/SUM(b.`prevQty` - b.`qty`) harga,
				SUM(b.`prevQty` - b.`qty`) terjual, SUM(b.`prevQty`) stokAwal
				FROM `produk` a
				LEFT JOIN `opnamedetail` b ON a.`idProduk`=b.`idProduk`
				LEFT JOIN `opname` c ON b.`idOpname`=c.`idOpname` AND c.`tanggalOpname` BETWEEN '%s' AND '%s'
				WHERE c.idOpname IS NOT NULL AND a.idJenisProduk = '%s' AND b.idSize = '%s'
				GROUP BY a.`idJenisProduk`, LEFT(c.`tanggalOpname`, 7)",$tanggalKemarinAwal, $tanggalKemarinAkhir,$ekstraksiPrefill->idJenis,$ekstraksiPrefill->idSize) as $row)
			$data[$row->idJenisProduk.".".($row->ye *12 + $row->mo)] = $row;
	// print_r($data); echo "### $tanggalDari, $tanggalSampai, $tanggalKemarinAwal, $tanggalKemarinAkhir ###";
}

Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Peramalan Jaringan Syaraf Tiruan
			</h1>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="nav-tabs-custom">
						<ul class="nav nav-tabs">
							<li class="active"><a href="#peramalan" data-toggle="tab">Ekstraksi</a></li>
							<li><a  href="hasil-peramalan.php?idPelatihan=<?=($_REQUEST['idPelatihan']) ? $_REQUEST['idPelatihan'] : ''?>">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="peramalan">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Ekstraksi dari data Opname berdasarkan Jenis Produk</h3>
									</div>
									
										<div class="box-body">
											
											<div class="clearfix"></div>
											<form method="post" enctype="multipart/form-data">
												<input type="hidden" name="idPeramalan" value="<?=$_REQUEST['idPeramalan'];?>"/>
												<input type="hidden" class="form-control" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
												<input type="hidden" class="form-control" name="tanggalDari" value="<?=$tanggalDari;?>"/>
												<input type="hidden" class="form-control" name="tanggalSampai" value="<?=$tanggalSampai;?>"/>
												<table class="table table-bordered table-striped dataTable"  border="1">
													<thead>
														<tr>
															<th>Bulan</th>
															<th>Produk Terjual Bulan Lalu</th>
															<th>Harga Produk</th>
															<th>Stok pada Bulan Lalu</th>
															<th>Ramalan Produk Terjual</th>
														</tr>
														
													</thead>
													<tbody>
														<?php $normalized = normalized($data);?>
														<?php foreach((array)$normalized as $k=>$v):?>
															
															<tr>
																<td>
																	<input type="text" name="bulan[]" value="<?=substr($data[$k]->bulan,5,2).'-'.substr($data[$k]->bulan,0,4);?>" class="form-control"/></td>
																<td>
																	<input type="hidden" name="terjualLaluNorm[]" value="<?=htmlentities($v->terjual);?>"/>
																	<input type="text" name="terjualLalu[]" value="<?=($data[$k]->terjual > 0 || $data[$k]->terjual != NULL) ? htmlentities($data[$k]->terjual): 0;?>" class="form-control"/></td>
																<td>
																	<input type="hidden" name="hargaNorm[]" value="<?=htmlentities($v->harga);?>"/>
																	<input type="text" name="harga[]" value="<?=htmlentities($data[$k]->harga)?>" class="form-control"/></td>
																<td>
																	<input type="hidden" name="stokAwalNorm[]" value="<?=htmlentities($v->stokAwal);?>"/>
																	<input type="text" name="stokAwal[]" value="<?=htmlentities($data[$k]->stokAwal)?>" class="form-control"/></td>
																<td>
																	<input type="hidden" name="terjualNorm[]" value=""/>
																	<input type="text" name="terjual[]" value="?" class="form-control"/></td>
															</tr>
														<?php endforeach?>
													</tbody>
												</table>
												
												<div class="box-footer">
													<a href="parameter-pelatihan-mainpage.php" class="btn btn-info pull-left">Back</a>
													<?php if ($normalized):?>
														<div class="pull-right">
															<button style="margin-right:5px;" type="submit" value="Simpan" name="simpan"class="btn btn-info">Simpan</button>
															<a href="backprop-peramalan.php?idPeramalan=<?=$_REQUEST['idPeramalan'];?>&idPelatihan=<?=$selectPelatihan->idPelatihan;?>" target="_blank"><button type="button" value="Proses"class="btn btn-info">Proses</button></a>
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