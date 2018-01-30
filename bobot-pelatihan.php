<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "bobotPelatihan.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
kickManage($hakAkses[$_SESSION['hak']]);
$selectBoxJenis = $db->table("SELECT a.idJenis, a.jenisProduk FROM jenisproduk AS a ORDER BY a.idJenis");
$selectBoxOutlet = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
$selectBoxSize = $db->table("SELECT a.idSize, a.size FROM sizeproduk AS a ORDER BY a.idsize");

if ($_REQUEST['mode'] == 'insert' && $_REQUEST['submit'] == 'Simpan') {
	print_r($_REQUEST['neuronFromTo']);
	foreach($_REQUEST['neuronFromTo'] as $k=>$v){
		foreach($v as $kk=>$vv){
			_validation($vv,$k,$kk,$error);
			if (!$error){
				$db->exec("INSERT INTO bobotpelatihan(idPelatihan, neuronFrom, neuronTo, weight) VALUES ('%s','%s','%s','%s')",$_REQUEST['idPel'],$k,$kk,$vv);
				$message = "Berhasil memasukan data bobot!";
			}
			
		}
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
							 <li ><a href="parameter-pelatihan.php?idPelatihan=<?=($_REQUEST['idPelatihan']) ? $_REQUEST['idPelatihan'] : ''?>">Parameter</a></li>
							 <li class="active"><a href="bobot-pelatihan.php?idPelatihan=<?=($_REQUEST['idPelatihan']) ? $_REQUEST['idPelatihan'] : ''?>#bobot-pelatihan">Bobot</a></li>
							 <li><a href="ekstraksi-pelatihan.php?idPelatihan=<?=($_REQUEST['idPelatihan']) ? $_REQUEST['idPelatihan'] : ''?>">Ekstraksi</a></li>
							 <li><a href="">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="bobot-pelatihan">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Pembobotan JST dari Jenis Produk</h3>
									</div>
									<form method="post" class="form-horizontal" enctype="multipart/form-data">
										<div class="box-body">
											<div class="form-group">
												<label  name="labelHiddenNeuron"class="col-sm-2 control-label">Jumlah Hidden Neuron</label>
												<div class="col-sm-8">
													<input type="hidden" class="form-control" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
													<input type="hidden" name="idPel" value="<?=$_REQUEST['idPelatihan'];?>"/>
													<input readonly type="text" class="form-control" name="hiddenNeuron" value="<?=$pembobotanPrefill->hiddenNeuron;?>"/>
													
												</div>
												<div class="col-sm-2">
													<button class="btn btn-default" type="button" name="genRnd">Generate</button>
												</div>
												<script>
												$(function(){
													$('[name="genRnd"]').click(function(){
														$('input[type="number"]').each(function(){
															if (/^neuronFromTo/.test($(this).attr('name')))
																$(this).val(Math.round((Math.random()-0.5)*10)/10);
														})
													});
												});
												</script>
											</div>
											<p>Dari input neuron (x) ke Hidden Neuron (z)</p>
											<table class="table table-bordered table-striped dataTable"  border="1">
												<thead>
													<tr>
														<th></th>
														<th>z1</th>
														<th>z2</th>
														<th>z3</th>
														<th>z4</th>
														<th>z5</th>
														<th>z6</th>
														<th>z7</th>
													</tr>
													
												</thead>
												<tbody>
													<tr>
														<td>x1</td>
														<td><input step="any" class="form-control" type="number" name="neuronFromTo[11][21]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 11 && $pembobotanPrefill->neuronTo ==21) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[11][22]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 11 && $pembobotanPrefill->neuronTo ==22) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[11][23]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 11 && $pembobotanPrefill->neuronTo ==23) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[11][24]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 11 && $pembobotanPrefill->neuronTo ==24) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[11][25]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 11 && $pembobotanPrefill->neuronTo ==25) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[11][26]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 11 && $pembobotanPrefill->neuronTo ==26) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[11][27]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 11 && $pembobotanPrefill->neuronTo ==27) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
													<tr>
														<td>x2</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[12][21]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 12 && $pembobotanPrefill->neuronTo ==21) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any" class="form-control" type="number" name="neuronFromTo[12][22]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 12 && $pembobotanPrefill->neuronTo ==22)? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[12][23]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 12 && $pembobotanPrefill->neuronTo ==23) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[12][24]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 12 && $pembobotanPrefill->neuronTo ==24) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any" class="form-control" type="number" name="neuronFromTo[12][25]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 12 && $pembobotanPrefill->neuronTo ==25) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[12][26]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 12 && $pembobotanPrefill->neuronTo ==26) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[12][27]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 12 && $pembobotanPrefill->neuronTo ==27) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
													<tr>
														<td>x3</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[13][21]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 13 && $pembobotanPrefill->neuronTo ==21) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[13][22]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 13 && $pembobotanPrefill->neuronTo ==22) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[13][23]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 13 && $pembobotanPrefill->neuronTo ==23) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[13][24]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 13 && $pembobotanPrefill->neuronTo ==24) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any" class="form-control" type="number" name="neuronFromTo[13][25]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 13 && $pembobotanPrefill->neuronTo ==25) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[13][26]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 13 && $pembobotanPrefill->neuronTo ==26) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[13][27]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 13 && $pembobotanPrefill->neuronTo ==27) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
												</tbody>
											</table>
											<p>Dari Bias (x) ke Hidden Neuron (z)</p>
											<table class="table table-bordered table-striped dataTable"  border="1">
												<thead>
													<tr>
														<th></th>
														<th>z1</th>
														<th>z2</th>
														<th>z3</th>
														<th>z4</th>
														<th>z5</th>
														<th>z6</th>
														<th>z7</th>
													</tr>
													
												</thead>
												<tbody>
													<tr>
														<td>Bias</td>
														<td><input step="any" class="form-control" type="number" name="neuronFromTo[14][21]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 14 && $pembobotanPrefill->neuronTo ==21) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[14][22]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 14 && $pembobotanPrefill->neuronTo ==22) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[14][23]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 14 && $pembobotanPrefill->neuronTo ==23) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[14][24]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 14 && $pembobotanPrefill->neuronTo ==24) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[14][25]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 14 && $pembobotanPrefill->neuronTo ==25) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[14][26]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 14 && $pembobotanPrefill->neuronTo ==26) ? $pembobotanPrefill->weight : '';?>"/></td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[14][27]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 14 && $pembobotanPrefill->neuronTo ==27) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
												</tbody>
											</table>
											<p>Dari Hidden neuron (z) ke Output Neuron (y)</p>
											<table class="table table-bordered table-striped dataTable"  border=1>
												<thead>
													<tr>
														<th></th>
														<th>y</th>
													</tr>
													
												</thead>
												<tbody>
													<tr>
														<td>z1</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[21][31]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 21 && $pembobotanPrefill->neuronTo ==31) ? $pembobotanPrefill->weight : '';?>"/></td>
														
													</tr>
													<tr>
														<td>z2</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[22][31]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 22 && $pembobotanPrefill->neuronTo ==31) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
													<tr>
														<td>z3</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[23][31]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 23 && $pembobotanPrefill->neuronTo ==31) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
													<tr>
														<td>z4</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[24][31]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 24 && $pembobotanPrefill->neuronTo ==31) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
													<tr>
														<td>z5</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[25][31]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 25 && $pembobotanPrefill->neuronTo ==31) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
													<tr>
														<td>z6</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[26][31]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 26 && $pembobotanPrefill->neuronTo ==31) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
													<tr>
														<td>z7</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[27][31]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 27 && $pembobotanPrefill->neuronTo ==31) ? $pembobotanPrefill->weight : '';?>"/></td>
													</tr>
												</tbody>
											</table>
											<p>Dari Bias Hidden neuron (z) ke Output Neuron (y)</p>
											<table class="table table-bordered table-striped dataTable"  border=1>
												<thead>
													<tr>
														<th></th>
														<th>y</th>
													</tr>
													
												</thead>
												<tbody>
													<tr>
														<td>Bias</td>
														<td><input step="any"  class="form-control" type="number" name="neuronFromTo[28][31]" value="<?=($pembobotanPrefill->neuronFrom && $pembobotanPrefill->neuronTo && $pembobotanPrefill->neuronFrom == 28 && $pembobotanPrefill->neuronTo ==31) ? $pembobotanPrefill->weight : '';?>"/></td>
														
													</tr>
												</tbody>
											</table>
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