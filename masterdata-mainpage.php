<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
$file = new File();
$db = new DB();
Template::head($db,$file, $hakAkses);
?>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Manajemen Data Master
			</h1>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-aqua"><i class="fa fa-users"></i></span>
					
						<div class="info-box-content">
							<span class="info-box-text">Users</span>
							<span class="info-box-number"><a href="users.php">Kelola Master User</a></span>
						</div>
					
					</div>
					
				</div>
				
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-green"><i class="fa  fa-pencil-square"></i></span>
				
						<div class="info-box-content">
							<span class="info-box-text">Master Produksi</span>
							<span class="info-box-number"><a href="master-produksi.php">Kelola Master Produksi</a></span>
						</div>
					
					</div>
					
				</div>
			
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-yellow"><i class="fa fa-tasks"></i></span>
				
						<div class="info-box-content">
							<span class="info-box-text">Jenis Produk</span>
							<span class="info-box-number"><a href="jenisproduk.php">Kelola Master Jenis</a></span>
						</div>
					
					</div>
					
				</div>
				
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-red"><i class="fa fa-binoculars "></i></span>
				
						<div class="info-box-content">
							<span class="info-box-text">Outlet</span>
							<span class="info-box-number"><a href="outlet.php">Kelola Master Outlet</a></span>
						</div>
					
					</div>
					
				</div>
			</div>
		</section>
	</div>

<?php Template::foot();?>