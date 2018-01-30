<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "outlet.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;
$paginationrecords = "SELECT a.namaOutlet, a.alamat, a.telp,a.idOutlet
		FROM outlet AS a
		ORDER BY a.idOutlet";
if ($_REQUEST['searchOutlet'] =='Cari'){
	$search = $_REQUEST['outlet'];
	$paginationrecords = "SELECT a.namaOutlet, a.alamat, a.telp,a.idOutlet
	FROM outlet AS a
	WHERE a.namaOutlet LIKE '%".$search."%'
	ORDER BY a.idOutlet";
}

$paginator = new Paginator($db, $paginationrecords);
$dataproduk =$paginator->getData($limit, $page);
//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$carioutlet = $db->table("SELECT a.namaOutlet, a.idOutlet
		FROM outlet AS a
		WHERE a.namaOutlet LIKE '%$key%'
		ORDER BY a.namaOutlet");
	die(json_encode($carioutlet));
}

Template::head($db,$file, $hakAkses);;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<?php if( isset($_REQUEST['mode']) && $_REQUEST['mode'] = 'view' && isset($_REQUEST['idOutlet']) ):?>
	<?php 
		$dataOutletDetail = $db->row("SELECT a.idOutlet, a.namaOutlet, a.alamat, a.telp,a.foto
		FROM outlet AS a
		WHERE a.idOutlet = '%s'", $_REQUEST['idOutlet']);
	?>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Data Produk <?=$dataOutletDetail->namaOutlet;?>
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formProduk.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Produk</button>
			</a>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-4">
					
				</div>
				<div class="col-lg-8 pull-right" style="margin-bottom:10px !important;">
					<form action="outlet.php" method="post">
						<div class="input-group input-group-sm">
							<input style="width:100% !important;" placeholder="Search Outlet" name="outlet" id="outlet-autocomplete" type="text" class="form-control">
							<span class="input-group-btn">
								<input name="searchOutlet" value ="Cari" type="submit" class="btn btn-info btn-flat"/>
							</span>
							<div data-id="outlet-template" id="results">
								<div data-id="search-outlet" class="item"><div data-id="outlet-krik"></div></div>
							</div>
							<input type="hidden" name="idoutlet" data-id="idoutlet-krik"/>
						</div>
					</form>
				</div>
			</div>
			<div class="row">
				 <div class="col-md-12">
					<!-- Profile Image -->
					<div class="box box-primary">
						<div class="box-body box-profile">
							<?php if (isset($dataOutletDetail->foto)):?>
								<img class="profile-user-img img-responsive img-circle" src="classes/<?=$file->download($dataOutletDetail->foto);?>" alt="Foto Produk">
							<?php else: ?>
								<img class="profile-user-img img-responsive img-circle" src="dist/img/clothes_512pxGREY.png" alt="Foto Produk">
							<?php endif; ?>
							<h3 class="profile-username text-center"><?=$dataOutletDetail->namaOutlet;?></h3>
						
							<p class="text-muted text-center"><?=$dataOutletDetail->alamat;?></p>
							<p class="text-muted text-center"><?=$dataOutletDetail->telp;?></p>
						</div>
						<a class="btn btn-default" href="outlet.php">Back</a>
					</div>
				</div>
			</div>
		</section>
	</div>
	<?php else: ?>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Manajemen Master Data Outlet
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formOutlet.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Outlet</button>
			</a>
			<a style="float: right;margin-bottom: 10px !important;" href="">
				<button type="button" class="btn btn-default pull-right"><i class="fa  fa-file-code-o"></i> Export Laporan</button>
			</a>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-4 ">
				</div>
				<div class="col-lg-8 pull-right" style="margin-bottom:10px !important;">
					<form action="outlet.php" method="post">
						<div class="input-group input-group-sm">
							<input style="width:100% !important;" placeholder="Search Outlet" name="outlet" id="outlet-autocomplete" type="text" class="form-control">
							<span class="input-group-btn">
								<input name="searchOutlet" value ="Cari" type="submit" class="btn btn-info btn-flat"/>
							</span>
							<div data-id="outlet-template" id="results">
								<div data-id="search-outlet" class="item"><div data-id="outlet-krik"></div></div>
							</div>
							<input type="hidden" name="idoutlet" data-id="idoutlet-krik"/>
						</div>
					</form>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="box">
						<div class="box-header">
							<h3 class="box-title">Data Outlet</h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Nama Outlet</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Platform(s): activate to sort column ascending" style="width: 197px;">Alamat</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Engine version: activate to sort column ascending" style="width: 154px;">Telp</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending" style="width: 112px;">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($dataproduk->data as $k=>$v):?>
										<tr role="row">
											
											<td><?=$v->namaOutlet;?></td>
											<td><?=$v->alamat;?></td>
											<td><?=$v->telp;?></td>
											<td>
											
											<?php if (in_array('MANAGE_OUTLET', $hakAkses[$_SESSION['hak']])):?>
												<a class="btn btn-default" href="formProduk.php?mode=edit&idOutlet=<?=$v->idOutlet;?>">Edit</a>
											<?php endif; ?>
												<a class="btn btn-default" href="?mode=view&idOutlet=<?=$v->idOutlet;?>">Detail</a>
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
		var idoutlet = "";
		$(function() {
			
			var template_outlet_parent = $('[data-id="search-outlet"]').parent();
			var template_outlet = $('[data-id="search-outlet"]').detach();
			$("#outlet-autocomplete").keyup(function() {
				var keyword = $("#outlet-autocomplete").val();
				if (keyword.length >= MIN_LENGTH) {
					$.getJSON('outlet.php', {cari:keyword}, function(d){
						$(template_outlet_parent).show().html('');
						if (d.length != 0) {
							$.each(d, function(k,v){
								var row = $(template_outlet).clone().appendTo(template_outlet_parent);
								row.find('[data-id="outlet-krik"]').html(v.namaOutlet);
								row.find('[data-id="idoutlet-krik"]').val(v.idOutlet);
								kodewilayah = v.idOutlet;
							});
							
							$('.item').click(function() {
								var text = $(this).find('[data-id="outlet-krik"]').html();
								$('#outlet-autocomplete').val(text);
								$('[data-id="idoutlet-krik"]').val(kodewilayah);
								$(template_outlet_parent).hide();
							});
						} else {
							var row = $(template_outlet).clone().appendTo(template_outlet_parent);
							row.find('[data-id="outlet-krik"]').html('Data Outlet Tidak Ditemukan');
							$('.item').click(function() {
								$(template_outlet_parent).hide();
							});
						}
				});
				}
			});
		});
	</script>
<?php Template::foot();?>