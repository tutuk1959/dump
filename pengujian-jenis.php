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
$pelatihanPrefill = $db->table("SELECT a.idPelatihan, a.tanggalPelatihan,a.idJenis,a.hiddenNeuron, b.jenisProduk, c.size, a.idSize, a.idOutlet,
(SELECT epoch FROM msepelatihan WHERE idPelatihan = a.idPelatihan ORDER BY idMsePelatihan DESC LIMIT 1) epoch,
(SELECT MSE FROM msepelatihan WHERE idPelatihan = a.idPelatihan ORDER BY idMsePelatihan DESC LIMIT 1) mse
FROM pelatihan AS a
LEFT JOIN jenisproduk AS b ON a.idJenis = b.idJenis
LEFT JOIN sizeproduk AS c ON a.idSize = c.`idSize`
WHERE a.idProduk = 0
ORDER BY a.idPelatihan ASC");

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
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Pengujian JST dari Produk</h3>
						</div>
						<div class="box-body">
							<table class="table table-bordered table-striped dataTable"  border="1">
										<thead>
											<tr>
												<th>Tanggal Pelatihan</th>
												<th>Jumlah Hidden Neuron</th>
												<th>Epoch</th>
												<th>Jenis</th>
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
														<?=$v->jenisProduk;?>
													</td>
													<td>
														<?=$v->size;?>
													</td>
													<td>
														<?=$v->mse;?>
													</td>
													<td>
														<a href="parameter-pengujian.php?jenisProduk=<?=$v->idJenis;?>&size=<?=$v->idSize;?>&outlet=<?=$v->idOutlet;?>" class="btn btn-default">Pilih</a>
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
		</section>
	</div>

<?php Template::foot();?>