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

if ($_REQUEST['mode'] == 'view' && isset($_REQUEST['toko'])){
	foreach ($db->table("SELECT a.idOpname,b.idOpnameDetail, a.tanggalOpname, a.idOutlet,b.idProduk, b.idSize, SUM(b.qty) AS jumlahproduk, c.kodeProduk, c.namaProduk, d.size, d.slug, d.tipeUkur, e.jenisProduk
		FROM opname AS a
		LEFT JOIN opnamedetail AS b ON a.idOpname = b.idOpname
		LEFT JOIN produk AS c ON c.idProduk = b.idProduk
		LEFT JOIN sizeproduk AS d ON d.idSize = b.idSize
		LEFT JOIN jenisproduk AS e ON e.idJenis = c.idJenisProduk
		WHERE a.tanggalOpname LIKE '%s' AND a.idOutlet = '%s' GROUP BY b.idSize,b.idProduk,c.idJenisProduk", $tahuntanggalsekarang = date('Y-m', strtotime('now')).'-%',$_REQUEST['toko']) as $row){
		$data[ $row->idProduk ][ $row->jenisProduk ]['namaProduk'] = $row->namaProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['jenisProduk'] = $row->jenisProduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['tipeUkur'] = $row->tipeUkur;
		$data[ $row->idProduk ][ $row->jenisProduk ][$row->size] += $row->jumlahproduk;
		$data[ $row->idProduk ][ $row->jenisProduk ]['qtyTotal'] += $row->jumlahproduk;
	}
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
				On-Stock Product
			</h1>
			<a style="float: left;margin-bottom: 10px !important;" href="formOpname.php?mode=insert">
				<button type="button" class="btn btn-default pull-right"><i class="fa fa-plus"></i> Tambah Opname</button>
			</a>
			<form class="col-md-6 pull-right"action="?mode=view" method="post">
				<div class="col-sm-9 form-group">
					
					<select name="toko" class="form-control">
						<option value="">Pilih Toko / Outlet</option>
						<?php foreach($selectToko as $k=>$v) :?>
							<option value="<?=$v->idOutlet;?>" <?=($_REQUEST['toko'] == $v->idOutlet) ? 'selected' : '';?> ><?=$v->namaOutlet;?></option>
						<?php endforeach;?>
					</select>
				</div>
				<div class="col-sm-1 form-group">
					<?php $tahuntanggalsekarang = date('Y-m', strtotime('now')).'-%';?>
					<input type="hidden" name="tanggalOpname" value=<?=$tahuntanggalsekarang?>/>
				</div>
				<button class="col-sm-2 btn btn-default pull-right" type="submit" value="Sort" name="submit">Sort</button>
			</form>
			<div class="row">
				<div class="col-lg-12">
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
														<td><?= ($row['XXS'] <= 0)? '0' : $row['XXS'];?></td>
														<td><?= ($row['XS'] <= 0)? '0' : $row['XS'];?></td>
														<td><?= ($row['S'] <= 0)? '0' : $row['S'];?></td>
														<td><?= ($row['M'] <= 0)? '0' : $row['M'];?></td>
														<td><?= ($row['L'] <= 0)? '0' : $row['L'];?></td>
														<td><?= ($row['XL'] <= 0)? '0' : $row['XL'];?></td>
														<td><?= ($row['XXL'] <= 0)? '0' : $row['XXL'];?></td>
														<td><?=$row['qtyTotal']?></td>
														
													</tr>
												<?php elseif($row['tipeUkur']==2) : ?>
													<tr role="row">
														<td><?=$row['namaProduk'];?></td>
														<td><?=$row['jenisProduk'];?></td>
														<td><?=$row['tipeUkur'];?></td>
														<td><?= ($row['38'] <= 0)? '0' : $row['38'];?></td>
														<td><?= ($row['39'] <= 0)? '0' : $row['39'];?></td>
														<td><?= ($row['40'] <= 0)? '0' : $row['40'];?></td>
														<td><?= ($row['41'] <= 0)? '0' : $row['41'];?></td>
														<td><?= ($row['42'] <= 0)? '0' : $row['42'];?></td>
														<td><?= ($row['43'] <= 0)? '0' : $row['43'];?></td>
														<td><?= ($row['44'] <= 0)? '0' : $row['44'];?></td>
														<td><?=$row['qtyTotal']?></td>
														
													</tr>
												<?php elseif($row['tipeUkur']==3) : ?>
													<tr role="row">
														<td><?=$row['namaProduk'];?></td>
														<td><?=$row['jenisProduk'];?></td>
														<td><?=$row['tipeUkur'];?></td>
														<td><?= ($row['All'] <= 0)? '0' : $row['All'];?></td>
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