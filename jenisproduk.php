<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "jenisproduk.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;
$paginationrecords = "SELECT a.idJenis, a.jenisProduk
		FROM jenisproduk AS a
		ORDER BY a.idJenis";
if ($_REQUEST['searchjenisproduk'] =='Cari'){
	$search = $_REQUEST['jenisproduk'];
	$paginationrecords = "SELECT a.idJenis, a.jenisProduk
	FROM jenisproduk AS a
	WHERE a.jenisProduk LIKE '%".$search."%'
	ORDER BY a.idJenis";
}

//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$carijenis = $db->table("SELECT a.idJenis, a.jenisProduk
		FROM jenisproduk AS a
		WHERE a.jenisProduk LIKE '%$key%'
		ORDER BY a.idJenis");
	die(json_encode($carijenis));
}


$paginator = new Paginator($db, $paginationrecords);
$dataproduk =$paginator->getData($limit, $page);

//sortitems
$sortProdukItems = $db->table("SELECT DISTINCT a.idJenis, a.jenisProduk,
								(SELECT COUNT(*) 
								FROM produk AS a1
								LEFT JOIN jenisproduk AS b1 ON b1.idJenis = a1.idJenisProduk
								WHERE a1.idJenisProduk = a.idJenis
								GROUP BY a1.idJenisProduk) jumlah
							FROM jenisproduk AS a
							ORDER BY a.idJenis");

Template::head($db,$file, $hakAkses);;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Manajemen Master Data Jenis Produk
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formJenis.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Jenis Produk</button>
			</a>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-8">
					<div class="box box-solid">
						<div class="box-header with-border">
							<i class="fa  fa-arrows-v"></i>
				
							<h3 class="box-title">Sort Jenis</h3>
						</div>
						<div class="box-body">
							<ul style="padding:0 3px !important;list-style:none;">
								<li style="display:inline;">
									<a href="produk.php">All | </a>
								</li>
							<?php foreach($sortProdukItems as $k=>$v):?>
								<li style="display:inline;"> 
									<a href="produk.php?sort=<?=$v->idJenis;?>"><?=$v->jenisProduk; ?> (<?=($v->jumlah != null) ? $v->jumlah : 0;?>) |</a>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
				<div class="col-lg-4 pull-right" style="margin-bottom:10px !important;">
					<form action="jenisproduk.php" method="post">
						<div class="input-group input-group-sm">
							<input style="width:100% !important;" placeholder="Search Jenis" name="jenisproduk" id="jenisproduk-autocomplete" type="text" class="form-control">
							<span class="input-group-btn">
								<input name="searchjenisproduk" value ="Cari" type="submit" class="btn btn-info btn-flat"/>
							</span>
							<div data-id="jenisproduk-template" id="results">
								<div data-id="search-jenisproduk" class="item"><div data-id="jenisproduk-krik"></div></div>
							</div>
							<input type="hidden" name="idjenisproduk" data-id="idjenisproduk-krik"/>
						</div>
					</form>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="box">
						<div class="box-header">
							<h3 class="box-title">Data Jenis Produk</h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Jenis Produk</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending" style="width: 112px;">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($dataproduk->data as $k=>$v):?>
										<tr role="row">
											<td><?=$v->jenisProduk;?></td>
											<td>
											
											<?php if (in_array('MANAGE_JENIS', $hakAkses[$_SESSION['hak']])):?>
												<a class="btn btn-default" href="formJenis.php?mode=edit&idJenis=<?=$v->idJenis;?>">Edit</a>
											<?php endif; ?>
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
	<script>
		//Search Jenis Produk
		var MIN_LENGTH = 1;
		var idjenisproduk = "";
		$(function() {
			
			var template_jenisproduk_parent = $('[data-id="search-jenisproduk"]').parent();
			var template_jenisproduk = $('[data-id="search-jenisproduk"]').detach();
			$("#jenisproduk-autocomplete").keyup(function() {
				var keyword = $("#jenisproduk-autocomplete").val();
				if (keyword.length >= MIN_LENGTH) {
					$.getJSON('jenisproduk.php', {cari:keyword}, function(d){
						$(template_jenisproduk_parent).show().html('');
						if (d.length != 0) {
							$.each(d, function(k,v){
								var row = $(template_jenisproduk).clone().appendTo(template_jenisproduk_parent);
								row.find('[data-id="jenisproduk-krik"]').html(v.jenisProduk);
								row.find('[data-id="idjenisproduk-krik"]').val(v.idJenis);
								kodewilayah = v.idJenis;
							});
							
							$('.item').click(function() {
								var text = $(this).find('[data-id="jenisproduk-krik"]').html();
								$('#jenisproduk-autocomplete').val(text);
								$('[data-id="idjenisproduk-krik"]').val(kodewilayah);
								$(template_jenisproduk_parent).hide();
							});
						} else {
							var row = $(template_jenisproduk).clone().appendTo(template_jenisproduk_parent);
							row.find('[data-id="jenisproduk-krik"]').html('Data Jenis Produk Tidak Ditemukan');
							$('.item').click(function() {
								$(template_jenisproduk_parent).hide();
							});
						}
				});
				}
			});
		});
	</script>
<?php Template::foot();?>