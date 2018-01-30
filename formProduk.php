<?php
session_start();
include "classes/db.class.php";
include  "classes/hak-akses.inc.php";
include "classes/template.class.php";
include "classes/class.file.php";
include "pagination.php";
include "produk.function.php";
$db = new DB();
$file = new File();

if ($_REQUEST['mode'] == 'insert'){
	kickInsert($hakAkses[$_SESSION['hak']]);
} else if ($_REQUEST['mode'] == 'edit'){
	kickManage($hakAkses[$_SESSION['hak']]);
}

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

//sortitems
$sortProdukItems = $db->table("SELECT a.idJenis, a.jenisProduk,
								(SELECT COUNT(*) 
								FROM produk AS a1
								LEFT JOIN jenisproduk AS b1 ON b1.idJenis = a1.idJenisProduk
								WHERE a1.idJenisProduk = a.idJenis
								GROUP BY a1.idJenisProduk) jumlah
							FROM jenisproduk AS a
							ORDER BY a.idJenis");

//jenisProdukForm 
$jenisProduk = $db->table("SELECT a.idJenis, a.jenisProduk FROM jenisproduk AS a ORDER BY a.idJenis");

//prefillDataUbah
$produkPrefill = $db->row("SELECT a.kodeProduk, a.namaProduk, a.idJenisProduk,b.jenisProduk, a.foto,a.hargaAsli
							FROM produk AS a
							LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
							WHERE a.idProduk = '%s'",$_REQUEST['idProduk']);
							
//insertFotoFormSubmit
if ($_REQUEST['mode'] == 'insert' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['kodeProduk'],$_REQUEST['namaProduk'],$_REQUEST['jenisProduk'],$_REQUEST['hargaAsli'], $error);
	if (!$error){
		$db->exec("INSERT INTO produk(kodeProduk,namaProduk,idJenisProduk,foto,hargaAsli) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['kodeProduk'],$_REQUEST['namaProduk'],$_REQUEST['jenisProduk'],$file->upload($_FILES['change-foto']),$_REQUEST['hargaAsli']);
		$newProdukId = $db->insertID();
		$produkPrefill = $db->row("SELECT a.kodeProduk, a.namaProduk, a.idJenisProduk,b.jenisProduk, a.foto,a.hargaAsli
			FROM produk AS a
			LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
			WHERE a.idProduk = '%s'",$newProdukId);
		$message = 'Data produk' .$produkPrefill->namaProduk.' ditambah.';
	} else {
		$error[] = 'Gagal menambah data produk';
	}
	
	
} 
if ($_REQUEST['mode'] == 'edit' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['kodeProduk'],$_REQUEST['namaProduk'],$_REQUEST['jenisProduk'],$_REQUEST['hargaAsli'], $error);
	if (!$error){
		$db->exec("UPDATE produk SET kodeProduk = '%s', namaProduk = '%s', idJenisProduk = '%s', foto = '%s', hargaAsli = '%s' WHERE idProduk = '%s'",$_REQUEST['kodeProduk'], $_REQUEST['namaProduk'], $_REQUEST['jenisProduk'], $file->upload($_FILES['change-foto']),$_REQUEST['hargaAsli'], $_REQUEST['idProduk']);
		$message = 'Data produk diubah..';
	} else {
		$error[] = 'Gagal mengupdate data produk';
	}
	
	$produkPrefill = $db->row("SELECT a.kodeProduk, a.namaProduk, a.idJenisProduk,b.jenisProduk, a.foto,a.hargaAsli
	FROM produk AS a
	LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
	WHERE a.idProduk = '%s'",$_REQUEST['idProduk']);
}

Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<?php ($_REQUEST['mode'] == 'insert')? $msg = 'Tambah Produk Baru' :$msg='Ubah Data Produk';?>
			<h1 style="float: left;margin-right: 10px !important;">
				<?=$msg;?>
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
									<a href="produk.php?sort=<?=$v->idJenisProduk;?>"><?=$v->jenisProduk; ?> (<?=($v->jumlah != null) ? $v->jumlah : 0;?>) |</a>
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
				<div class="col-md-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Form Produk</h3>
						</div>
						<form method="post" class="form-horizontal" enctype="multipart/form-data">
							<div class="box-body">
								<div class="form-group">
									<label  name="kodeProduk"class="col-sm-2 control-label">Kode Produk</label>
								
									<div class="col-sm-10">
										<input type="hidden" name="idProduk" value="<?=$_REQUEST['idProduk'];?>"style="display:none;"/>
										<input type="text" name="kodeProduk" class="form-control" id="inputEmail3" placeholder="Kode Produk" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($produkPrefill->kodeProduk)) ? $produkPrefill->kodeProduk : '' ); ?>" />
									</div>
								</div>
								<div class="form-group">
									<label  class="col-sm-2 control-label">Nama Produk</label>
								
									<div class="col-sm-10">
										<input type="text" name="namaProduk" class="form-control" id="inputPassword3" placeholder="Nama Produk" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($produkPrefill->namaProduk)) ? $produkPrefill->namaProduk : '') ;?>">
									</div>
								</div>
								<div class="form-group">
									<label  class="col-sm-2 control-label">Jenis Produk</label>
									<div class="col-sm-10">
										<select name="jenisProduk"class="form-control">
										<?php foreach ($jenisProduk as $k=>$v):?>
											<option value="<?=$v->idJenis; ?>" <?=(($_REQUEST['mode'] == 'edit') && (isset($produkPrefill->idJenisProduk)) && ($produkPrefill->idJenisProduk == $v->idJenis)? 'selected' : '');?> ><?=$v->jenisProduk; ?></option>
										<?php endforeach ;?>
										</select>
									</div>
	
								</div>
								<div class="form-group">
									<label  class="col-sm-2 control-label">Gambar Produk</label>
									<div class="col-sm-10">
										<img src="classes/<?=(($_REQUEST['mode'] == 'edit') && (isset($produkPrefill->foto)) ? $file->download($produkPrefill->foto) : 'dist/img/clothes_512pxGREY.png') ;?>" width="150" height="200"  alt="user-profile-" />
										<input value="<?=(($_REQUEST['mode'] == 'edit') && (isset($produkPrefill->foto)) ? $file->download($produkPrefill->foto) : '') ; ?>" name="change-foto" id="input-foto" type="file" class="btn btn-default"/>
									</div>
									
								</div>
								<div class="form-group">
									<label  class="col-sm-2 control-label">Harga Asli</label>
								
									<div class="col-sm-10">
										<input value="<?=(($_REQUEST['mode'] == 'edit') && (isset($produkPrefill->hargaAsli)) ? $produkPrefill->hargaAsli : '') ;?>"type="text" name="hargaAsli" class="form-control" id="inputPassword3" placeholder="Harga Asli">
									</div>
								</div>
							</div>
							<!-- /.box-body -->
							<div class="box-footer">
								<a href="produk.php" class="btn btn-info pull-left">Back</a>
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