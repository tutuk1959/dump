<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "opname.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;

//selectBoxToko
$selectToko = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Form Perubahan Stok Produk
			</h1>
			<form class="col-md-6 pull-right" action="formOpnameDetail.php" method="post">
				<div class="col-sm-5 form-group">
					<input type="hidden" name="mode" value="<?= ($_REQUEST['mode'] == 'insert') ? 'insert' : 'edit'?>"/>
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
					<input placeholder="Bulan Opname" name="tanggalOpname" type="text" class="form-control pull-right" id="datepicker">
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