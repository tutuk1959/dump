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
	$date = $_REQUEST['tanggalSupply'];
	$tanggalSupply = date('Y-m',strtotime("$date")).'-%';
	$prefillOutlet = $db->row("SELECT a.namaOutlet FROM outlet AS a WHERE a.idOutlet = '%s'",$_REQUEST['idOutlet']);
	$prefillSupply = $db->row("SELECT COUNT(a.idSupply) AS jumlahSupply,a.idSupply, a.idOutlet, a.tanggalSupply FROM supply AS a WHERE  a.tanggalSupply LIKE '%s'",$tanggalSupply);
	if ($prefillSupply->jumlahSupply > 0){
		foreach ($db->table("SELECT a.idSupply,b.idSupplyDetail, a.tanggalSupply, a.idOutlet,f.namaOutlet,b.idProduk, b.idSize, b.qty AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk,e.idJenis,c.hargaAsli
		FROM supply AS a
		LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		LEFT JOIN outlet AS f ON a.idOutlet = f.idOutlet
		WHERE a.tanggalSupply LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk,a.tanggalSupply ORDER BY a.tanggalSupply,b.idProduk,c.idJenisProduk",$tanggalSupply,$_REQUEST['idOutlet']) as $row){
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
Template::reportHead('Laporan Pasok Global','pasok.php');;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<div class="content-wrapper" style="position:relative;top:40px !important;">
		<div class="container">
			<section class="content-header"><?php showMessageAndErrors($message,$error);?></section>
			<section class="invoice" style="margin:10px 0px !important;">
				<div class="col-xs-12">
					<h3 class="page-header">
						<i class="fa fa-globe"></i> Laporan Pasokan Produk <?=$prefillOutlet->namaOutlet;?> , Bulan <?=date('m',strtotime("$date"));?> Tahun <?=date('Y',strtotime("$date"));?>
					</h3>
					<a data-toolbar="data-toolbar"style="float: left;">
						<button type="button" class="btn btn-default pull-right" onClick="javascript:$('[data-toolbar]').hide(); window.print(); $('[data-toolbar]').show();" ><i class="fa fa-print"></i> Print</button>
					</a>
				</div>
				<!-- /.row -->
	
				<!-- Table row -->
				<div class="row">
					<div class="col-xs-12 table-responsive">
					<table class="table table-striped">
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
									<th style="vertical-align:middle !important;" rowspan="3">Total</th>
									<th style="vertical-align:middle !important;" rowspan="3">Harga Asli</th>
									<th style="vertical-align:middle !important;" rowspan="3" >Nominal</th>
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
							<?php if ($prefillSupply->tanggalSupply) :?>
									<?php foreach($data as $k=>$v):?>
										<?php foreach($v as $kk=>$row):?>
											<?php if ($row['tipeUkur']==1) :?>
												<tr role="row">
													
													<td style="width:20%;" class="sorting_1">
														<div>
															<?=$row['namaProduk'];?>
														</div>
													</td>
													<td class="sorting_1">
														<div >
															<?=$row['jenisProduk'];?>
														</div>
													</td>
													<td class="sorting_1">
														<div >
															<?=$row['tipeUkur'];?>
														</div>
													</td>
													<td>
														<div>
															<?=$row['XXS']['XXS'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['XS']['XS'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['S']['S'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['M']['M'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['L']['L'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['XL']['XL'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['XXL']['XXL'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['qtyTotal'];?>
														</div>
													</td>
													<td>
														<div >
															<?='Rp.'.Template::format($row['hargaAsli'],"money");?>
														</div>
													</td>
													<td>
														<div >
															<?='Rp.'.Template::format($row['totalNominal'],"money");?>
														</div>
													</td>
												</tr>
											<?php elseif($row['tipeUkur'] == 2): ?>
												<tr role="row">
													<td style="width:20%;" class="sorting_1">
														<div>
															<?=$row['namaProduk'];?>
														</div>
													</td>
													<td class="sorting_1">
														<div >
															<?=$row['jenisProduk'];?>
														</div>
													</td>
													<td class="sorting_1">
														<div >
															<?=$row['tipeUkur'];?>
														</div>
													</td>
													<td>
														<div>
															<?=$row['38']['38'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['39']['39'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['40']['40'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['41']['41'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['42']['42'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['43']['43'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['44']['44'];?>
														</div>
													</td>
													<td>
														<div >
															<?=$row['qtyTotal'];?>
														</div>
													</td>
													<td>
														<div >
															<?='Rp.'.Template::format($row['hargaAsli'],"money");?>
														</div>
													</td>
													<td>
														<div >
															<?='Rp.'.Template::format($row['totalNominal'],"money");?>
														</div>
													</td>
												</tr>
											<?php elseif($row['tipeUkur'] == 3): ?>
												<tr role="row">
													<td style="width:20%;" class="sorting_1">
														<div>
															<?=$row['namaProduk'];?>
														</div>
													</td>
													<td class="sorting_1">
														<div >
															<?=$row['jenisProduk'];?>
														</div>
													</td>
													<td class="sorting_1">
														<div >
															<?=$row['tipeUkur'];?>
														</div>
													</td>
													<td>
														<div>
															<?=$row['All']['All'];?>
														</div>
													</td>
													<td>
														
													</td>
													<td>
														
													</td>
													<td>
														
													</td>
													<td>
														
													</td>
													<td>
														
													</td>
													<td>
														
													</td>
													<td>
														<div >
															<?=$row['qtyTotal'];?>
														</div>
													</td>
													<td>
														<div >
															<?='Rp.'.Template::format($row['hargaAsli'],"money");?>
														</div>
													</td>
													<td>
														<div >
															<?='Rp.'.Template::format($row['totalNominal'],"money");?>
														</div>
													</td>
												</tr>
												
											<?php endif;?>
											<?php 
												$grandtotal += $row['totalNominal'];
												$outlet = $row->namaOutlet;
												$consignmentOutlet = 0.3 * $grandtotal;
												$komisiSG = $grandtotal - $consignmentOutlet;
											?>
										<?php endforeach;?>
									<?php endforeach;?>
								<?php else :?>
									NO DATA
								<?php endif;?>
								<tr >
									<td colspan="12" align="center">
										<strong>Grand Total</strong>
									</td>
									<td>
										<strong><?='Rp'.Template::format($grandtotal,"money");?></strong>
									</td>
								</tr>
								<tr>
									<td colspan="12" align="center">
										<strong>Consignment <?=$outlet;?> 30%</strong>
									</td>
									<td>
										<strong><?='Rp.'.Template::format($consignmentOutlet,"money");?></strong>
									</td>
								</tr>
								<tr>
									<td colspan="12" align="center">
										<strong>Suicide Glam</strong>
									</td>
									<td>
										<strong><?='Rp.'.Template::format($komisiSG,"money");?></strong>
									</td>
								</tr>
						</tbody>
					</table>
					</div>
					<!-- /.col -->
				</div>
			</section>
		</div>
	</div>
<?php Template::reportFoot();?>