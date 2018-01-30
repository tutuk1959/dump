<?php
session_start();
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "opname.function.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
//pagination
$limit = (isset( $_REQUEST['limit'])) ? $_REQUEST['limit']:25;
$page=(isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
$links=(isset($_GET['links']))?$_GET['links'] : 7;

if ($_REQUEST['mode'] == 'view' && isset($_REQUEST['toko']) && isset($_REQUEST['tanggalOpname'])){
	$bulanOpname = substr($_REQUEST['tanggalOpname'], 6,10).'-'.substr($_REQUEST['tanggalOpname'], 0,2).'-'.substr($_REQUEST['tanggalOpname'], 3,2);
	$bulOpname = substr($_REQUEST['tanggalOpname'], 6,10).'-'.substr($_REQUEST['tanggalOpname'], 0,2).'-%';
	$bulOpnameSebelum = date('Y-m', strtotime("$bulanOpname -1month")).'-%';
	$count = $db->row("SELECT COUNT(a.idOpname) AS jumlahOpname, a.tanggalOpname,a.idOpname,a.idOutlet FROM opname AS a 
	WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' ORDER BY a.tanggalOpname DESC LIMIT 1", $bulOpname,$_REQUEST['toko']);
	if ($count->jumlahOpname > 0){
		$tanggalymd = date('Y-m-d', strtotime($count->tanggalOpname));
		foreach ($db->table("SELECT a.idOpname,b.idOpnameDetail, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
		FROM opname AS a
		LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk ORDER BY c.idJenisProduk",$tanggalymd,$_REQUEST['toko']) as $row){
		$data[ $row->idProduk ][ $row->jenisProduk ]['idProduk'] = $row->idProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ][$row->size]['idOpnameDetail'] = $row->idOpnameDetail;
		$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['kodeProduk'] = $row->kodeProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
		$data[ $row->idProduk ][ $row->jenisProduk ][$row->size][$row->size] += $row->jumlahproduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
		}
	} else {
		$error[] = 'Tidak ada data opname yang ditemukan!';
	}
}

//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$cariproduk = $db->table("SELECT a.namaProduk, a.idProduk
		FROM produk AS a
		WHERE a.namaProduk LIKE '%$key%'
		ORDER BY a.namaProduk");
	die(json_encode($cariproduk));
}

//autocompletes
if (isset($_REQUEST['cari'])){
	$key = $_REQUEST['cari'];
	$cariproduk = $db->table("SELECT a.namaProduk, a.idProduk
		FROM produk AS a
		WHERE a.namaProduk LIKE '%$key%'
		ORDER BY a.namaProduk");
	die(json_encode($cariproduk));
}

//selectBoxToko
$selectToko = $db->table("SELECT a.idOutlet, a.namaOutlet FROM outlet AS a ORDER BY a.idOutlet");
Template::head($db,$file, $hakAkses);
?>
	<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Perubahan Stok Produk
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formOpname.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Opname</button>
			</a>
			<form class="col-md-6 pull-right"action="?mode=view" method="post">
				<div class="col-sm-5 form-group">
					
					<select name="toko" class="form-control">
						<option value="">Pilih Toko / Outlet</option>
						<?php foreach($selectToko as $k=>$v) :?>
							<option value="<?=$v->idOutlet;?>" <?=($_REQUEST['toko'] == $v->idOutlet) ? 'selected' : '';?> ><?=$v->namaOutlet;?></option>
						<?php endforeach;?>
					</select>
				</div>
				<div class="col-sm-5 form-group">
					<div class="input-group date">
					<div class="input-group-addon">
						<i class="fa fa-calendar"></i>
					</div>
					<input name="tanggalOpname" type="text" value="<?=$_REQUEST['tanggalOpname']?>"class="form-control pull-right" id="datepicker">
					</div>
				</div>
				<button class="col-sm-2 btn btn-default" type="submit" value="Sort" name="submit">Sort</button>
			</form>
			<div class="row">
				<div class="col-lg-6 pull-left">
					<a style="float: left;margin-bottom: 10px !important;" href="laporan-opname.php?mode=report&tanggalOpname=<?=$count->tanggalOpname;?>&toko=<?=$count->idOutlet;?>&idOpname=<?=$count->idOpname;?>">
						<button type="button" class="btn btn-default"><i class="fa  fa-file-code-o"></i> Export Laporan Opname</button>
					</a>
					<a style="float: left;margin-bottom: 10px !important;margin-left:10px !important;" href="formOpnameDetail.php?mode=edit&tanggalOpname=<?=$_REQUEST['tanggalOpname']?>&toko=<?=$_REQUEST['toko'];?>&idOpname=<?=$count->idOpname;?>">
						<button type="button" class="btn btn-default"><i class="fa  fa-balance-scale"></i> Ubah Opname</button>
					</a>
				</div>
				<div class="col-lg-4 pull-right">
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
				<div class="col-lg-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="box">
						<div class="box-header">
							<h3 class="box-title">Data Produk</h3>
						</div>
						<!-- /.box-header -->
						<div class="box-body">
							<div id="example1_wrapper" class="dataTables_wrapper form-inline dt-bootstrap">
								<table id="example1" class="table table-bordered table-striped dataTable" role="grid" aria-describedby="example1_info">
									<thead>
										<tr role="row">
											<th style="vertical-align:middle !important;" class="sorting" rowspan="3">Produk</th>
											<th style="vertical-align:middle !important;" rowspan="3">Jenis</th>
											<th>1</th>
											<th>XSS</th>
											<th>XS</th>
											<th>S</th>
											<th>M</th>
											<th>L</th>
											<th>XL</th>
											<th>XXL</th>
											<th>Qty</th>
										</tr>
										<tr role="row">
											<!--td rowspan="3"--><!--td rowspan="3"-->
											<th>2</th>
											<th>38</th>
											<th>39</th>
											<th>40</th>
											<th>41</th>
											<th>42</th>
											<th>43</th>
											<th>44</th>
											<th>&nbsp;</th>
										</tr>
										<tr role="row">
											<!--td rowspan="3"--><!--td rowspan="3"-->
											<th>3</th>
											<th>All</th>
											<th>&nbsp;</th>
											<th>&nbsp;</th>
											<th>&nbsp;</th>
											<th>&nbsp;</th>
											<th>&nbsp;</th>
											<th>&nbsp;</th>
											<th>&nbsp;</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($data as $k=>$v) :?> 
											<?php foreach ($v as $kk=>$row) :?>
												<?php if ($row['tipeUkur']==1) :?>
													<tr role="row">
														<td><?=$row['namaProduk'];?></td>
														<td><?=$row['jenisProduk'];?></td>
														<td><?=$row['tipeUkur'];?></td>
														<td><?= ($row['XXS']['XXS'] <= 0)? '0' : $row['XXS']['XXS'];?></td>
														<td><?= ($row['XS']['XS'] <= 0)? '0' : $row['XS']['XS'];?></td>
														<td><?= ($row['S']['S'] <= 0)? '0' : $row['S']['S'];?></td>
														<td><?= ($row['M']['M'] <= 0)? '0' : $row['M']['M'];?></td>
														<td><?= ($row['L']['L'] <= 0)? '0' : $row['L']['L'];?></td>
														<td><?= ($row['XL']['XL'] <= 0)? '0' : $row['XL']['XL'];?></td>
														<td><?= ($row['XXL']['XXL'] <= 0)? '0' : $row['XXL']['XXL'];?></td>
														<td><?=$row['qtyTotal']?></td>
														
													</tr>
												<?php elseif($row['tipeUkur']==2) : ?>
													<tr role="row">
														<td><?=$row['namaProduk'];?></td>
														<td><?=$row['jenisProduk'];?></td>
														<td><?=$row['tipeUkur'];?></td>
														<td><?= ($row['38']['38'] <= 0)? '0' : $row['38']['38'];?></td>
														<td><?= ($row['39']['39'] <= 0)? '0' : $row['39']['39'];?></td>
														<td><?= ($row['40']['40'] <= 0)? '0' : $row['40']['40'];?></td>
														<td><?= ($row['41']['41'] <= 0)? '0' : $row['41']['41'];?></td>
														<td><?= ($row['42']['42'] <= 0)? '0' : $row['42']['42'];?></td>
														<td><?= ($row['43']['43'] <= 0)? '0' : $row['43']['43'];?></td>
														<td><?= ($row['44']['44'] <= 0)? '0' : $row['44']['44'];?></td>
														<td><?=$row['qtyTotal']?></td>
														
													</tr>
												<?php elseif($row['tipeUkur']==3) : ?>
													<tr role="row">
														<td><?=$row['namaProduk'];?></td>
														<td><?=$row['jenisProduk'];?></td>
														<td><?=$row['tipeUkur'];?></td>
														<td><?= ($row['All']['All'] <= 0)? '0' : $row['All']['All'];?></td>
														<td></td>
														<td></td>
														<td></td>
														<td></td>
														<td></td>
														<td></td>
														<td><?=$row['qtyTotal']?></td>
														
													</tr>
												<?php endif?>
											<?php endforeach; ?>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
	<script>
	//Date picker
	$('#datepicker').datepicker({
		autoclose: true,
		dateFormat : 'yy-mm-dd'
	});
	
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