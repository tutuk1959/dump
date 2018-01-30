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
		ORDER BY a.namaProduk";
if ($_REQUEST['searchProduk'] =='Cari'){
	$search = $_REQUEST['produk'];
	$paginationrecords = "SELECT a.namaProduk, a.idProduk, a.kodeProduk, b.jenisProduk, a.hargaAsli
	FROM produk AS a
	LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
	WHERE a.kodeProduk LIKE '%".$search."%'
	OR a.namaProduk LIKE '%".$search."%'
	ORDER BY a.namaProduk";
}

if ($_REQUEST['sort']){
	$sort = $_REQUEST['sort'];
	$paginationrecords = "SELECT a.namaProduk, a.idProduk, a.kodeProduk, b.jenisProduk, a.hargaAsli
	FROM produk AS a
	LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
	WHERE a.idJenisProduk = '".$sort."'
	ORDER BY a.namaProduk";
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


//selectBoxToko
$selectToko = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Perubahan Stok Produk
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formOpname.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Opname</button>
			</a>
			<form class="col-md-7 pull-right" action="opnamedetail.php" method="post">
				<div class="col-sm-5 form-group">
					<input type="hidden" name="mode" value="view"/>
					<select name="toko" class="form-control">
						<option value="">Pilih Toko / Outlet</option>
						<?php foreach($selectToko as $k=>$v) :?>
							<option value="<?=$v->idOutlet;?>"><?=$v->namaOutlet;?></option>
						<?php endforeach;?>
					</select>
				</div>
				<div class="col-sm-5 form-group">
					<div class="input-group date">
					<div class="input-group-addon">
						<i class="fa fa-calendar"></i>
					</div>
					<input name="tanggalOpname" type="text" class="form-control pull-right" id="datepicker">
					</div>
				</div>
				<button class="col-sm-2 btn btn-default" type="submit" value="Sort" name="submit">Sort</button>
			</form>
		</section>
	</div>
	<script>
	//Date picker
	$('#datepicker').datepicker({
		autoclose: true,
		dateFormat : 'yy-mm-dd'
	});
	</script>
<?php Template::foot();?>