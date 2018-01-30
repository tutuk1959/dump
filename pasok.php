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
//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;
$tanggalSupply = date('Y-m', strtotime('now')).'-%';
$paginationrecords = "SELECT DISTINCT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, d.size, d.tipeUkur, e.jenisProduk, SUM(a.qty) AS jumlah
		FROM supplydetail AS a
		LEFT JOIN supply AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSupply LIKE '".$tanggalSupply."' AND b.idOutlet = '".$_SESSION['idOutlet']."'
		GROUP BY b.tanggalSupply,a.idProduk,c.idJenisProduk";
if (isset($_REQUEST['toko']) && isset($_REQUEST['urut']) && isset ($_REQUEST['tanggalSupply'])){
	$tanggalSupply = substr($_REQUEST['tanggalSupply'], 6,10).'-'.substr($_REQUEST['tanggalSupply'], 0,2).'-%';
	_validation($_REQUEST['toko'],$_REQUEST['tanggalSupply'], $error);
	if (!$error){
		if ($_REQUEST['urut'] == 'namaProduk'){
		$paginationrecords = "SELECT DISTINCT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, d.size, d.tipeUkur, e.jenisProduk, SUM(a.qty) AS jumlah
		FROM supplydetail AS a
		LEFT JOIN supply AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSupply LIKE '".$tanggalSupply."' AND b.idOutlet = '".$_REQUEST['toko']."'
		GROUP BY b.tanggalSupply,a.idProduk
		ORDER BY c.namaProduk,b.tanggalSupply,c.idJenisProduk";
		} elseif ($_REQUEST['urut'] == 'kodeProduk'){
		$paginationrecords = "SELECT DISTINCT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, d.size, d.tipeUkur, e.jenisProduk, SUM(a.qty) AS jumlah
		FROM supplydetail AS a
		LEFT JOIN supply AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSupply LIKE '".$tanggalSupply."' AND b.idOutlet = '".$_REQUEST['toko']."'
		GROUP BY b.tanggalSupply,a.idProduk
		ORDER BY c.namaProduk,b.tanggalSupply,c.idJenisProduk";
		} elseif($_REQUEST['urut'] == 'hargaAsli'){
		$paginationrecords = "SELECT DISTINCT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, d.size, d.tipeUkur, e.jenisProduk, SUM(a.qty) AS jumlah
		FROM supplydetail AS a
		LEFT JOIN supply AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSupply LIKE '".$tanggalSupply."' AND b.idOutlet = '".$_REQUEST['toko']."'
		GROUP BY b.tanggalSupply,a.idProduk
		ORDER BY c.namaProduk,b.tanggalSupply,c.idJenisProduk";
		} else {
		$paginationrecords = "SELECT DISTINCT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, d.size, d.tipeUkur, e.jenisProduk, SUM(a.qty) AS jumlah
		FROM supplydetail AS a
		LEFT JOIN supply AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSupply LIKE '".$tanggalSupply."' AND b.idOutlet = '".$_REQUEST['toko']."'
		GROUP BY b.tanggalSupply,a.idProduk
		ORDER BY c.namaProduk,b.tanggalSupply,c.idJenisProduk";
		}
	}
	
	
}

//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$cariproduk = $db->table("SELECT a.namaProduk, a.idProduk
		FROM produk AS a
		WHERE a.namaProduk LIKE '%$key%'
		ORDER BY a.namaProduk");
	die(json_encode($cariproduk));
}

if ($_REQUEST['sort']){
	$sort = $_REQUEST['sort'];
	$paginationrecords = "SELECT DISTINCT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, d.size, d.tipeUkur, e.jenisProduk, SUM(a.qty) AS jumlah
		FROM supplydetail AS a
		LEFT JOIN supply AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSupply LIKE '".$tanggalSupply."' AND b.idOutlet = '".$_SESSION['idOutlet']."' AND c.idJenisProduk = '".$sort."'
		GROUP BY b.tanggalSupply,a.idProduk
		ORDER BY c.namaProduk,b.tanggalSupply,c.idJenisProduk";
} 

$paginator = new Paginator($db, $paginationrecords);
$dataproduk =$paginator->getData($limit, $page);

//sortitems
if (isset($_REQUEST['toko'])){
	$tokoForSorting = $_REQUEST['toko'];
} else {
	$tokoForSorting = $_SESSION['idOutlet'];
}
$sortProdukItems = $db->table("SELECT DISTINCT a.idJenis, a.jenisProduk,
								(SELECT SUM(a1.qty) 
								FROM supplydetail AS a1
								LEFT JOIN supply AS b1 ON a1.idSupply = b1.idSupply
								LEFT JOIN produk AS c1 ON a1.idProduk = c1.idProduk
								LEFT JOIN jenisproduk AS d1 ON c1.idJenisProduk = d1.idJenis
								WHERE b1.tanggalSupply LIKE '".$tanggalSupply."' AND b1.idOutlet = '".$tokoForSorting."' AND d1.idJenis = a.idJenis
								GROUP BY c1.idJenisProduk
								) jumlah
							FROM jenisproduk AS a
							ORDER BY a.idJenis");
//selectBoxToko
$selectToko = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<?php if( isset($_REQUEST['mode']) && $_REQUEST['mode'] = 'view' && isset($_REQUEST['idSupply']) && isset($_REQUEST['idProduk'])):?>
	<?php 
		$detailProduk = $db->row("SELECT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, c.foto, d.size, d.tipeUkur, e.jenisProduk, a.qty
		FROM supplydetail AS a
		LEFT JOIN supply AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSupply LIKE '%s' AND b.idOutlet = '%s' AND b.idSupply = '%s' AND a.idProduk = '%s'
		GROUP BY a.idProduk", $_REQUEST['tanggalSupply'], $_REQUEST['idOutlet'], $_REQUEST['idSupply'], $_REQUEST['idProduk']);
		
		$detailSupplyProduk = $db->row("SELECT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, c.foto, d.size, d.tipeUkur, e.jenisProduk,SUM(a.qty) jumlah
		FROM supplydetail AS a
		LEFT JOIN supply AS b ON a.idSupply = b.idSupply
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSupply LIKE '%s' AND b.idOutlet = '%s' AND b.idSupply = '%s' AND a.idProduk = '%s'", $_REQUEST['tanggalSupply'], $_REQUEST['idOutlet'], $_REQUEST['idSupply'], $_REQUEST['idProduk']);
	?>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Detail Pasok Produk
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formPasokProduk.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Pasok</button>
			</a>
			<a style="float: right;margin-bottom: 10px !important;" href="laporan-invoice-pasok.php?mode=report&tanggalSupply=<?=$_REQUEST['tanggalSupply'];?>&idOutlet=<?=$_REQUEST['idOutlet'];?>">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan Detail Pasok</button>
			</a>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-8">
					<div class="box box-solid">
						<div class="box-header with-border">
							<i class="fa  fa-arrows-v"></i>
				
							<h3 class="box-title">Sort Pasok</h3>
						</div>
						<div class="box-body">
							<ul style="padding:0 3px !important;list-style:none;">
								<li style="display:inline;">
									<a href="pasok.php">All | </a>
								</li>
							<?php foreach($sortProdukItems as $k=>$v):?>
								<li style="display:inline;"> 
									<a href="?sort=<?=$v->idJenis;?>"><?=$v->jenisProduk; ?> (<?=($v->jumlah != null) ? $v->jumlah : 0;?>) |</a>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<form action="produk.php" method="post">
						<div class="input-group input-group-sm">
							<input style="width:100% !important;" placeholder="Search Produk" name="produk" id="produk-autocomplete" type="text" class="form-control">
							<span class="input-group-btn">
								<input name="searchProduk" value ="Cari" type="submit" class="btn btn-info btn-flat"/>
							</span>
							<div data-id="produk-template" id="results">
								<div data-id="search-produk" class="item"><div data-id="produk-krik"></div></div>
							</div>
							<input type="hidden" name="idproduk" data-id="idproduk-krik"/>
						</div>
					</form>
				</div>
			</div>
			<div class="row">
				 <div class="col-md-3">
					<!-- Profile Image -->
					<div class="box box-primary">
						<div class="box-body box-profile">
							<?php if (isset($detailSupplyProduk->foto)):?>
								<img class="profile-user-img img-responsive img-circle" src="classes/<?=$file->download($detailSupplyProduk->foto);?>" alt="Foto Produk">
							<?php else: ?>
								<img class="profile-user-img img-responsive img-circle" src="dist/img/clothes_512pxGREY.png" alt="Foto Produk">
							<?php endif; ?>
							<h3 class="profile-username text-center"><?=$detailSupplyProduk->namaProduk;?></h3>
						
							<p class="text-muted text-center"><?=$detailSupplyProduk->kodeProduk;?></p>
						
							<ul class="list-group list-group-unbordered">
								<li class="list-group-item">
									<b>Jenis</b> 
									<a class="pull-right"><?=$detailSupplyProduk->jenisProduk;?></a>
									
								</li>
								<li class="list-group-item">
									<b>Harga Asli</b> <a class="pull-right"><?=$detailSupplyProduk->hargaAsli;?></a>
								</li>

							</ul>
						</div>
					</div>
					<div class="box box-primary">
						<div class="box-header with-border">
							<h3 class="box-title">Pasokan Produk Global</h3>
						</div>
							<?php
								$pasokanGlobal = $db->row("SELECT SUM(a.qty) jumlah
								FROM supplydetail AS a
								LEFT JOIN supply AS b ON a.idSupply = b.idSupply
								LEFT JOIN produk AS c ON a.idProduk = c.idProduk
								LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
								LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
								WHERE b.tanggalSupply LIKE '%s' AND b.idOutlet = '%s' AND b.idSupply = '%s' AND a.idProduk = '%s'", $_REQUEST['tanggalSupply'], $_REQUEST['idOutlet'], $_REQUEST['idSupply'], $_REQUEST['idProduk']);
							?>
							<div class="box-body">
								<strong><i class="fa fa-cart-plus margin-r-5"></i> Jumlah Total</strong>
							
								<p class="text-muted"><?=$pasokanGlobal->jumlah;?></p>
							</div>
					<!-- /.box-body -->
					</div>
					<!-- /.box -->
				</div>
				<div class="col-md-9">
					<div class="nav-tabs-custom">
						<ul class="nav nav-tabs">
							 <li class="active"><a href="#outlet" data-toggle="tab">Rincian Pasok Produk</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="outlet">
								<?php 
									$pasokdetail = $db->table("SELECT a.idProduk, b.idSupply, b.tanggalSupply, b.idOutlet, c.kodeProduk, c.namaProduk, c.hargaAsli, d.size, d.tipeUkur, e.jenisProduk, a.qty, f.namaOutlet
										FROM supplydetail AS a
										LEFT JOIN supply AS b ON a.idSupply = b.idSupply
										LEFT JOIN produk AS c ON a.idProduk = c.idProduk
										LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
										LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
										LEFT JOIN outlet AS f ON b.idOutlet = f.idOutlet
										WHERE b.tanggalSupply LIKE '%s' AND b.idOutlet = '%s' AND b.idSupply = '%s' AND a.idProduk = '%s'", $_REQUEST['tanggalSupply'],$_REQUEST['idOutlet'], $_REQUEST['idSupply'], $_REQUEST['idProduk']);
								?>
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Tanggal Pasok</th>
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Outlet</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Size</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Qty</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($pasokdetail as $k=>$v):?>
										<tr role="row">
											<td class="sorting_1"><?=Template::format($v->tanggalSupply,"date");?></td>
											<td class="sorting_1"><?=$v->namaOutlet;?></td>
											<td class="sorting_1"><?=$v->size;?></td>
											<td><?=$v->qty;?></td>
										</tr>
										<?php endforeach;?>
										
									</tbody>
								</table>
								<a class="btn btn-default" href="?mode=view&tanggalSupply=<?=$_REQUEST['tanggalSupply'];?>">Back</a>
							</div>
						</div> 
					</div>
				</div>
			</div>
		</section>
	</div>
	<?php else: ?>
	
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Pasok Produk
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formPasokProduk.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Pasok</button>
			</a>
			<a style="float: left;margin-left:5px !important;margin-bottom: 10px !important;" target="_blank"href="laporan-pasok.php?mode=report&tanggalSupply=<?=($_REQUEST['tanggalSupply']) ? $_REQUEST['tanggalSupply'] : date('Y-m',strtotime("now"));?>&idOutlet=<?=($_REQUEST['toko']) ? $_REQUEST['toko'] : 1;?>">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan Pasok Global</button>
			</a>
			<form class="col-md-7 pull-right"action="?mode=view" method="post">
				<div class="col-sm-3 form-group">
					
					<select name="toko" class="form-control">
						<option value="">Pilih Toko / Outlet</option>
						<?php foreach($selectToko as $k=>$v) :?>
							<option value="<?=$v->idOutlet;?>" <?=($_SESSION['idOutlet'] == $v->idOutlet) ? 'selected' : '';?> ><?=$v->namaOutlet;?></option>
						<?php endforeach;?>
					</select>
				</div>
				<div class="col-sm-4 form-group">
					<select name="urut" class="form-control">
						<option value="">Urutkan sesuai</option>
						<option value="namaProduk">Nama Produk</option>
						<option value="kodeProduk">Kode Produk</option>
						<option value="hargaAsli">Harga Produk</option>
					</select>
				</div>
				<div class="col-sm-4 form-group">
					<div class="input-group date">
					<div class="input-group-addon">
						<i class="fa fa-calendar"></i>
					</div>
					<input name="tanggalSupply" type="text" class="form-control pull-right" id="datepicker">
					</div>
				</div>
				<button class="col-sm-1 btn btn-default" type="submit" value="Sort" name="submit">Sort</button>
			</form>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-8">
					<div class="box box-solid">
						<div class="box-header with-border">
							<i class="fa  fa-arrows-v"></i>
				
							<h3 class="box-title">Sort Pasok</h3>
						</div>
						<div class="box-body">
							<ul style="padding:0 3px !important;list-style:none;">
								<li style="display:inline;">
									<a href="pasok.php">All | </a>
								</li>
							<?php foreach($sortProdukItems as $k=>$v):?>
								<li style="display:inline;"> 
									<a href="?sort=<?=$v->idJenis;?>"><?=$v->jenisProduk; ?> (<?=($v->jumlah != null) ? $v->jumlah : 0;?>) |</a>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
				<div class="col-lg-4">
					<form action="produk.php" method="post">
						<div class="input-group input-group-sm">
							<input style="width:100% !important;" placeholder="Search Produk" name="produk" id="produk-autocomplete" type="text" class="form-control">
							<span class="input-group-btn">
								<input name="searchProduk" value ="Cari" type="submit" class="btn btn-info btn-flat"/>
							</span>
							<div data-id="produk-template" id="results">
								<div data-id="search-produk" class="item"><div data-id="produk-krik"></div></div>
							</div>
							<input type="hidden" name="idproduk" data-id="idproduk-krik"/>
						</div>
					</form>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="box">
						<div class="box-header">
							<?php 
							if (isset($_REQUEST['toko'])){
								$outlet = $_REQUEST['toko']; 
							} else {
								$outlet = $_SESSION['idOutlet'];
							}
							$outletpasok = $db->row("SELECT a.namaOutlet FROM outlet AS a WHERE a.idOutlet = '%s'", $outlet);
							$bulanpasok = substr($tanggalSupply,5,2).' Tahun '.substr($tanggalSupply,0,4);
							?>
							<h3 class="box-title">Data Pasok Bulan <?=$bulanpasok;?> , <?=$outletpasok->namaOutlet;?></h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Tanggal</th>
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Kode Produk</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Nama Produk</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Platform(s): activate to sort column ascending" style="width: 197px;">Jenis</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Engine version: activate to sort column ascending" style="width: 154px;">Qty</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending" style="width: 112px;">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($dataproduk->data as $k=>$v):?>
										<tr role="row">
											
											<td class="sorting_1"><?= substr($v->tanggalSupply,8,2).'-'.substr($v->tanggalSupply,5,2).'-'.substr($tanggalSupply,0,4);?></td>
											<td class="sorting_1"><?=$v->kodeProduk;?></td>
											<td><?=$v->namaProduk;?></td>
											<td><?=$v->jenisProduk;?></td>
											<td><?=$v->jumlah;?></td>
											<td>
											
											<?php if (in_array('MANAGE_PASOK', $hakAkses[$_SESSION['hak']])):?>
												<a class="btn btn-default" href="formPasokProduk.php?mode=edit&idPasok=<?=$v->idSupply;?>&tanggalSupply=<?=$v->tanggalSupply;?>&idOutlet=<?=$v->idOutlet;?>">Edit</a>
											<?php endif; ?>
												<a class="btn btn-default" href="?mode=view&idSupply=<?=$v->idSupply;?>&idProduk=<?=$v->idProduk;?>&tanggalSupply=<?=$v->tanggalSupply;?>&idOutlet=<?=$v->idOutlet;?>">Detail</a>
											</td>
										</tr>
										<?php endforeach;?>
									</tbody>
								</table>
							</div>
							<div class="dataTables_paginate paging_simple_numbers" id="example1_paginate">
								<?php echo $paginator->createLinks($links, 'pagination');?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
	<?php endif; ?>
	<script>
		//Date picker
		$('#datepicker').datepicker({
			autoclose: true,
			dateFormat : 'yy-mm-dd'
		});
		
		//Search Produk
		var MIN_LENGTH = 1;
		var idproduk = "";
		$(function() {
			
			var template_produk_parent = $('[data-id="search-produk"]').parent();
			var template_produk = $('[data-id="search-produk"]').detach();
			$("#produk-autocomplete").keyup(function() {
				var keyword = $("#produk-autocomplete").val();
				if (keyword.length >= MIN_LENGTH) {
					$.getJSON('produk.php', {cari:keyword}, function(d){
						$(template_produk_parent).show().html('');
						if (d.length != 0) {
							$.each(d, function(k,v){
								var row = $(template_produk).clone().appendTo(template_produk_parent);
								row.find('[data-id="produk-krik"]').html(v.namaProduk);
								row.find('[data-id="idproduk-krik"]').val(v.idProduk);
								kodewilayah = v.id;
							});
							
							$('.item').click(function() {
								var text = $(this).find('[data-id="produk-krik"]').html();
								$('#produk-autocomplete').val(text);
								$('[data-id="idproduk-krik"]').val(kodewilayah);
								$(template_produk_parent).hide();
							});
						} else {
							var row = $(template_produk).clone().appendTo(template_produk_parent);
							row.find('[data-id="produk-krik"]').html('Data Produk Tidak Ditemukan');
							$('.item').click(function() {
								$(template_produk_parent).hide();
							});
						}
				});
				}
			});
		});
	</script>
<?php Template::foot();?>