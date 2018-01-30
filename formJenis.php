<?php
session_start();
include "classes/db.class.php";
include  "classes/hak-akses.inc.php";
include "classes/template.class.php";
include "classes/class.file.php";
include "pagination.php";
include "jenisproduk.function.php";
$db = new DB();
$file = new File();

if ($_REQUEST['mode'] == 'insert'){
	kickInsert($hakAkses[$_SESSION['hak']]);
} else if ($_REQUEST['mode'] == 'edit'){
	kickManage($hakAkses[$_SESSION['hak']]);
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

//prefillUbah
$jenisPrefill = $db->row("SELECT a.idJenis, a.jenisProduk
FROM jenisproduk AS a
WHERE a.idJenis = '%s'",$_REQUEST['idJenis']);
							
//insertFotoFormSubmit
if ($_REQUEST['mode'] == 'insert' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['jenisProduk'],$error);
	if (!$error){
		$db->exec("INSERT INTO jenisproduk(jenisProduk) VALUES ('%s')",$_REQUEST['jenisProduk']);
		$newJenis = $db->insertID();
		$message = 'Data jenis berhasil ditambah';
	} else {
		$error[] = 'Gagal menambah data jenis produk';
	}
	$jenisPrefill = $db->row("SELECT a.idJenis, a.jenisProduk
	FROM jenisproduk AS a
	WHERE a.idJenis = '%s'",$newJenis);
	
} 
if ($_REQUEST['mode'] == 'edit' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['jenisProduk'],$error);
	if (!$error){
		
		$db->exec("UPDATE jenisproduk SET jenisProduk = '%s' WHERE idJenis = '%s'",$_REQUEST['jenisProduk'], $_REQUEST['idJenis']);
		$message = 'Data jenis diubah..';
	} else {
		$error[] = 'Gagal mengupdate data jenis';
	}
	
	$jenisPrefill = $db->row("SELECT a.idJenis, a.jenisProduk
	FROM jenisproduk AS a
	WHERE a.idJenis = '%s'",$_REQUEST['idJenis']);
}

Template::head($db,$file, $hakAkses);;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<?php ($_REQUEST['mode'] == 'insert')? $msg = 'Tambah Jenis Baru' :$msg='Ubah Jenis Produk';?>
			<h1 style="float: left;margin-right: 10px !important;">
				<?=$msg;?>
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formJenis.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Jenis</button>
			</a>
			<div style="clear:both;"></div>
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
				<div class="col-md-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Form User</h3>
						</div>
						<form method="post" class="form-horizontal" enctype="multipart/form-data">
							<div class="box-body">
								<div class="form-group">
									<label class="col-sm-2 control-label">Jenis</label>
								
									<div class="col-sm-10">
										<input type="hidden" name="idJenis" value="<?=$_REQUEST['idJenis'];?>"style="display:none;"/>
										<input type="text" name="jenisProduk" class="form-control" id="inputEmail3" placeholder="Nama Jenis Produk" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($jenisPrefill->jenisProduk)) ? $jenisPrefill->jenisProduk : '' ); ?>" />
									</div>
								</div>
								
							</div>
							<!-- /.box-body -->
							<div class="box-footer">
								<a href="users.php" class="btn btn-info pull-left">Back</a>
								<button type="submit" value="Simpan" name="submit"class="btn btn-info pull-right">Simpan</button>
							</div>
							<!-- /.box-footer -->
						</form>
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