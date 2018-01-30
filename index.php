<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "index.function.php";

$db = new DB();
$selectoutlet = $db->table("SELECT a.`idOutlet`, a.`namaOutlet` FROM `outlet` AS a");
if (isset($_SESSION['idUser'])){
	?><script> location = 'dashboard.php';</script><?php
}

if ($_REQUEST['logout'] == true){
	session_destroy();
	$message = "Logout berhasil";
}
if ($_REQUEST['submit'] == 'Login'){
	_validation($_REQUEST['username'], $_REQUEST['password'], $_REQUEST['outlet'], $error);
	$credential = $db->row("SELECT a.idUser, a.idOutlet, a.username, b.namaOutlet, a.hak
	FROM user AS a
	LEFT JOIN outlet AS b ON a.idOutlet = b.idOutlet
	WHERE a.username = '%s' AND a.password='%s' AND a.idOutlet = '%s'", $_REQUEST['username'],$_REQUEST['password'],$_REQUEST['outlet']);
	if ($credential->idUser){
		$_SESSION['idUser'] = $credential->idUser;
		$_SESSION['username'] = $credential->username;
		$_SESSION['idOutlet'] = $credential->idOutlet;
		$_SESSION['namaOutlet'] = $crendential->namaOutlet;
		$_SESSION['hak'] = $credential->hak;
		?><script> alert('Login Berhasil'); location = 'dashboard.php';</script><?php
		die();
	} else{
		$error[]="Tidak bisa login. Tidak ada data pengguna.";
	}
	
}

if ($_REQUEST['logout'] == true){
	foreach ($_SESSION['idUser'] as $k=>$v){
		unset($_SESSION['idUser'][$k]);
		unset($_SESSION['username'][$k]);
		unset($_SESSION['idOutlet'][$k]);
		unset($_SESSION['namaOutlet'][$k]);
		unset($_SESSION['hak'][$k]);
	}
}
?>
<html>
	<head>
		<title>Sistem Informasi Manajemen Stok dan Peramalan Penjualan</title>
		<link rel="stylesheet" href="bootstrap/css/custom.css">
		<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
		<link rel="stylesheet" href="dist/css/AdminLTE.min.css">
		<link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
		<link rel="stylesheet" href="plugins/iCheck/flat/blue.css">
		<link rel="stylesheet" href="plugins/morris/morris.css">
		<link rel="stylesheet" href="plugins/jvectormap/jquery-jvectormap-1.2.2.css">
		<link rel="stylesheet" href="plugins/datepicker/datepicker3.css">
		<link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
		<link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
	</head>
	<body class="hold-transition skin-blue layout-top-nav">
		<div class="wrapper">
			<header class="main-header">
				<nav class="navbar navbar-static-top">
					 <div class="container">
						<div class="navbar-header">
							<p class="navbar-brand">Sistem Peramalan Penjualan Suicide Glam</p>
						</div>
					 </div>
				</nav>
			</header>
			<div class="content-wrapper">
				<div class="container">
					<section class="content">
						<div class="row">
							<?php showMessageAndErrors($message,$error);?>
							<div class="box box-primary">
								<div class="box-header with-border">
								<h3 class="box-title">Login Sistem</h3>
								</div>
								<form method="post" action="index.php" role="form">
									<div class="box-body">
										<div class="form-group">
											<select name="outlet" class="form-control">
												<option value="">Pilih Outlet</option>
													<?php foreach($selectoutlet as $k=>$v):?>
														<option value="<?=$v->idOutlet?>"><?=$v->namaOutlet?></option>
													<?php endforeach;?>
											</select>
										</div>
										<div class="form-group">
											<input name="username" placeholder="Username" type="username" class="form-control">
										</div>
										<div class="form-group">
											
											<input name="password"placeholder="Password" type="password" class="form-control" placeholder="Password">
										</div>
									</div>
									<div class="box-footer">
										<button name="submit" value="Login"type="submit" class="btn btn-primary">Login</button>
									</div>
								</form>
							</div>
						</div>
					</section>
				</div>
			</div>
		</div>
	</body>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
	<script>
	$.widget.bridge('uibutton', $.ui.button);
	</script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
	<script src="plugins/morris/morris.min.js"></script>
	<script src="plugins/sparkline/jquery.sparkline.min.js"></script>
	<script src="plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
	<script src="plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
	<script src="plugins/knob/jquery.knob.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js"></script>
	<script src="plugins/daterangepicker/daterangepicker.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<script src="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
	<script src="plugins/slimScroll/jquery.slimscroll.min.js"></script>
	<script src="plugins/fastclick/fastclick.js"></script>
	<script src="dist/js/app.min.js"></script>
	<script src="dist/js/pages/dashboard.js"></script>
	<script src="dist/js/demo.js"></script>
</html>