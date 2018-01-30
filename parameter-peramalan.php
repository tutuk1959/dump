<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "pelatihan.jenisproduk.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
kickManage($hakAkses[$_SESSION['hak']]);
$selectBoxJenis = $db->table("SELECT a.idJenis, a.jenisProduk FROM jenisproduk AS a ORDER BY a.idJenis");
$selectBoxOutlet = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
$selectBoxSize = $db->table("SELECT a.idSize, a.size FROM sizeproduk AS a ORDER BY a.idsize");

if ($_REQUEST['mode'] == 'insert') {
	$tanggalPeramalan = date('Y-m-d',strtotime("now"));
	_validation($_REQUEST['jenisProduk'],$_REQUEST['size'],$_REQUEST['outlet'],$error);
	if (!$error){
		$tanggalRamal = substr($_REQUEST['tanggalRamal'],6,4).'-'.substr($_REQUEST['tanggalRamal'],0,2).'-'.substr($_REQUEST['tanggalRamal'],3,2);
		$db->exec("INSERT INTO peramalan(tanggalPeramalan,idProduk, idSize, idOutlet, idJenis,tanggalTujuan) VALUES ('%s','%s','%s','%s','%s','%s')",$tanggalPeramalan,0,$_REQUEST['size'],$_REQUEST['outlet'],$_REQUEST['jenisProduk'],$tanggalRamal);
		$idMasterperamalan = $db->insertID();
		$pelatihanPrefill = $db->row("SELECT a.tanggalPeramalan,idProduk, idSize, idOutlet, idJenis
	FROM peramalan  AS a
	WHERE idPeramalan = '%s'",$idMasterperamalan);
		$message = "Berhasil Memasukan Data Peramalan Jenis Produk";
	} else {
		$error[] = "Gagal memasukan data peramalan";
	}
	
}
if ($_REQUEST['mode'] == 'edit' ||  isset($_REQUEST['idPeramalan'])) {
	$pelatihanPrefill = $db->row("SELECT a.tanggalPeramalan,idProduk, idSize, idOutlet, idJenis
	FROM peramalan  AS a
	WHERE idPeramalan = '%s'",$_REQUEST['idPeramalan']);
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
						<!--------
						<ul class="nav nav-tabs">
							 <li class="active"><a href="#parameter-pelatihan" data-toggle="tab">Parameter</a></li>
							 <li><a href="ekstraksi-pelatihan.php?idPelatihan=<?=($idMasterpelatihan) ? $idMasterpelatihan : $_REQUEST['idPelatihan']?>">Ekstraksi</a></li>
							 <li><a href="">Hasil</a></li>
						</ul>
						------->
						<div class="tab-content">
							<div class="active tab-pane" id="parameter-pelatihan">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Peramalan JST dari Jenis Produk</h3>
									</div>
									<form method="post" class="form-horizontal" enctype="multipart/form-data">
										<div class="box-body">
											<div class="form-group">
												<label  name="labelJenisProduk"class="col-sm-2 control-label">Jenis Produk</label>
												<div class="col-sm-10">
													<input type="hidden" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
													<select name="jenisProduk"class="form-control" <?=($_REQUEST['idPelatihan']) ? 'disabled' : ''?>>
														<?php foreach ($selectBoxJenis as $k=>$v):?>
															<option value="<?=$v->idJenis; ?>" <?=(($_REQUEST['mode'] == 'edit') && (isset($pelatihanPrefill->idJenis)) && ($pelatihanPrefill->idJenis == $v->idJenis)? 'selected' : '');?> ><?=$v->jenisProduk; ?></option>
														<?php endforeach ;?>
													</select>
												</div>
											</div>
											<div class="form-group">
												<label  name="labelJenisProduk"class="col-sm-2 control-label">Outlet</label>
												<div class="col-sm-10">
													<select name="outlet"class="form-control" <?=($_REQUEST['idPelatihan']) ? 'disabled' : ''?>>
														<?php foreach ($selectBoxOutlet as $k=>$v):?>
															<option value="<?=$v->idOutlet; ?>" <?=(($_REQUEST['mode'] == 'edit') && (isset($pelatihanPrefill->idOutlet)) && ($pelatihanPrefill->idOutlet == $v->idOutlet)? 'selected' : '');?> ><?=$v->namaOutlet; ?></option>
														<?php endforeach ;?>
													</select>
												</div>
											</div>
											<div class="form-group">
												<label  name="labelJenisProduk"class="col-sm-2 control-label">Size</label>
												<div class="col-sm-10">
													<select name="size" class="form-control" <?=($_REQUEST['idPelatihan']) ? 'disabled' : ''?>>
														<?php foreach ($selectBoxSize as $k=>$v):?>
															<option value="<?=$v->idSize; ?>" <?=(($_REQUEST['mode'] == 'edit') && (isset($pelatihanPrefill->idSize)) && ($pelatihanPrefill->idSize == $v->idSize)? 'selected' : '');?> ><?=$v->size; ?></option>
														<?php endforeach ;?>
													</select>
												</div>
											</div>
											<div class="form-group">
												<label  name="labelTanggal"class="col-sm-2 control-label">Tanggal Peramalan</label>
												<div class="col-sm-10">
													<div class="input-group date">
														<div class="input-group-addon">
															<i class="fa fa-calendar"></i>
														</div>
														<input name="tanggalRamal" type="text" class="form-control pull-right" value="" id="tanggalRamal">
													</div>
												</div>
											</div>
											<div class="box-footer">
												<a href="parameter-peramalan-mainpage.php" class="btn btn-info pull-left">Back</a>
												<button style="float:left;"type="submit" value="Simpan" name="submit"class="btn btn-info pull-right">Simpan</button>
												<a style="margin-right:5px;"href="peramalan.php?idPeramalan=<?=($_REQUEST['idPeramalan'] ? $_REQUEST['idPeramalan']: $idMasterperamalan);?>" class="pull-right">
													<button type="button" <?=(!$idMasterperamalan ? 'disabled' : '')?> class="btn btn-info">Proses</button>
												</a>
											</div>
										</div>
									</form>
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
	$('#tanggalRamal').datepicker({
		autoclose: true,
		dateFormat : 'yy-mm-dd'
	});
	$('#tanggalSampai').datepicker({
		autoclose: true,
		dateFormat : 'yy-mm-dd'
	});
</script>
<?php Template::foot();?>