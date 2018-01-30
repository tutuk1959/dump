<?php
session_start();
include "classes/db.class.php";
include  "classes/hak-akses.inc.php";
include "classes/template.class.php";
include "classes/class.file.php";
include "pagination.php";
include "user.function.php";
$db = new DB();
$file = new File();

if ($_REQUEST['mode'] == 'insert'){
	kickInsert($hakAkses[$_SESSION['hak']]);
} else if ($_REQUEST['mode'] == 'edit'){
	kickManage($hakAkses[$_SESSION['hak']]);
}
//selectBoxToko
$selectToko = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");

//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$cariuser = $db->table("SELECT a.idUser, a.idOutlet, a.nama, a.username, a.password, a.profilepict, a.jabatan,b.namaOutlet
		FROM user AS a
		LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
		WHERE a.nama LIKE '%$key%'
		ORDER BY a.jabatan");
	die(json_encode($cariuser));
}

//sortitems
$sortUserItems = $db->table("SELECT DISTINCT a.jabatan, a.idUser, a.username,
								(SELECT COUNT(a1.idUser) 
								FROM user AS a1
								WHERE a1.jabatan = a.jabatan
								GROUP BY a1.jabatan
								) jumlah
							FROM user AS a
							GROUP BY a.jabatan
							ORDER BY a.jabatan");

//prefillUbah
$userPrefill = $db->row("SELECT a.idUser, a.idOutlet, a.nama,a.username,a.password, a.jabatan, b.namaOutlet
FROM user AS a
LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
WHERE a.idUser = '%s'",$_REQUEST['idUser']);
							
//insertFotoFormSubmit
if ($_REQUEST['mode'] == 'insert' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['namaUser'],$_REQUEST['username'],$_REQUEST['password'],$_REQUEST['passwordagain'],$_REQUEST['jabatan'],$_REQUEST['toko'],$error);
	if (!$error){
		if ($_REQUEST['password'] == $_REQUEST['passwordagain']){
			$db->exec("INSERT INTO user(idOutlet,nama,username,password,hak,profilepict,jabatan) VALUES ('%s','%s','%s','%s','%s','%s','%s')",$_REQUEST['toko'],$_REQUEST['namaUser'],$_REQUEST['username'],$_REQUEST['password'],$_REQUEST['jabatan'],$file->upload($_FILES['change-foto']),$_REQUEST['jabatan']);
			$newUserId = $db->insertID();
			$message = 'Data user berhasil ditambah';
		} else{
			$error[] = 'Password tidak cocok !';
		}
	} else {
		$error[] = 'Gagal menambah data produk';
	}
	$userPrefill = $db->row("SELECT a.idUser, a.idOutlet, a.nama,a.username,a.password, a.jabatan, b.namaOutlet
	FROM user AS a
	LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
	WHERE a.idUser = '%s'",$newUserId);
	
} 
if ($_REQUEST['mode'] == 'edit' && $_REQUEST['submit'] == 'Simpan'){
	_validation($_REQUEST['namaUser'],$_REQUEST['username'],$_REQUEST['password'],$_REQUEST['passwordagain'],$_REQUEST['jabatan'],$_REQUEST['toko'],$error);
	if (!$error){
		if ($_REQUEST['password'] == $_REQUEST['passwordagain']){
			$db->exec("UPDATE user SET nama = '%s', idOutlet = '%s', username = '%s', password = '%s', hak = '%s',profilepict = '%s',jabatan = '%s'WHERE idUser = '%s'",$_REQUEST['namaUser'], $_REQUEST['toko'], $_REQUEST['username'],$_REQUEST['password'],$_REQUEST['jabatan'],$file->upload($_FILES['change-foto']),$_REQUEST['jabatan'], $_REQUEST['idUser']);
			$message = 'Data produk diubah..';
		} else {
			$error[] = 'Password tidak cocok!';
		}
	} else {
		$error[] = 'Gagal mengupdate data produk';
	}
	
	$userPrefill = $db->row("SELECT a.idUser, a.idOutlet, a.nama,a.username,a.password, a.jabatan, b.namaOutlet
	FROM user AS a
	LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
	WHERE a.idUser = '%s'",$_REQUEST['idUser']);
}

Template::head($db,$file, $hakAkses);;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<?php ($_REQUEST['mode'] == 'insert')? $msg = 'Tambah User Baru' :$msg='Ubah User Produk';?>
			<h1 style="float: left;margin-right: 10px !important;">
				<?=$msg;?>
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formUser.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah User</button>
			</a>
			<div style="clear:both;"></div>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-8">
					<div class="box box-solid">
						<div class="box-header with-border">
							<i class="fa  fa-arrows-v"></i>
				
							<h3 class="box-title">Sort User</h3>
						</div>
						<div class="box-body">
							<ul style="padding:0 3px !important;list-style:none;">
								<li style="display:inline;">
									<a href="users.php">All | </a>
								</li>
							<?php foreach($sortUserItems as $k=>$v):?>
								<li style="display:inline;"> 
									<a href="users.php?sort=<?=$v->jabatan;?>"><?=($v->jabatan == 0 ? 'Super System Admin' : ($v->jabatan == 1 ? 'Manager' : ($v->jabatan == 2 ? 'Admin' : ($v->jabatan == 3 ? 'Stock Checker' : ($v->jabatan == 4 ? 'Staff' : ''))))); ?> (<?=$v->jumlah;?>) |</a>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
				<div class="col-lg-4 pull-right" style="margin-bottom:10px !important;">
					<form action="users.php" method="post">
						<div class="input-group input-group-sm">
							<input style="width:100% !important;" placeholder="Search User" name="user" id="user-autocomplete" type="text" class="form-control">
							<span class="input-group-btn">
								<input name="searchUser" value ="Cari" type="submit" class="btn btn-info btn-flat"/>
							</span>
							<div data-id="user-template" id="results">
								<div data-id="search-user" class="item"><div data-id="user-krik"></div></div>
							</div>
							<input type="hidden" name="iduser" data-id="iduser-krik"/>
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
									<label class="col-sm-2 control-label">Nama</label>
								
									<div class="col-sm-10">
										<input type="hidden" name="idUser" value="<?=$_REQUEST['idUser'];?>"style="display:none;"/>
										<input type="text" name="namaUser" class="form-control" id="inputEmail3" placeholder="Nama User" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($userPrefill->nama)) ? $userPrefill->nama : '' ); ?>" />
									</div>
								</div>
								<div class="form-group">
									<label  name="kodeProduk"class="col-sm-2 control-label">Outlet</label>
									<div class="col-sm-10">
										<select name="toko" class="form-control">
											<?php foreach($selectToko as $k=>$v) :?>
												<option value="<?=$v->idOutlet;?>" <?=($userPrefill->idOutlet == $v->idOutlet || $_REQUEST['idOutlet'] == $v->idOutlet) ? 'selected' : '';?> ><?=$v->namaOutlet;?></option>
											<?php endforeach;?>
										</select>
									</div>
								</div>
								
								<div class="form-group">
									<label  class="col-sm-2 control-label">Username</label>
								
									<div class="col-sm-10">
										<input type="text" name="username" class="form-control" id="inputPassword3" placeholder="Username" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($userPrefill->username)) ? $userPrefill->username : '') ;?>">
									</div>
								</div>
								<div class="form-group">
									<label  class="col-sm-2 control-label">Password</label>
								
									<div class="col-sm-10">
										<input type="password" name="password" class="form-control" id="inputPassword3" placeholder="Password" value="<?=(($_REQUEST['mode'] == 'edit') && (isset($userPrefill->password)) ? $userPrefill->password : '') ;?>">
									</div>
								</div>
								<div class="form-group">
									<label  class="col-sm-2 control-label">Konfirmasi Password</label>
								
									<div class="col-sm-10">
										<input type="password" name="passwordagain" class="form-control" id="inputPassword3" placeholder="Ketikan Kembali Password">
									</div>
								</div>
								<div class="form-group">
									<label  class="col-sm-2 control-label">Gambar Profile</label>
									<div class="col-sm-10">
										<img src="classes/<?=(($_REQUEST['mode'] == 'edit') && (isset($userPrefill->profilepict)) ? $file->download($userPrefill->userPrefill) : 'dist/img/avatar5.png') ;?>" width="150" height="200"  alt="user-profile-" />
										<input value="<?=(($_REQUEST['mode'] == 'edit') && (isset($userPrefill->profilepict)) ? $userPrefill->download($userPrefill->profilepict) : '') ; ?>" name="change-foto" id="input-foto" type="file" class="btn btn-default"/>
									</div>
									
								</div>
								<div class="form-group">
									<label  class="col-sm-2 control-label">Jabatan</label>
									<div class="col-sm-10">
										<select name="jabatan" class="form-control">
											<?php for($i=0;$i<=4;$i++):?>
												<option value="<?=$i?>" <?=($userPrefill->jabatan == $i) ? 'selected' : '';?>><?=($i == 0 ? 'Super System Admin' : ($i == 1 ? 'Manager' : ($i == 2 ? 'Admin' : ($i == 3 ? 'Stock Checker' : ($i== 4 ? 'Staff' : ''))))); ?></option>
											<?php endfor;?>
										</select>
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