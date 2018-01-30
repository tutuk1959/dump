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
$selectBoxProduk = $db->table("SELECT a.idProduk, a.namaProduk FROM produk AS a ORDER BY a.idJenisProduk,a.idProduk ASC");
$selectBoxOutlet = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
$selectBoxSize = $db->table("SELECT a.idSize, a.size FROM sizeproduk AS a ORDER BY a.idsize");

if ($_REQUEST['mode'] == 'insert') {
	$tanggalPelatihan = date('Y-m-d',strtotime("now"));
	$mseTotal = 0.00000001;
	$hiddenNeuron = 7;
	$learningrate = 0.2;
	_validationProduk($_REQUEST['produk'],$_REQUEST['size'],$_REQUEST['outlet'],$error);
	if (!$error){
		$db->exec("INSERT INTO pelatihan(tanggalPelatihan,MSETotal, hiddenNeuron, idProduk, idSize, idOutlet, idJenis, learningRate) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s')",$tanggalPelatihan, $mseTotal, $hiddenNeuron,$_REQUEST['produk'],$_REQUEST['size'],$_REQUEST['outlet'],0, $learningrate);
		$idMasterpelatihan = $db->insertID();
		$pelatihanPrefill = $db->row("SELECT a.tanggalPelatihan, a.MSETotal, a.hiddenNeuron, a.idProduk, a.idSize, a.idOutlet, a.idJenis, a.learningRate
	FROM pelatihan AS a
	WHERE idPelatihan = '%s'",$idMasterpelatihan);
		$message = "Berhasil Memasukan Data Pelatihan Produk";
	} else {
		$error[] = "Gagal memasukan data peramalan";
	}
	
}
if ($_REQUEST['mode'] == 'edit' && isset($_REQUEST['idPelatihan'])) {
	$pelatihanPrefill = $db->row("SELECT a.tanggalPelatihan, a.MSETotal, a.hiddenNeuron, a.idProduk, a.idSize, a.idOutlet, a.learningRate
	FROM pelatihan 
	WHERE idPelatihan = '%s'",$_REQUEST['idPelatihan']);
}
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
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
							 <li class="active"><a href="#parameter-pelatihan" data-toggle="tab">Parameter</a></li>
							 <li><a href="ekstraksi-pelatihan-produk.php?idPelatihan=<?=($idMasterpelatihan) ? $idMasterpelatihan : $_REQUEST['idPelatihan']?>">Ekstraksi</a></li>
							 <li><a href="">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="parameter-pelatihan">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Pelatihan JST dari Produk</h3>
									</div>
									<form method="post" class="form-horizontal" enctype="multipart/form-data">
										<div class="box-body">
											<div class="form-group">
												<label  name="labelJenisProduk"class="col-sm-2 control-label">Produk</label>
												<div class="col-sm-10">
													<input type="hidden" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
													<select name="produk"class="form-control">
														<?php foreach ($selectBoxProduk as $k=>$v):?>
															<option value="<?=$v->idProduk; ?>" <?=(($_REQUEST['mode'] == 'edit') && (isset($pelatihanPrefill->idProduk)) && ($pelatihanPrefill->idProduk == $v->idProduk)? 'selected' : '');?> ><?=$v->namaProduk; ?></option>
														<?php endforeach ;?>
													</select>
												</div>
											</div>
											<div class="form-group">
												<label  name="labelJenisProduk"class="col-sm-2 control-label">Outlet</label>
												<div class="col-sm-10">
													<select name="outlet"class="form-control">
														<?php foreach ($selectBoxOutlet as $k=>$v):?>
															<option value="<?=$v->idOutlet; ?>" <?=(($_REQUEST['mode'] == 'edit') && (isset($prefillPelatihan->idOutlet)) && ($prefillPelatihan->idOutlet == $v->idOutlet)? 'selected' : '');?> ><?=$v->namaOutlet; ?></option>
														<?php endforeach ;?>
													</select>
												</div>
											</div>
											<div class="form-group">
												<label  name="labelJenisProduk"class="col-sm-2 control-label">Size</label>
												<div class="col-sm-10">
													<select name="size" class="form-control">
														<?php foreach ($selectBoxSize as $k=>$v):?>
															<option value="<?=$v->idSize; ?>" <?=(($_REQUEST['mode'] == 'edit') && (isset($prefillPelatihan->idSize)) && ($prefillPelatihan->idSize == $v->idSize)? 'selected' : '');?> ><?=$v->size; ?></option>
														<?php endforeach ;?>
													</select>
												</div>
											</div>
											<div class="box-footer">
												<a href="parameter-pelatihan-mainpage.php" class="btn btn-info pull-left">Back</a>
												<button type="submit" value="Simpan" name="submit"class="btn btn-info pull-right">Simpan</button>
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

<?php Template::foot();?>