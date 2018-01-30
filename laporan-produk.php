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

$prefillProduk = $db->table("SELECT a.idProduk,a.kodeProduk,a.namaProduk, a.idJenisProduk, b.jenisProduk, a.hargaAsli
							FROM produk AS a
							LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
							ORDER BY a.namaProduk");
Template::reportHead('Laporan Produk Global','produk.php');;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<div class="content-wrapper" style="position:relative;top:40px !important;">
		<div class="container">
			<section class="content-header"></section>
			<section class="invoice" style="margin:10px 0px !important;">
				<div class="col-xs-12">
					<h2 class="page-header">
						<i class="fa fa-globe"></i> Laporan Produk Global
					</h2>
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
						<tr>
							<th>No</th>
							<th>Kode</th>
							<th>Nama Produk</th>
							<th>Jenis Produk</th>
							<th>Harga</th>
						</tr>
						</thead>
						<tbody>
							<?php foreach ($prefillProduk as $k=>$v):?>
								<tr>
									<td><?=$k+1;?></td>
									<td><?=$v->kodeProduk;?></td>
									<td><?=$v->namaProduk;?></td>
									<td><?=$v->jenisProduk;?></td>
									<td><?='Rp.'.Template::format($v->hargaAsli,"money");?></td>
								<tr>
							<?php endforeach;?>
						</tbody>
					</table>
					</div>
					<!-- /.col -->
				</div>
			</section>
		</div>
	</div>
<?php Template::reportFoot();?>