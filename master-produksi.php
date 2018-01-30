<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "produksi.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;
$tanggalMasuk = date('Y-m',strtotime("now")).'-%';
$paginationrecords = "SELECT DISTINCT a.namaProduk, a.idProduk, a.kodeProduk, e.jenisProduk, a.hargaAsli,c.tanggalSelesaiProduksi,SUM(b.qty) AS totalproduksi,d.size,c.idProduksi
		FROM produk AS a
		LEFT JOIN produksidetail AS b ON b.idProduk = a.idProduk
		LEFT JOIN produksi AS c ON b.idProduksi = c.idProduksi
		LEFT JOIN sizeproduk AS d ON b.idSize = d.idSize
		LEFT JOIN jenisproduk AS e ON a.idJenisProduk = e.idJenis
		WHERE c.tanggalSelesaiProduksi LIKE '".$tanggalMasuk."'
		GROUP BY c.idProduksi,c.tanggalSelesaiProduksi,a.idProduk";
		
if (isset($_REQUEST['urut']) && isset ($_REQUEST['tanggalSelesaiProduksi'])){
	$tanggalMasuk = substr($_REQUEST['tanggalSelesaiProduksi'], 6,10).'-'.substr($_REQUEST['tanggalSelesaiProduksi'], 0,2).'-%';
	_validation($_REQUEST['toko'],$_REQUEST['tanggalSelesaiProduksi'], $error);
	if (!$error){
		if ($_REQUEST['urut'] == 'namaProduk'){
		$paginationrecords = "SELECT DISTINCT a.namaProduk, a.idProduk, a.kodeProduk, e.jenisProduk, a.hargaAsli,c.tanggalSelesaiProduksi,SUM(b.qty) AS totalproduksi,d.size,c.idProduksi
		FROM produk AS a
		LEFT JOIN produksidetail AS b ON b.idProduk = a.idProduk
		LEFT JOIN produksi AS c ON b.idProduksi = c.idProduksi
		LEFT JOIN sizeproduk AS d ON b.idSize = d.idSize
		LEFT JOIN jenisproduk AS e ON a.idJenisProduk = e.idJenis
		WHERE c.tanggalSelesaiProduksi LIKE '".$tanggalMasuk."'
		GROUP BY c.idProduksi,c.tanggalSelesaiProduksi,a.idProduk
		ORDER BY a.namaProduk";
		} elseif ($_REQUEST['urut'] == 'kodeProduk'){
		$paginationrecords = "SELECT DISTINCT a.namaProduk, a.idProduk, a.kodeProduk, e.jenisProduk, a.hargaAsli,c.tanggalSelesaiProduksi,SUM(b.qty) AS totalproduksi,d.size,c.idProduksi
		FROM produk AS a
		LEFT JOIN produksidetail AS b ON b.idProduk = a.idProduk
		LEFT JOIN produksi AS c ON b.idProduksi = c.idProduksi
		LEFT JOIN sizeproduk AS d ON b.idSize = d.idSize
		LEFT JOIN jenisproduk AS e ON a.idJenisProduk = e.idJenis
		WHERE c.tanggalSelesaiProduksi LIKE '".$tanggalMasuk."'
		GROUP BY c.idProduksi,c.tanggalSelesaiProduksi,a.idProduk
		ORDER BY a.kodeProduk";
		} elseif($_REQUEST['urut'] == 'hargaAsli'){
		$paginationrecords = "SELECT DISTINCT a.namaProduk, a.idProduk, a.kodeProduk, e.jenisProduk, a.hargaAsli,c.tanggalSelesaiProduksi,SUM(b.qty) AS totalproduksi,d.size,c.idProduksi
		FROM produk AS a
		LEFT JOIN produksidetail AS b ON b.idProduk = a.idProduk
		LEFT JOIN produksi AS c ON b.idProduksi = c.idProduksi
		LEFT JOIN sizeproduk AS d ON b.idSize = d.idSize
		LEFT JOIN jenisproduk AS e ON a.idJenisProduk = e.idJenis
		WHERE c.tanggalSelesaiProduksi LIKE '".$tanggalMasuk."'
		GROUP BY c.idProduksi,c.tanggalSelesaiProduksi,a.idProduk
		ORDER BY a.hargaAsli";
		} else {
		$paginationrecords = "SELECT DISTINCT a.namaProduk, a.idProduk, a.kodeProduk, e.jenisProduk, a.hargaAsli,c.tanggalSelesaiProduksi,SUM(b.qty) AS totalproduksi,d.size,c.idProduksi
		FROM produk AS a
		LEFT JOIN produksidetail AS b ON b.idProduk = a.idProduk
		LEFT JOIN produksi AS c ON b.idProduksi = c.idProduksi
		LEFT JOIN sizeproduk AS d ON b.idSize = d.idSize
		LEFT JOIN jenisproduk AS e ON a.idJenisProduk = e.idJenis
		WHERE c.tanggalSelesaiProduksi LIKE '".$tanggalMasuk."'
		GROUP BY c.idProduksi,c.tanggalSelesaiProduksi,a.idProduk";
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
	$paginationrecords = "SELECT DISTINCT c.idProduk, b.idProduksi, b.tanggalSelesaiProduksi,  c.kodeProduk, c.namaProduk, c.hargaAsli, d.size, d.tipeUkur, e.jenisProduk, SUM(a.qty) AS jumlah
		FROM produksidetail AS a
		LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSelesaiProduksi LIKE '".$tanggalMasuk."' AND c.idJenisProduk = '".$sort."'
		GROUP BY b.tanggalSelesaiProduksi,a.idProduk,a.idProduk";
} 

$paginator = new Paginator($db, $paginationrecords);
$dataproduk =$paginator->getData($limit, $page);

//sortitems
$sortProdukItems = $db->table("SELECT DISTINCT a.idJenis, a.jenisProduk,
								(SELECT SUM(a1.qty) 
								FROM produksidetail AS a1
								LEFT JOIN produksi AS b1 ON a1.idProduksi = b1.idProduksi
								LEFT JOIN produk AS c1 ON a1.idProduk = c1.idProduk
								LEFT JOIN jenisproduk AS d1 ON c1.idJenisProduk = d1.idJenis
								WHERE b1.tanggalSelesaiProduksi LIKE '".$tanggalMasuk."' AND d1.idJenis = a.idJenis
								GROUP BY c1.idJenisProduk
								) jumlah
							FROM jenisproduk AS a
							ORDER BY a.idJenis");
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<?php if( isset($_REQUEST['mode']) && $_REQUEST['mode'] = 'view' && isset($_REQUEST['idProduksi']) && isset($_REQUEST['idProduk'])):?>
	<?php 
		$detailProduk = $db->row("SELECT a.idProduk, b.idProduksi, b.tanggalSelesaiProduksi,  c.kodeProduk, c.namaProduk, c.hargaAsli, c.foto, d.size, d.tipeUkur, e.jenisProduk, a.qty
		FROM produksidetail AS a
		LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSelesaiProduksi LIKE '%s' AND b.idProduksi = '%s' AND a.idProduk = '%s'
		GROUP BY a.idProduk", $_REQUEST['tanggalSelesaiProduksi'],$_REQUEST['idProduksi'], $_REQUEST['idProduk']);
		
		$detailProduksiProduk = $db->row("SELECT a.idProduk, b.idProduksi, b.tanggalSelesaiProduksi,  c.kodeProduk, c.namaProduk, c.hargaAsli, c.foto, d.size, d.tipeUkur, e.jenisProduk,SUM(a.qty) jumlah
		FROM produksidetail AS a
		LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
		LEFT JOIN produk AS c ON a.idProduk = c.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
		LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
		WHERE b.tanggalSelesaiProduksi LIKE '%s' AND b.idProduksi = '%s' AND a.idProduk = '%s'", $_REQUEST['tanggalSelesaiProduksi'], $_REQUEST['idProduksi'], $_REQUEST['idProduk']);
	?>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Detail Produksi
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formProduksi.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Produksi</button>
			</a>
			<a style="float: left;margin-left:5px !important;margin-bottom: 10px !important;" href="">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan</button>
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
									<a href="master-produksi.php">All | </a>
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
							<?php if (isset($detailProduksiProduk->foto)):?>
								<img class="profile-user-img img-responsive img-circle" src="classes/<?=$file->download($detailProduksiProduk->foto);?>" alt="Foto Produk">
							<?php else: ?>
								<img class="profile-user-img img-responsive img-circle" src="dist/img/clothes_512pxGREY.png" alt="Foto Produk">
							<?php endif; ?>
							<h3 class="profile-username text-center"><?=$detailProduksiProduk->namaProduk;?></h3>
						
							<p class="text-muted text-center"><?=$detailProduksiProduk->kodeProduk;?></p>
						
							<ul class="list-group list-group-unbordered">
								<li class="list-group-item">
									<b>Jenis</b> 
									<a class="pull-right"><?=$detailProduksiProduk->jenisProduk;?></a>
									
								</li>
								<li class="list-group-item">
									<b>Harga Asli</b> <a class="pull-right"><?=$detailProduksiProduk->hargaAsli;?></a>
								</li>

							</ul>
						</div>
					</div>
					<div class="box box-primary">
						<div class="box-header with-border">
							<h3 class="box-title">Produksi Produk Global</h3>
						</div>
							<?php
								$pasokanGlobal = $db->row("SELECT SUM(a.qty) jumlah
								FROM produksidetail AS a
								LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
								LEFT JOIN produk AS c ON a.idProduk = c.idProduk
								LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
								LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
								WHERE b.tanggalSelesaiProduksi LIKE '%s' AND b.idProduksi = '%s' AND a.idProduk = '%s'", $_REQUEST['tanggalSelesaiProduksi'], $_REQUEST['idProduksi'], $_REQUEST['idProduk']);
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
									$pasokdetail = $db->table("SELECT a.idProduk, b.idProduksi, b.tanggalSelesaiProduksi,  c.kodeProduk, c.namaProduk, c.HargaAsli, d.size, d.tipeUkur, e.jenisProduk, a.qty, a.idSize
										FROM produksidetail AS a
										LEFT JOIN produksi AS b ON a.idProduksi = b.idProduksi
										LEFT JOIN produk AS c ON a.idProduk = c.idProduk
										LEFT JOIN sizeproduk AS d ON d.idSize = a.idSize
										LEFT JOIN jenisProduk AS e ON c.idJenisProduk = e.idJenis
										WHERE b.tanggalSelesaiProduksi LIKE '%s' AND b.idProduksi = '%s' AND a.idProduk = '%s'", $_REQUEST['tanggalSelesaiProduksi'],$_REQUEST['idProduksi'], $_REQUEST['idProduk']);
								?>
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Tanggal Produk Masuk</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Size</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Qty</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($pasokdetail as $k=>$v):?>
										<tr role="row">
											<td class="sorting_1"><?=Template::format($v->tanggalSelesaiProduksi,"date");?></td>
											<td class="sorting_1"><?=$v->size;?></td>
											<td><?=$v->qty;?></td>
										</tr>
										<?php endforeach;?>
										
									</tbody>
								</table>
								<a class="btn btn-default" href="?mode=view&tanggalSelesaiProduksi=<?=$_REQUEST['tanggalSelesaiProduksi'];?>">Back</a>
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
				Master Produksi Produk
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formProduksi.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Produksi</button>
			</a>
			<a style="float: left;margin-left:5px !important;margin-bottom: 10px !important;" href="">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan</button>
			</a>
			<form class="col-md-6 pull-right"action="?mode=view" method="post">
				<div class="col-sm-5 form-group">
					<select name="urut" class="form-control">
						<option value="">Urutkan sesuai</option>
						<option value="namaProduk">Nama Produk</option>
						<option value="kodeProduk">Kode Produk</option>
						<option value="hargaAsli">Harga Produk</option>
					</select>
				</div>
				<div class="col-sm-5 form-group">
					<div class="input-group date">
					<div class="input-group-addon">
						<i class="fa fa-calendar"></i>
					</div>
					<input name="tanggalSupply" type="text" class="form-control pull-right" id="datepicker">
					</div>
				</div>
				<button class="col-sm-2 btn btn-default" type="submit" value="Sort" name="submit">Sort</button>
			</form>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-8">
					<div class="box box-solid">
						<div class="box-header with-border">
							<i class="fa  fa-arrows-v"></i>
				
							<h3 class="box-title">Sort Produksi</h3>
						</div>
						<div class="box-body">
							<ul style="padding:0 3px !important;list-style:none;">
								<li style="display:inline;">
									<a href="master-produksi.php">All | </a>
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
							$bulanpasok = substr($tanggalMasuk,5,2).' Tahun '.substr($tanggalMasuk,0,4);
							?>
							<h3 class="box-title">Data Produksi Bulan <?=$bulanpasok;?></h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Tanggal Masuk</th>
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Kode Produk</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Nama Produk</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Platform(s): activate to sort column ascending" style="width: 197px;">Jenis</th>
											<th width="5%"class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Engine version: activate to sort column ascending" style="width: 154px;">Qty</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending" style="width: 112px;">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($dataproduk->data as $k=>$v):?>
										<tr role="row">
											
											<td class="sorting_1"><?= substr($v->tanggalSelesaiProduksi,8,2).'-'.substr($v->tanggalSelesaiProduksi,5,2).'-'.substr($v->tanggalSelesaiProduksi,0,4);?></td>
											<td class="sorting_1"><?=$v->kodeProduk;?></td>
											<td><?=$v->namaProduk;?></td>
											<td ><?=$v->jenisProduk;?></td>
											<td width="5%"><?=$v->totalproduksi;?></td>
											<td>
											
											<?php if (in_array('MANAGE_PASOK', $hakAkses[$_SESSION['hak']])):?>
												<a class="btn btn-default" href="formProduksi.php?mode=edit&idProduksi=<?=$v->idProduksi;?>&tanggalSelesaiProduksi=<?=$v->tanggalSelesaiProduksi;?>">Edit</a>
											<?php endif; ?>
												<a class="btn btn-default" href="?mode=view&idProduksi=<?=$v->idProduksi;?>&idProduk=<?=$v->idProduk;?>&tanggalSelesaiProduksi=<?=$v->tanggalSelesaiProduksi;?>">Detail</a>
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