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
				Peramalan dengan JST Backpropagation
			</h1>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-aqua"><i class="fa  fa-pencil-square"></i></span>
				
						<div class="info-box-content">
							<span class="info-box-text">Pelatihan JST</span>
							<span class="info-box-number"><a href="pelatihan-mainpage.php">Menu Pelatihan</a></span>
						</div>
					
					</div>
					
				</div>
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-green"><i class="fa  fa-pencil-square"></i></span>
				
						<div class="info-box-content">
							<span class="info-box-text">Pengujian JST</span>
							<span class="info-box-number"><a href="pengujian.php">Proses Pengujian</a></span>
						</div>
					
					</div>
					
				</div>
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-yellow"><i class="fa  fa-pencil-square"></i></span>
				
						<div class="info-box-content">
							<span class="info-box-text">Peramalan</span>
							<span class="info-box-number"><a href="ramal.php">Proses Peramalan</a></span>
						</div>
					
					</div>
					
				</div>
			</div>
		</section>
	</div>

<?php Template::foot();?>