<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "index.function.php";
$db = new DB();
$file = new File();
kickYou($_SESSION['hak']);
$opnameTerakhir = $db->row("SELECT a.tanggalOpname FROM opname AS a ORDER BY a.tanggalOpname DESC LIMIT 1");
$bulanIni = date('Y-m', strtotime($opnameTerakhir->tanggalOpname)).'-%';
$bulanLalu = date('Y-m', strtotime('-1 month')).'-%';
$dashboardOutletInfo = $db->row("SELECT 
						a.idOutlet, a.namaOutlet,a.alamat,a.telp
						FROM
						outlet AS a
						WHERE a.idOutlet = '%s'", $_SESSION['idOutlet']);
$dashboardUserInfo = $db->row("SELECT 
						a.nama, a.jabatan
						FROM
						user AS a
						WHERE a.idUser = '%s'", $_SESSION['idUser']);
$widgetOutlet = $db->cell("SELECT COUNT(a.idOutlet)
						FROM
						outlet AS a");
$widgetProduk = $db->cell("SELECT COUNT(a.idProduk)
						FROM
						produk AS a");
$widgetJenisProduk = $db->cell("SELECT COUNT(a.idJenis)
						FROM
						jenisproduk AS a");
$widgetTotalPenjualan = $db->cell("SELECT 
						(SELECT SUM(a.prevQty) FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s')
						-
						(SELECT SUM(a.qty) FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s')
						selisih",$bulanIni,$bulanIni);
$bestsellingProduct =  $db->table("SELECT b.namaProduk,c.jenisProduk, x.bulanLalu, y.bulanIni, (x.bulanLalu - y.bulanIni) AS totalPenjualan
						FROM 
						(SELECT a.idProduk, SUM(a.prevQty) AS bulanLalu FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s'
						GROUP BY a.idProduk) x
						JOIN 
						(SELECT a.idProduk, SUM(a.qty) AS bulanIni FROM `opnamedetail` a LEFT JOIN `opname` b ON a.`idOpname`=b.`idOpname` WHERE b.`tanggalOpname` LIKE '%s'
						GROUP BY a.idProduk) y
						ON x.idProduk = y.idProduk
						LEFT JOIN produk AS b ON x.idProduk = b.idProduk
						LEFT JOIN jenisProduk AS c ON b.idJenisProduk = c.idJenis
						ORDER BY totalPenjualan DESC LIMIT 5
						",$bulanIni, $bulanIni);
$produkTeratas = $db->table("SELECT a.namaProduk, b.jenisProduk FROM produk AS a 
LEFT JOIN jenisproduk AS b ON a.idJenisProduk = b.idJenis
ORDER BY a.idProduk DESC LIMIT 5");
$pasokanTeratas = $db->table("SELECT d.namaOutlet,c.namaProduk, a.tanggalSupply, SUM(b.qty) AS qty
FROM supply AS a 
LEFT JOIN supplydetail AS b ON a.idSupply = b.idSupply
LEFT JOIN produk AS c ON b.idProduk = c.idProduk
LEFT JOIN outlet AS d ON a.idOutlet = d.idOutlet
GROUP BY a.idSupply,c.idProduk
ORDER BY a.tanggalSupply DESC LIMIT 5");
Template::head($db, $file,$hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	
	
	<script src="plugins/morris/morris.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
	
	<div class="content-wrapper">
		<!-- Content Header (Page header) -->
		<section class="content-header">
			<h1>
				Dashboard
				
				<small>Sistem Peramalan Penjualan Suicide Glam - <?=$dashboardOutletInfo->namaOutlet;?></small>
			</h1>
			<ol class="breadcrumb">
				<li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
				<li class="active">Dashboard</li>
			</ol>
		</section>
		<!-- Main content -->
		<section class="content">
			<!-- Small boxes (Stat box) -->
			<div class="row">
				<div class="col-lg-12">
					<div class="alert alert-info">
						<h4><i class="icon fa fa-check"></i> Selamat Datang, <?=$dashboardUserInfo->nama; ?> !</h4>
						<?=$dashboardOutletInfo->namaOutlet;?>
					</div>
				</div>
				
				<div class="col-lg-3 col-xs-6">
					<!-- small box -->
					<div class="small-box bg-aqua">
						<div class="inner">
						<h3><?=$widgetProduk;?></h3>
				
						<p>Produk</p>
						</div>
						<div class="icon">
						<i class="ion ion-tshirt"></i>
						</div>
						<a href="produk.php" class="small-box-footer">Info Selengkapnya <i class="fa fa-arrow-circle-right"></i></a>
					</div>
				</div>
				<!-- ./col -->
				<div class="col-lg-3 col-xs-6">
				<!-- small box -->
				<div class="small-box bg-green">
					<div class="inner">
					<h3><?=$widgetOutlet;?></h3>
			
					<p>Outlet</p>
					</div>
					<div class="icon">
					<i class="ion ion-briefcase"></i>
					</div>
					<a href="outlet.php" class="small-box-footer">Info Selengkapnya <i class="fa fa-arrow-circle-right"></i></a>
				</div>
				</div>
				<!-- ./col -->
				<div class="col-lg-3 col-xs-6">
				<!-- small box -->
				<div class="small-box bg-yellow">
					<div class="inner">
					<h3><?=$widgetJenisProduk;?></h3>
			
					<p>Info Jenis Produk</p>
					</div>
					<div class="icon">
					<i class="ion ion-pricetag"></i>
					</div>
					<a href="#" class="small-box-footer">Info Selengkapnya <i class="fa fa-arrow-circle-right"></i></a>
				</div>
				</div>
				<!-- ./col -->
				<div class="col-lg-3 col-xs-6">
				<!-- small box -->
				<div class="small-box bg-red">
					<div class="inner">
					<h3><?=$widgetTotalPenjualan;?></h3>
			
					<p>Penjualan Bulan Lalu</p>
					</div>
					<div class="icon">
					<i class="ion ion-cash"></i>
					</div>
					<a href="opname.php" class="small-box-footer">Info Selengkapnya <i class="fa fa-arrow-circle-right"></i></a>
				</div>
				</div>
				<!-- ./col -->
			</div>
			<!-- /.row -->
			<!-- Main row -->
			<div class="row">
				
				<!-- Left col -->
				<section class="col-lg-7 connectedSortable">
				<!-- Custom tabs (Charts with tabs)-->
				
					<div class="nav-tabs-custom">
						<!-- Tabs within a box -->
						<ul class="nav nav-tabs pull-right">
						<li class="active"><a href="#penjualan-chart" data-toggle="tab">Area</a></li>
						<li class="pull-left header"><i class="fa fa-inbox"></i> Penjualan</li>
						</ul>
						<div class="tab-content no-padding">
						<!-- Morris chart - Sales -->
						<div class="chart tab-pane active" id="penjualan-chart" style="position: relative; height: 300px;"></div>
						<script src="graph-penjualan.php"></script>
						</div>
					</div>
				</section>
				<!-- /.Left col -->
				<!-- right col (We are only adding the ID to make the widgets sortable)-->
				<section class="col-lg-5 connectedSortable">
					<div class="box box-info">
						<div class="box-header">
						<i class="fa fa-tags"></i>
			
						<h3 class="box-title">Produk Best Selling</h3>
						</div>
						<div class="box-body">
							<?php foreach ($bestsellingProduct as $k=>$v):?>
								<div class="info-box bg-aqua">
									<span class="info-box-icon"><i class="ion-briefcase"></i></span>
									<div class="info-box-content">
										<span class="info-box-text"><?=$v->jenisProduk;?></span>
										<span class="info-box-number"><?=$v->namaProduk;?></span>
										<span class="info-box-number"><?=$v->totalPenjualan;?></span>
										
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="box-footer clearfix">
						</div>
					</div>
					
				<!-- /.box -->
				</section>
				<!-- right col -->
			</div>
			<div class="row">
				<section class="col-lg-6 connectedSortable">
					<div class="box box-primary">
						<div class="box-header">
						<i class="ion ion-clipboard"></i>
							<h3 class="box-title">Produk Baru</h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<ul class="todo-list">
								<?php foreach($produkTeratas as $k=>$v):?>
									<li>
									<!-- drag handle -->
									<span class="handle">
										<i class="fa fa-ellipsis-v"></i>
										<i class="fa fa-ellipsis-v"></i>
									</span>
									<!-- todo text -->
									<span class="text"><?=$v->namaProduk;?></span>
									<!-- Emphasis label -->
									<small class="label label-danger"><i class="fa fa-clock-o"></i> <?=$v->jenisProduk;?></small>
									<!-- General tools such as edit or delete-->
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						<!-- /.box-body -->
						<div class="box-footer clearfix no-border">
						<a href="">
							<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Produk</button>
						</a>
						</div>
					</div>
				</section>
				<section class="col-lg-6 connectedSortable">
					<div class="box box-primary">
						<div class="box-header">
						<i class="ion ion-clipboard"></i>
							<h3 class="box-title">Pasokan Baru</h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<ul class="todo-list">
								<?php foreach($pasokanTeratas as $k=>$v):?>
									<li>
									<!-- drag handle -->
									<span class="handle">
										<i class="fa fa-ellipsis-v"></i>
										<i class="fa fa-ellipsis-v"></i>
									</span>
									<!-- todo text -->
									<span class="text"><?=$v->namaProduk;?>, </span>
									<span class="text"><?=$v->namaOutlet;?></span>
									<!-- Emphasis label -->
									<small class="label label-danger"><i class="fa fa-clock-o"></i> <?=$v->tanggalSupply;?></small>
									<small class="label label-info"><i class="fa fa-clock-o"></i> <?=$v->qty;?></small>
									<!-- General tools such as edit or delete-->
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						<!-- /.box-body -->
						<div class="box-footer clearfix no-border">
						<a href="">
							<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Produk</button>
						</a>
						</div>
					</div>
				</section>
			</div>
			<!-- /.row (main row) -->
		</section>
	<!-- /.content -->
	</div>

<?php Template::foot();?>