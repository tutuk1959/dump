<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "pasok.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);

if ($_REQUEST['mode'] == 'report' && isset($_REQUEST['tanggalSupply'])){
	$prefillOutlet = $db->row("SELECT a.namaOutlet FROM outlet AS a WHERE a.idOutlet = '%s'",$_REQUEST['idOutlet']);
	$prefillSupply = $db->row("SELECT COUNT(a.idSupply) AS jumlahSupply,a.idSupply, a.idOutlet, a.tanggalSupply FROM supply AS a WHERE  a.tanggalSupply = '%s'",$_REQUEST['tanggalSupply']);
	if ($prefillSupply->jumlahSupply > 0){
		foreach ($db->table("SELECT a.idSupply,b.idSupplyDetail, a.tanggalSupply, a.idOutlet,f.namaOutlet,b.idProduk, b.idSize, b.qty AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,e.idJenis,c.hargaAsli
		FROM supply AS a
		LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		LEFT JOIN outlet AS f ON a.idOutlet = f.idOutlet
		WHERE a.tanggalSupply = '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk,a.tanggalSupply ORDER BY a.tanggalSupply,b.idProduk ",$_REQUEST['tanggalSupply'],$_REQUEST['idOutlet']) as $row){
			$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idSupplyDetail'] = $row->idSupplyDetail;
			$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
			$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
			$data[ $row->idProduk ][ $row->jenisProduk ]['hargaAsli'] = $row->hargaAsli;
			$data[ $row->idProduk ][ $row->jenisProduk ]['totalNominal'] = $row->hargaAsli * $data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'];
			$data[ $row->idProduk ][ $row->jenisProduk ]['outlet'] = $row->namaOutlet;
		}
	} else {
		$error[] = 'Tidak ada data pasok yang ditemukan!';
	}
}
Template::reportHead('Laporan Detail Pasok Produk','pasok.php');;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<script src="plugins/morris/morris.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
	<div class="content-wrapper" style="position:relative;top:40px !important;">
		<div class="container">
			<section class="content-header"><?php showMessageAndErrors($message,$error);?></section>
			<section class="invoice" style="margin:10px 0px !important;">
				<div class="col-xs-12">
					<h3 class="page-header">
						<i class="fa fa-globe"></i> Laporan Hasil Pelatihan <?=$prefillOutlet->namaOutlet;?> , <?=Template::format($prefillSupply->tanggalSupply,"date");?>
					</h3>
					<a data-toolbar="data-toolbar"style="float: left;">
						<button type="button" class="btn btn-default pull-right" onClick="javascript:$('[data-toolbar]').hide(); window.print(); $('[data-toolbar]').show();" ><i class="fa fa-print"></i> Print</button>
					</a>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<?php
							$pelatihanSummary = $db->row("SELECT a.tanggalPelatihan, a.learningRate, a.hiddenNeuron,b.jenisProduk,c.size
							FROM pelatihan AS a
							LEFT JOIN jenisproduk AS b ON a.idJenis = b.idJenis 
							LEFT JOIN sizeproduk AS c ON a.idSize = c.idSize 
							WHERE idPelatihan = '%s'",$_REQUEST['idPelatihan']);
							$lastEpoch = $db->row("SELECT a.MSE,a.epoch FROM msepelatihan AS a WHERE a.idPelatihan = '%s' ORDER BY a.epoch DESC LIMIT 1",$_REQUEST['idPelatihan']);
						?>
						<table border="0" class="table table-bordered dataTable">
							<tr>
								<td colspan="4" align="center"><strong><?=$pelatihanSummary->jenisProduk;?>,Size <?=$pelatihanSummary->size;?></strong></td>
							</tr>
							<tr>
								<td width="20%">Periode Latih</td>
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
								
							</tr>
						</table>
						<?php $_SESSION['idPelatihan'] = $_REQUEST['idPelatihan']; ?>
						<section class="connectedSortable">
							<div class="nav-tabs-custom">
								<!-- Tabs within a box -->
								<ul class="nav nav-tabs pull-right">
								<li class="active"><a href="#mse-chart" data-toggle="tab">Area</a></li>
								<li class="pull-left header"><i class="fa fa-inbox"></i> MSE per Epoch</li>
								</ul>
								<div class="tab-content no-padding">
								<!-- Morris chart - Sales -->
								<div class="chart tab-pane active" id="mse-chart" style="position: relative; height: 300px;"></div>
								<script src="graph-mse-pelatihan-pengujian.php"></script>
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
				</div>
			</section>
		</div>
	</div>
<?php Template::reportFoot();?>