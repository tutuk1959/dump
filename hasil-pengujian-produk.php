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

Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<script src="plugins/morris/morris.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
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
							<li ><a href="parameter-pengujian-produk.php?produk=<?=$_REQUEST['idProduk'];?>&size=<?=$_REQUEST['idSize'];?>&outlet=<?=$_REQUEST['idOutlet']?>&idPelatihan=<?=$_REQUEST['idPelatihan'];?>">Parameter</a></li>
							<li class="active"><a href="#ekstraksi-pengujian">Ekstraksi</a></li>
							<li><a  href="#hasil-pengujian" data-toggle="tab">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="hasil-pengujian">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Hasil Pengujian dari data Opname berdasarkanProduk</h3>
										<div class="col-lg-6 pull-right">
												<a style="float: right;margin-bottom: 10px !important;" href="laporan-pengujian.php?idPelatihan=<?=$_REQUEST['idPelatihan'];?>&idPengujian=<?=$_REQUEST['idPengujian'];?>">
													<button type="button" class="btn btn-default"><i class="fa  fa-file-code-o"></i> Ekspor Laporan</button>
												</a>
										</div>
									</div>
									
										<div class="box-body">
											<section>
											<?php
												$pelatihanSummary = $db->row("SELECT a.tanggalPelatihan, a.learningRate, a.hiddenNeuron,b.namaProduk,c.size,d.MAPE, d.trackingSignal
												FROM pelatihan AS a
												LEFT JOIN produk AS b ON a.idProduk = b.idProduk 
												LEFT JOIN sizeproduk AS c ON a.idSize = c.idSize 
												LEFT JOIN pengujian AS d ON d.idPelatihan = a.idPelatihan
												WHERE d.idPelatihan = '%s' AND d.idPengujian = '%s'",$_REQUEST['idPelatihan'],$_REQUEST['idPengujian']);
												$lastEpoch = $db->row("SELECT a.MSE,a.epoch FROM msepelatihan AS a WHERE a.idPelatihan = '%s' ORDER BY a.epoch DESC LIMIT 1",$_REQUEST['idPelatihan']);
											?>
												<table border="0" class="table table-bordered dataTable">
													<tr>
														<td colspan="4" align="center"><strong><?=$pelatihanSummary->namaProduk;?>,Size <?=$pelatihanSummary->size;?></strong></td>
													</tr>
													<tr>
														<td width="20%">Periode Uji</td>
														<td width="30%"><?=Template::format($pelatihanSummary->tanggalPelatihan,"date");?></td>
														<td width="20%">Total Epoch</td>
														<td width="30%"><?=$lastEpoch->epoch?></td>
													</tr>
													<tr>
														<td width="20%">Hidden Neuron</td>
														<td width="30%"><?=$pelatihanSummary->hiddenNeuron;?></td>
														<td width="20%">MSE</td>
														<td width="30%"><?=$lastEpoch->MSE?></td>
													</tr>
													<tr>
														<td width="20%">Learning Rate</td>
														<td width="30%"><?=$pelatihanSummary->learningRate;?></td>
														<td width="20%">MAPE</td>
														<td width="30%"><?=$pelatihanSummary->MAPE;?></td>
													</tr>
													<tr>
														<td width="20%">Tracking Signal</td>
														<td width="30%"><?=$pelatihanSummary->trackingSignal;?></td>
													</tr>
												</table>
											</section>
											<?php $_SESSION['idPengujian'] = $_REQUEST['idPengujian']; ?>
											<section class="connectedSortable">
												<div class="nav-tabs-custom">
													<!-- Tabs within a box -->
													<ul class="nav nav-tabs pull-right">
													<li class="active"><a href="#mse-chart" data-toggle="tab">Area</a></li>
													<li class="pull-left header"><i class="fa fa-inbox"></i> Perbandingan</li>
													</ul>
													<div class="tab-content no-padding">
													<!-- Morris chart - Sales -->
													<div class="chart tab-pane active" id="perbandingan-chart" style="position: relative; height: 300px;"></div>
													<script src="graph-pengujian.php"></script>
													</div>
												</div>
											</section>
											<div class="clearfix"></div>
											<section>
												<?php
													$pembobotanvPrefillneuroninput1 = $db->table("SELECT a.idPelatihan, a.neuronFrom, a.neuronTo, a.weight, a.jenis
													FROM bobotpelatihan AS a
													WHERE a.idPelatihan = '%s' AND a.jenis = '%s' AND a.neuronFrom = '%s'",$_REQUEST['idPelatihan'],'v',0);
													$pembobotanvPrefillneuroninput2 = $db->table("SELECT a.idPelatihan, a.neuronFrom, a.neuronTo, a.weight, a.jenis
													FROM bobotpelatihan AS a
													WHERE a.idPelatihan = '%s' AND a.jenis = '%s' AND a.neuronFrom = '%s'",$_REQUEST['idPelatihan'],'v',1);
													$pembobotanvPrefillneuroninput3 = $db->table("SELECT a.idPelatihan, a.neuronFrom, a.neuronTo, a.weight, a.jenis
													FROM bobotpelatihan AS a
													WHERE a.idPelatihan = '%s' AND a.jenis = '%s' AND a.neuronFrom = '%s'",$_REQUEST['idPelatihan'],'v',2);
													$pembobotanvPrefillneuronbias1 = $db->table("SELECT a.idPelatihan, a.neuronFrom, a.neuronTo, a.weight, a.jenis
													FROM bobotpelatihan AS a
													WHERE a.idPelatihan = '%s' AND a.jenis = '%s' AND a.neuronFrom = '%s'",$_REQUEST['idPelatihan'],'v',3);
													$pembobotanwPrefillneuronitersembunyi1 = $db->table("SELECT a.idPelatihan, a.neuronFrom, a.neuronTo, a.weight, a.jenis
													FROM bobotpelatihan AS a
													WHERE a.idPelatihan = '%s' AND a.jenis = '%s' AND a.neuronFrom BETWEEN 0 AND 6",$_REQUEST['idPelatihan'],'w');
													$pembobotanvPrefillneuronbias2 = $db->row("SELECT a.idPelatihan, a.neuronFrom, a.neuronTo, a.weight, a.jenis
													FROM bobotpelatihan AS a
													WHERE a.idPelatihan = '%s' AND a.jenis = '%s' AND a.neuronFrom= '%s' AND a.neuronTo='%s' ",$_REQUEST['idPelatihan'],'w',7,0);
												?>
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
															<?php foreach($pembobotanvPrefillneuroninput1 as $k=>$v):?>
																<td><input step="any" class="form-control" type="number" value="<?=$v->weight;?>"/></td>
															<?php endforeach;?>
															
														</tr>
														<tr>
															<td>x2</td>
															<?php foreach($pembobotanvPrefillneuroninput2 as $k=>$v):?>
																<td><input step="any" class="form-control" type="number" value="<?=$v->weight;?>"/></td>
															<?php endforeach;?>
														</tr>
														<tr>
															<td>x3</td>
															<?php foreach($pembobotanvPrefillneuroninput3 as $k=>$v):?>
																<td><input step="any" class="form-control" type="number" value="<?=$v->weight;?>"/></td>
															<?php endforeach;?>
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
															<?php foreach($pembobotanvPrefillneuronbias1 as $k=>$v):?>
																<td><input step="any" class="form-control" type="number" value="<?=$v->weight;?>"/></td>
															<?php endforeach;?>
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
															<?php foreach($pembobotanwPrefillneuronitersembunyi1 as $k=>$v):?>
																<tr>
																	<td>z<?=$k+1?></td>
																	<td><input step="any" class="form-control" type="number" value="<?=$v->weight;?>"/></td>
																</tr>
															<?php endforeach;?>
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
															<td><input step="any" class="form-control" type="number" value="<?=$pembobotanvPrefillneuronbias2->weight;?>"/></td>
															
														</tr>
													</tbody>
												</table>
											</section>
										</div>
										<div class="box-footer">
											<a href="pengujian-produk.php?" class="btn btn-info pull-left">Back</a>
											
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