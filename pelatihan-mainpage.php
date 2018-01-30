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
				Menu Pelatihan JST
			</h1>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-aqua"><i class="fa  fa-star"></i></span>
				
						<div class="info-box-content">
							<span class="info-box-text">Pelatihan Jenis Produk</span>
							<span class="info-box-number"><a href="parameter-pelatihan.php">Proses Pelatihan Jenis Produk</a></span>
						</div>
					
					</div>
					
				</div>
				<div class="col-lg-12">
					<div class="info-box">
						<span class="info-box-icon bg-green"><i class="fa  fa-star"></i></span>
				
						<div class="info-box-content">
							<span class="info-box-text">Pelatihan Produk</span>
							<span class="info-box-number"><a href="parameter-pelatihan-produk.php">Proses Pelatihan Produk</a></span>
						</div>
					
					</div>
					
				</div>
			</div>
		</section>
	</div>

<?php Template::foot();?>