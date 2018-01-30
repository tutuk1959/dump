<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "user.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;
$paginationrecords = "SELECT a.idUser, a.idOutlet, a.nama, a.username, a.password, a.profilepict, a.jabatan,b.namaOutlet
		FROM user AS a
		LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
		ORDER BY a.jabatan";
if ($_REQUEST['searchUser'] =='Cari'){
	$search = $_REQUEST['user'];
	$paginationrecords = "SELECT a.idUser, a.idOutlet, a.nama, a.username, a.password, a.profilepict, a.jabatan,b.namaOutlet
	FROM user AS a
	LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
	WHERE a.nama LIKE '%".$search."%' OR a.username LIKE '%".$search."%'
	ORDER BY a.jabatan";
}

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

if ($_REQUEST['sort']){
	$sort = $_REQUEST['sort'];
	$paginationrecords = "SELECT a.idUser, a.idOutlet, a.nama, a.username, a.password, a.profilepict, a.jabatan,b.namaOutlet
		FROM user AS a
		LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
		WHERE a.jabatan =".$sort;
	
} 

$paginator = new Paginator($db, $paginationrecords);
$dataproduk =$paginator->getData($limit, $page);

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

Template::head($db,$file, $hakAkses);;
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<?php if( isset($_REQUEST['mode']) && $_REQUEST['mode'] = 'view' && isset($_REQUEST['idUser']) ):?>
	<?php 
		$dataUserDetail = $db->row("SELECT a.idUser, a.idOutlet, a.nama, a.password,a.profilepict, a.jabatan,b.namaOutlet,a.username
		FROM user AS a
		LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
		WHERE a.idUser = '%s'", $_REQUEST['idUser']);
	?>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Data Produk <?=$dataUserDetail->namaOutlet;?>
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formUser.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah User</button>
			</a>
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
									<a href="?sort=<?=$v->jabatan;?>"><?=($v->jabatan == 0 ? 'Super System Admin' : ($v->jabatan == 1 ? 'Manager' : ($v->jabatan == 2 ? 'Admin' : ($v->jabatan == 3 ? 'Stock Checker' : ($v->jabatan == 4 ? 'Staff' : ''))))); ?> (<?=$v->jumlah;?>) |</a>
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
				 <div class="col-md-4">
					<!-- Profile Image -->
					<div class="box box-primary">
						<div class="box-body box-profile">
							<?php if (isset($dataUserDetail->profilepict)):?>
								<img class="profile-user-img img-responsive img-circle" src="classes/<?=$file->download($dataUserDetail->profilepict);?>" alt="Foto Produk">
							<?php else: ?>
								<img class="profile-user-img img-responsive img-circle" src="dist/img/avatar5.png" alt="Foto User">
							<?php endif; ?>
							<h3 class="profile-username text-center"><?=$dataUserDetail->nama;?></h3>
						
							<p class="text-muted text-center"><?=$dataUserDetail->namaOutlet;?></p>
							<p class="text-muted text-center"><?=($dataUserDetail->jabatan == 0 ? 'Super System Admin' : ($dataUserDetail->jabatan == 1 ? 'Manager' : ($dataUserDetail->jabatan == 2 ? 'Admin' : ($dataUserDetail->jabatan == 3 ? 'Stock Checker' : ($dataUserDetail->jabatan == 4 ? 'Staff' : ''))))); ?></p>
						</div>
					</div>
				</div>
				<div class="col-md-8">
					<div class="nav-tabs-custom">
						<ul class="nav nav-tabs">
							 <li class="active"><a href="#outlet" data-toggle="tab">Informasi Credential</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="outlet">
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Username</th>
											<th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending" style="width: 181px;">Password </th>
											
										</tr>
									</thead>
									<tbody>
										<a href="formUser.php?mode=edit&idUser=<?=$dataUserDetail->idUser?>"> Change Profile?</a>
										<tr role="row">
											<td><input type="text" class="form-control" value="<?=$dataUserDetail->username;?>"/></td>
											<td><input type="password" class="form-control" value="<?=$dataUserDetail->password;?>"/> <a href="formUser.php?mode=edit&idUser=<?=$dataUserDetail->idUser?>"> Ganti Password?</a></td>
										</tr>
									</tbody>
								</table>
								<a class="btn btn-default" href="users.php">Back</a>
								<hr />
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
				Manajemen Master Data User
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formUser.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah User</button>
			</a>
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
									<a href="users.php">All | </a>
								</li>
							<?php foreach($sortUserItems as $k=>$v):?>
								<li style="display:inline;"> 
									<a href="?sort=<?=$v->jabatan;?>"><?=($v->jabatan == 0 ? 'Super System Admin' : ($v->jabatan == 1 ? 'Manager' : ($v->jabatan == 2 ? 'Admin' : ($v->jabatan == 3 ? 'Stock Checker' : ($v->jabatan == 4 ? 'Staff' : ''))))); ?> (<?=$v->jumlah;?>) |</a>
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
				<div class="col-lg-12">
					<div class="box">
						<div class="box-header">
							<h3 class="box-title">Data User</h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Nama</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Browser: activate to sort column ascending" style="width: 224px;">Username</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Platform(s): activate to sort column ascending" style="width: 197px;">Outlet</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Engine version: activate to sort column ascending" style="width: 154px;">Jabatan</th>
											<th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending" style="width: 112px;">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($dataproduk->data as $k=>$v):?>
										<tr role="row">
											<td><?=$v->nama;?></td>
											<td><?=$v->username;?></td>
											<td><?=$v->namaOutlet;?></td>
											<td>
												<?php 
													if($v->jabatan == 0) {
														echo 'Super Admin System'; 
													} else if ($v->jabatan == 1){
														echo 'Manager';
													} else if ($v->jabatan == 2){
														echo 'Admin';
													} else if ($v->jabatan == 3){
														echo 'Stock Checker';
													} else {
														echo 'Staff';
													}
												?>
											
											</td>
											<td>
											
											<?php if (in_array('MANAGE_USER', $hakAkses[$_SESSION['hak']])):?>
												<a class="btn btn-default" href="formUser.php?mode=edit&idUser=<?=$v->idUser;?>">Edit</a>
											<?php endif; ?>
												<a class="btn btn-default" href="?mode=view&idUser=<?=$v->idUser;?>">Profile</a>
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
		var iduser = "";
		$(function() {
			
			var template_user_parent = $('[data-id="search-user"]').parent();
			var template_user = $('[data-id="search-user"]').detach();
			$("#user-autocomplete").keyup(function() {
				var keyword = $("#user-autocomplete").val();
				if (keyword.length >= MIN_LENGTH) {
					$.getJSON('users.php', {cari:keyword}, function(d){
						$(template_user_parent).show().html('');
						if (d.length != 0) {
							$.each(d, function(k,v){
								var row = $(template_user).clone().appendTo(template_user_parent);
								row.find('[data-id="user-krik"]').html(v.nama);
								row.find('[data-id="iduser-krik"]').val(v.idUser);
								kodewilayah = v.idUser;
							});
							
							$('.item').click(function() {
								var text = $(this).find('[data-id="user-krik"]').html();
								$('#user-autocomplete').val(text);
								$('[data-id="iduser-krik"]').val(kodewilayah);
								$(template_user_parent).hide();
							});
						} else {
							var row = $(template_user).clone().appendTo(template_user_parent);
							row.find('[data-id="user-krik"]').html('Data User Tidak Ditemukan');
							$('.item').click(function() {
								$(template_user_parent).hide();
							});
						}
				});
				}
			});
		});
	</script>
<?php Template::foot();?>