<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "produk.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;
$paginationrecords = "SELECT a.namaProduk, a.idProduk, a.kodeProduk, b.jenisProduk, a.hargaAsli
		FROM produk AS a
		LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
		ORDER BY a.idProduk DESC";
if ($_REQUEST['searchProduk'] =='Cari'){
	$search = $_REQUEST['produk'];
	$paginationrecords = "SELECT a.namaProduk, a.idProduk, a.kodeProduk, b.jenisProduk, a.hargaAsli
	FROM produk AS a
	LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
	WHERE a.kodeProduk LIKE '%".$search."%'
	OR a.namaProduk LIKE '%".$search."%'
	ORDER BY a.idProduk DESC";
}

if ($_REQUEST['sort']){
	$sort = $_REQUEST['sort'];
	$paginationrecords = "SELECT a.namaProduk, a.idProduk, a.kodeProduk, b.jenisProduk, a.hargaAsli
	FROM produk AS a
	LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
	WHERE a.idJenisProduk = '".$sort."'
	ORDER BY a.idProduk DESC";
} 

$paginator = new Paginator($db, $paginationrecords);
$dataproduk =$paginator->getData($limit, $page);

//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$cariproduk = $db->table("SELECT a.namaProduk, a.idProduk
		FROM produk AS a
		WHERE a.namaProduk LIKE '%$key%'
		ORDER BY a.namaProduk");
	die(json_encode($cariproduk));
}

//sortitems
$sortProdukItems = $db->table("SELECT DISTINCT a.idJenis, a.jenisProduk,
								(SELECT COUNT(*) 
								FROM produk AS a1
								LEFT JOIN jenisproduk AS b1 ON b1.idJenis = a1.idJenisProduk
								WHERE a1.idJenisProduk = a.idJenis
								GROUP BY a1.idJenisProduk) jumlah
							FROM jenisproduk AS a
							ORDER BY a.idJenis");
							
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<?php if( isset($_REQUEST['mode']) && $_REQUEST['mode'] = 'view' && isset($_REQUEST['idProduk']) ):?>
	<?php 
		$opnameTerakhir = $db->row("SELECT a.tanggalOpname 
		FROM opname AS a 
		LEFT JOIN opnameDetail AS b ON a.idOpname = b.idOpname
		LEFT JOIN produk AS c ON b.idProduk = c.idProduk
		WHERE c.idProduk = '%s'
		ORDER BY a.tanggalOpname DESC LIMIT 1", $_REQUEST['idProduk']);
		$dataProdukDetail = $db->row("SELECT a.idProduk, a.kodeProduk, a.namaProduk, b.jenisProduk, a.foto, a.hargaAsli,
		c.qty, d.tanggalOpname
		FROM produk AS a
		LEFT JOIN jenisProduk AS b ON a.idJenisProduk = b.idJenis
		LEFT JOIN opnameDetail AS c ON a.idProduk = c.idProduk
		LEFT JOIN opname AS d ON c.idOpname = d.idOpname
		WHERE a.idProduk = '%s' AND d.tanggalOpname = '%s'", $_REQUEST['idProduk'], $opnameTerakhir->tanggalOpname);
	?>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Data Produk <?=$dataProdukDetail->namaProduk;?>
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formProduk.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Produk</button>
			</a>
			<a style="float: right;margin-bottom: 10px !important;" target="_blank" href="laporan-produk.php">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan</button>
			</a>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-8">
					<div class="box box-solid">
						<div class="box-header with-border">
							<i class="fa  fa-arrows-v"></i>
				
							<h3 class="box-title">Sort Produk</h3>
						</div>
						<div class="box-body">
							<ul style="padding:0 3px !important;list-style:none;">
								<li style="display:inline;">
									<a href="produk.php">All | </a>
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
							
							<input style="width:100% !important;" placeholder="Search Produk"name="produk" id="produk-autocomplete" type="text" class="form-control">
							<span class="input-group-btn">
								<button name="searchProduk" value ="cari" type="button" class="btn btn-info btn-flat">Cari</button>
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
							<?php if (isset($dataProdukDetail->foto)):?>
								<img class="profile-user-img img-responsive img-circle" src="classes/<?=$file->download($dataProdukDetail->foto);?>" alt="Foto Produk">
							<?php else: ?>
								<img class="profile-user-img img-responsive img-circle" src="dist/img/clothes_512pxGREY.png" alt="Foto Produk">
							<?php endif; ?>
							<h3 class="profile-username text-center"><?=$dataProdukDetail->namaProduk;?></h3>
						
							<p class="text-muted text-center"><?=$dataProdukDetail->kodeProduk;?></p>
						
							<ul class="list-group list-group-unbordered">
								<li class="list-group-item">
									<b>Jenis</b> 
									<a class="pull-right"><?=$dataProdukDetail->jenisProduk;?></a>
									
								</li>
								<li class="list-group-item">
									<b>Harga Asli</b> <a class="pull-right"><?=$dataProdukDetail->hargaAsli;?></a>
								</li>

							</ul>
						</div>
					</div>
					<div class="box box-primary">
					<?php
						$opnamePertama = $db->row("SELECT a.tanggalOpname, a.idOutlet
							FROM opname AS a 
							LEFT JOIN opnameDetail AS b ON a.idOpname = b.idOpname
							LEFT JOIN produk AS c ON b.idProduk = c.idProduk
							WHERE c.idProduk = '%s' AND a.idOutlet = 1
							ORDER BY a.tanggalOpname ASC LIMIT 1", $_REQUEST['idProduk']);
						$opnameTerakhir = $db->row("SELECT a.tanggalOpname, a.idOutlet
							FROM opname AS a 
							LEFT JOIN opnameDetail AS b ON a.idOpname = b.idOpname
							LEFT JOIN produk AS c ON b.idProduk = c.idProduk
							WHERE c.idProduk = '%s'
							ORDER BY a.tanggalOpname DESC LIMIT 1", $_REQUEST['idProduk']);
						$stokTerakhir = $db->row("SELECT a.qty
							FROM opnameDetail AS a 
							LEFT JOIN opname AS b ON a.idOpname = b.idOpname
							LEFT JOIN produk AS c ON a.idProduk = c.idProduk
							WHERE c.idProduk = '%s'
							ORDER BY b.tanggalOpname DESC LIMIT 1", $_REQUEST['idProduk']);
					?>
						<div class="box-header with-border">
							<h3 class="box-title">Keterangan Lainnya</h3>
						</div>
							<div class="box-body">
								<strong><i class="fa  fa-calendar-check-o margin-r-5"></i> Tanggal Barang Masuk </strong>
							
								<p class="text-muted">
								<?=Template::format($opnamePertama->tanggalOpname,"date");?>
								</p>
							
								<hr>
							
								<strong><i class="fa fa-calendar-check-o margin-r-5"></i> Tanggal Terakhir Opname</strong>
							
								<p class="text-muted"><?=Template::format($opnameTerakhir->tanggalOpname,"date");?></p>
							
								<hr>
							
								<strong><i class="fa fa-cart-plus margin-r-5"></i> Stok Terakhir</strong>
							
								<p class="text-muted"><?=$stokTerakhir->qty;?></p>
							</div>
					<!-- /.box-body -->
					</div>
					<!-- /.box -->
				</div>
				<div class="col-md-9">
					<div class="nav-tabs-custom">
						<ul class="nav nav-tabs">
							 <li class="active"><a href="#outlet" data-toggle="tab">Outlet</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="outlet">
								<?php 
									$outlet = $db->table("SELECT a.namaOutlet,a.idOutlet FROM outlet AS a ORDER BY a.idOutlet");
								?>
								<?php foreach ($outlet as $k=>$v):?>
								<?=$v->namaOutlet;?>
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Tanggal Opname</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Stok</th>
										</tr>
									</thead>
									<tbody>
										<?php
											$produkStokPertoko = $db->table("SELECT a.tanggalOpname, a.idOutlet, b.qty
												FROM opname AS a 
												LEFT JOIN opnameDetail AS b ON a.idOpname = b.idOpname
												LEFT JOIN produk AS c ON b.idProduk = c.idProduk
												WHERE c.idProduk = '%s' AND a.idOutlet = '%s'
												ORDER BY a.tanggalOpname DESC", $_REQUEST['idProduk'], $v->idOutlet);
										?>
										<?php foreach ($produkStokPertoko as $k=>$v):?>
										<tr role="row">
											<td class="sorting_1"><?=Template::format($v->tanggalOpname,"date");?></td>
											<td><?=$v->qty;?></td>
										</tr>
										<?php endforeach;?>
									</tbody>
								</table>
								<hr />
								<?php endforeach;?>
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
				Manajemen Master Data Produk
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formProduk.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Produk</button>
			</a>
			<a style="float: right;margin-bottom: 10px !important;"  target="_blank" href="laporan-produk.php">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan</button>
			</a>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-8">
					<div class="box box-solid">
						<div class="box-header with-border">
							<i class="fa  fa-arrows-v"></i>
				
							<h3 class="box-title">Sort Produk</h3>
						</div>
						<div class="box-body">
							<ul style="padding:0 3px !important;list-style:none;">
								<li style="display:inline;">
									<a href="produk.php">All | </a>
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
					<div class="box">
						<div class="box-header">
							<h3 class="box-title">Data Produk</h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Kode Produk</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Nama Produk</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Platform(s): activate to sort column ascending" style="width: 197px;">Jenis</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Engine version: activate to sort column ascending" style="width: 154px;">Harga Asli</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending" style="width: 112px;">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($dataproduk->data as $k=>$v):?>
										<tr role="row">
											<td class="sorting_1"><?=$v->kodeProduk;?></td>
											<td><?=$v->namaProduk;?></td>
											<td><?=$v->jenisProduk;?></td>
											<td><?=$v->hargaAsli;?></td>
											<td>
											
											<?php if (in_array('MANAGE_PRODUK', $hakAkses[$_SESSION['hak']])):?>
												<a class="btn btn-default" href="formProduk.php?mode=edit&idProduk=<?=$v->idProduk;?>">Edit</a>
											<?php endif; ?>
												<a class="btn btn-default" href="?mode=view&idProduk=<?=$v->idProduk;?>">Detail</a>
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