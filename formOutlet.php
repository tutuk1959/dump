<?php
session_start();
include "classes/db.class.php";
include  "classes/hak-akses.inc.php";
include "classes/template.class.php";
include "classes/class.file.php";
include "pagination.php";
include "outlet.function.php";
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

if ($_REQUEST['mode'] == 'insert' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['namaOutlet'],$_REQUEST['alamat'], $error);
	if (!$error){
		$db->exec("INSERT INTO outlet(namaOutlet,alamat,telp,foto) VALUES ('%s','%s','%s','%s')",$_REQUEST['namaOutlet'],$_REQUEST['alamat'],$_REQUEST['telp'],$file->upload($_FILES['change-foto']));
		$newOutletId = $db->insertID();
		$produkPrefill = $db->row("SELECT a.namaOutlet, a.alamat, a.telp, a.foto
			FROM outlet AS a
			WHERE a.idOutlet = '%s'",$newOutletId);
		$message = 'Data outlet ' .$newProdukData->namaOutlet.' ditambah.';
	} else {
		$error[] = 'Gagal menambah data outlet';
	}
	
	
} 
if ($_REQUEST['mode'] == 'edit' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['namaOutlet'],$_REQUEST['alamat'], $error);
	if (!$error){
		$db->exec("UPDATE outlet SET namaOutlet = '%s', alamat = '%s', telp = '%s', foto = '%s' WHERE idOutlet = '%s'", $_REQUEST['namaOutlet'], $_REQUEST['alamat'],$_REQUEST['telp'],$file->upload($_FILES['change-foto']),$_REQUEST['idOutlet']);
		$message = 'Data outlet diubah..';
	} else {
		$error[] = 'Gagal mengupdate data outlet';
	}
	
	$produkPrefill = $db->row("SELECT a.namaOutlet, a.alamat, a.telp, a.foto
	FROM outlet AS a
	WHERE a.idOutlet = '%s'",$_REQUEST['idOutlet']);
}

Template::head($db,$file, $hakAkses);;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<?php ($_REQUEST['mode'] == 'insert')? $msg = 'Tambah Outlet Baru' :$msg='Ubah Outlet Produk';?>
			<h1 style="float: left;margin-right: 10px !important;">
				<?=$msg;?>
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
					<?php showMessageAndErrors($message,$error);?>
					<div class="box box-info">
						<div class="box-header with-border">
							<h3 class="box-title">Form Outlet</h3>
						</div>
						<form method="post" class="form-horizontal" enctype="multipart/form-data">
							<div class="box-body">
								<div class="form-group">
									<label  class="col-sm-2 control-label">Nama Outlet</label>
								
									<div class="col-sm-10">
										<input type="text" name="namaOutlet" class="form-control" id="inputPassword3" placeholder="Nama Outlet" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($outletPrefill->namaOutlet)) ? $outletPrefill->namaOutlet : '') ;?>">
									</div>
								</div>
								
								<div class="form-group">
									<label  class="col-sm-2 control-label">Alamat</label>
								
									<div class="col-sm-10">
										<input type="text" name="alamat" class="form-control" id="inputPassword3" placeholder="Alamat" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($outletPrefill->alamat)) ? $outletPrefill->alamat : '') ;?>">
									</div>
								</div>
								
								<div class="form-group">
									<label  class="col-sm-2 control-label">Telp</label>
								
									<div class="col-sm-10">
										<input type="text" name="telp" class="form-control" id="inputPassword3" placeholder="Nomor Telepon" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($outletPrefill->telp)) ? $outletPrefill->telp : '') ;?>">
									</div>
								</div>
								
								<div class="form-group">
									<label  class="col-sm-2 control-label">Gambar Produk</label>
									<div class="col-sm-10">
										<img src="classes/<?=(($_REQUEST['mode'] == 'edit') && (isset($outletPrefill->foto)) ? $file->download($outletPrefill->foto) : 'dist/img/clothes_512pxGREY.png') ;?>" width="150" height="200"  alt="user-profile-" />
										<input value="<?=(($_REQUEST['mode'] == 'edit') && (isset($outletPrefill->foto)) ? $file->download($outletPrefill->foto) : '') ; ?>" name="change-foto" id="input-foto" type="file" class="btn btn-default"/>
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