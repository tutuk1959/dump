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
$selectBoxProduk = $db->table("SELECT a.idProduk, a.namaProduk FROM produk AS a ORDER BY a.idProduk");
$selectBoxOutlet = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
$selectBoxSize = $db->table("SELECT a.idSize, a.size FROM sizeproduk AS a ORDER BY a.idsize");
$pelatihanPrefill = $db->table("SELECT a.idPelatihan, a.tanggalPelatihan,a.hiddenNeuron, b.namaProduk, c.size, a.idProduk, a.idSize, a.idOutlet,
(SELECT epoch FROM msepelatihan WHERE idPelatihan = a.idPelatihan ORDER BY idMsePelatihan DESC LIMIT 1) epoch,
(SELECT MSE FROM msepelatihan WHERE idPelatihan = a.idPelatihan ORDER BY idMsePelatihan DESC LIMIT 1) mse
FROM pelatihan AS a
LEFT JOIN produk AS b ON a.idProduk = b.idProduk
LEFT JOIN sizeproduk AS c ON a.idSize = c.`idSize`
WHERE a.idProduk = '%s' AND a.idSize = '%s' AND a.idOutlet= '%s'",$_REQUEST['produk'],$_REQUEST['size'],$_REQUEST['outlet']);

if ($_REQUEST['mode'] == 'insert') {
	$tanggalPelatihan = date('Y-m-d',strtotime("now"));
	$mseTotal = 0.00000001;
	$hiddenNeuron = 7;
	$learningrate = 0.2;
	_validation($_REQUEST['jenisProduk'],$_REQUEST['size'],$_REQUEST['outlet'],$error);
	if (!$error){
		$db->exec("INSERT INTO pelatihan(tanggalPelatihan,MSETotal, hiddenNeuron, idProduk, idSize, idOutlet, idJenis, learningRate) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s')",$tanggalPelatihan, $mseTotal, $hiddenNeuron,0,$_REQUEST['size'],$_REQUEST['outlet'],$_REQUEST['jenisProduk'], $learningrate);
		$idMasterpelatihan = $db->insertID();
		$pelatihanPrefill = $db->row("SELECT a.tanggalPelatihan, a.MSETotal, a.hiddenNeuron, a.idProduk, a.idSize, a.idOutlet, a.idJenis, a.learningRate
	FROM pelatihan  AS a
	WHERE idPelatihan = '%s'",$idMasterpelatihan);
		$message = "Berhasil Memasukan Data Pelatihan Jenis Produk";
	} else {
		$error[] = "Gagal memasukan data peramalan";
	}
	
}
if ($_REQUEST['mode'] == 'edit' ||  isset($_REQUEST['idPelatihan'])) {
	$pelatihanPrefill = $db->row("SELECT a.tanggalPelatihan, a.MSETotal, a.hiddenNeuron, a.idProduk, a.idSize, a.idOutlet, a.idJenis, a.learningRate
	FROM pelatihan AS a
	WHERE idPelatihan = '%s'",$_REQUEST['idPelatihan']);
}
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Pengujian Jaringan Syaraf Tiruan
			</h1>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="nav-tabs-custom">
						<ul class="nav nav-tabs">
							 <li class="active"><a href="#parameter-pengujian" data-toggle="tab">Pembobotan</a></li>
							 <li><a href="#">Ekstraksi</a></li>
							 <li><a href="">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="parameter-pengujian">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Pengujian JST dari Produk</h3>
									</div>
									<div class="box-body">
											
											<label  name="labelJenisProduk"class="col-lg-1 control-label">Produk</label>
											<div class="col-lg-3">
												<input type="hidden" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
												<select name="produk" class="form-control" disabled>
													<?php foreach ($selectBoxProduk as $k=>$v):?>
														<option value="<?=$v->idJenis; ?>" <?=(isset($_REQUEST['produk']) && ($_REQUEST['produk'] == $v->idProduk)? 'selected' : '');?> ><?=$v->namaProduk; ?></option>
													<?php endforeach ;?>
												</select>
											</div>
											
											
											<label  name="labelJenisProduk"class="col-lg-1 control-label">Outlet</label>
											<div class="col-lg-3">
												<select name="outlet"class="form-control" disabled>
													<?php foreach ($selectBoxOutlet as $k=>$v):?>
														<option value="<?=$v->idOutlet; ?>" <?=(isset($_REQUEST['outlet']) && ($_REQUEST['outlet'] == $v->idOutlet)? 'selected' : '');?> ><?=$v->namaOutlet; ?></option>
													<?php endforeach ;?>
												</select>
											</div>
											
											
											<label  name="labelJenisProduk"class="col-lg-1 control-label">Size</label>
											<div class="col-lg-3">
												<select name="size" class="form-control" disabled>
													<?php foreach ($selectBoxSize as $k=>$v):?>
														<option value="<?=$v->idSize; ?>" <?=(isset($_REQUEST['size']) && ($_REQUEST['size'] == $v->idSize)? 'selected' : '');?>><?=$v->size; ?></option>
													<?php endforeach ;?>
												</select>
											</div>
											
										<div style="margin-bottom:10px !important;"class="clearfix"></div>
										<table class="table table-bordered table-striped dataTable"  border="1">
													<thead>
														<tr>
															<th>Tanggal Pelatihan</th>
															<th>Jumlah Hidden Neuron</th>
															<th>Epoch</th>
															<th>Produk</th>
															<th>Size</th>
															<th>Total MSE</th>
															<th>Action</th>
														</tr>
														
													</thead>
													<tbody>
														<?php foreach($pelatihanPrefill as $k=>$v):?>
															
															<tr>
																<td>
																	<?=Template::format($v->tanggalPelatihan,"date");?>
																</td>
																	
																<td>
																	<?=$v->hiddenNeuron;?>
																</td>
																
																<td>
																	<?=$v->epoch;?>
																</td>
																<td>
																	<?=$v->namaProduk;?>
																</td>
																<td>
																	<?=$v->size;?>
																</td>
																<td>
																	<?=$v->mse;?>
																</td>
																<td>
																	<a target="_blank" class="btn btn-default" href="backprop-pengujian-pelatihan-produk.php?idPelatihan=<?=$v->idPelatihan;?>">Ulangi Pelatihan</a>
																	<a class="btn btn-default" href="ekstraksi-pengujian-produk.php?idProduk=<?=$v->idProduk;?>&idSize=<?=$v->idSize;?>&idOutlet=<?=$v->idOutlet?>&idPelatihan=<?=$v->idPelatihan;?>">Proses</a>
																</td>
															</tr>
														<?php endforeach?>
													</tbody>
												</table>
										<div class="box-footer">
											<a href="parameter-pengujian-mainpage.php" class="btn btn-info pull-left">Back</a>
										</div>
								</div>
							</div>
						</div> 
					</div>
				</div>
			</div>
		</section>
	</div>

<?php Template::foot();?>