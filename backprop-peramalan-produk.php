<?php
session_start();
SET_TIME_LIMIT(1200);
include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "ekstraksiPelatihan.function.php";
include_once "kuda.php";
$db = new DB();
$file = new File();
kickView($hakAkses[$_SESSION['hak']]);
kickManage($hakAkses[$_SESSION['hak']]);

if (! class_exists('BackProp')) { Class BackProp {

function import($data){
	$this->x = $data->x;
	$this->z = $data->z;
	$this->y = $data->y;
	$this->minInput = $data->minInput;
	$this->maxInput = $data->maxInput;
	$this->minTarget = $data->minTarget;
	$this->maxTarget = $data->maxTarget;
	$this->v = $data->v;
	$this->w = $data->w;
}

function export(){
	return (object) array(
		'x'=>$this->x,
		'z'=>$this->z,
		'y'=>$this->y,
		'minInput'=>$this->minInput,
		'maxInput'=>$this->maxInput,
		'minTarget'=>$this->minTarget,
		'maxTarget'=>$this->maxTarget,
		'v'=>$this->v,
		'w'=>$this->w
	);
}

function setInputTarget($it){
	$this->input = $this->target = array();
	foreach (func_num_args()==1 ? $it : func_get_args() as $k=>$v){
		if ($k % 2 == 0){
			$this->input[] = $v;
		} else {
			$this->target[] = $v;
		}
	}
	$this->x = $this->input[0];
		$this->x[] = $this->BIAS;
	$this->y = $this->target[0];
	$this->nTrainings = count($this->target);
}

function normalize(){
	$this->nInput = $this->nTarget = array();
	$this->maxInput = $this->minInput = array();
	$this->maxTarget = $this->minTarget = array();
	foreach ($this->input as $k=>$v){
		if ($k == 0)
			$this->maxInput = $this->minInput = $v;
		else foreach ($v as $kk=>$vv){
			$this->maxInput[$kk] = max($this->maxInput[$kk], $vv);
			$this->minInput[$kk] = min($this->minInput[$kk], $vv);
		}
	}
	foreach ($this->target as $k=>$v){
		if ($k == 0)
			$this->maxTarget = $this->minTarget = $v;
		else foreach ($v as $kk=>$vv){
			$this->maxTarget[$kk] = max($this->maxTarget[$kk], $vv);
			$this->minTarget[$kk] = min($this->minTarget[$kk], $vv);
		}
	}
	foreach ($this->input as $k=>$v)
		foreach ($v as $kk=>$vv)
			$this->nInput[$k][$kk] = ($vv-$this->minInput[$kk])/($this->maxInput[$kk]-$this->minInput[$kk]);
	foreach ($this->target as $k=>$v)
		foreach ($v as $kk=>$vv)
			$this->nTarget[$k][$kk] = ($vv-$this->minTarget[$kk])/($this->maxTarget[$kk]-$this->minTarget[$kk]) * 0.5 + 0.25;
}

function setInput($input){
	$this->x = array();
	foreach ($input as $k=>$v)
		$this->x[] = ($v-$this->minInput[$k])/($this->maxInput[$k]-$this->minInput[$k]);
	$this->x[] = $this->BIAS;
}

function getOutput(){
	$ret = $this->y;
	foreach ($ret as $k=>$v)
		$ret[$k] = 2 * ($v - 0.25) * ($this->maxTarget[$k]-$this->minTarget[$k]) + $this->minTarget[$k];
	return $ret;
}

function setHidden($n){
	$this->z = array();
	for ($i=0; $i<=$n; $i++)
		$this->z[] = $this->BIAS;
}

function randomWeight(){
	$this->v = $this->w = array();
	foreach ($this->x as $i=>$x) foreach ($this->z as $j=>$z){
		if ($j == count($this->z) - 1) continue;
		$this->v[$i][$j] = (rand(0, 1000) - 500) / 500;
	}
	foreach ($this->z as $i=>$z)
		foreach ($this->y as $j=>$y)
			$this->w[$i][$j] = (rand(0, 1000) - 500) / 500;
}

function loadNthTrainData($which){
	$this->x = $this->nInput[$which];
		$this->x[] = $this->BIAS;
	$this->t = $this->nTarget[$which];
}

function oneStepForward(){
	foreach ($this->z as $j=>$z){
		if ($j == count($this->z) - 1) continue;
		$temp = 0;
		foreach ($this->x as $i=>$x)
			$temp += $x * $this->v[$i][$j];
		$this->z[$j] = 1 / (1 + exp(- $temp));
	}
	foreach ($this->y as $j=>$y){
		$temp = 0;
		foreach ($this->z as $i=>$z)
			$temp += $z * $this->w[$i][$j];
		$this->y[$j] = 1 / (1 + exp(- $temp));
	}
}

public $ALPHA = 0.2;
public $BIAS = 0.5;
function twoStepsBack(){
	$this->se = 0;
	$old_w = $this->w;
	foreach ($this->y as $k=>$y){
		$this->se += ($this->t[$k] - $y) * ($this->t[$k] - $y);
		$delta_w[$k] = ($this->t[$k] - $y) * $y * (1 - $y);
		foreach ($this->z as $j=>$z)
			$this->w[$j][$k] += $this->ALPHA * $delta_w[$k] * $z;
	}
	foreach ($this->z as $j=>$z){
		if ($j == count($this->z) - 1) continue;
		$delta_v[$j] = 0;
		foreach ($this->y as $k=>$y)
			$delta_v[$j] += $delta_w[$k] * $old_w[$j][$k];
		$delta_v[$j] = $delta_v[$j] * $z * (1 - $z);
		foreach ($this->x as $i=>$x)
			$this->v[$i][$j] += $this->ALPHA * $delta_v[$j] * $x;
	}
}

}}
$bp = new BackProp();
Template::head($db,$file, $hakAkses);

?>
<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="plugins/datepicker/bootstrap-datepicker.js"></script>
	<div class="content-wrapper">
		<section class="content-header">
			<h1 style="float: left;margin-right: 10px !important;">
				Peramalan Jaringan Syaraf Tiruan
			</h1>
		</section>
		<section style="min-height:1000px"class="content">
			<div class="row">
				<div class="col-lg-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="nav-tabs-custom">
						<ul class="nav nav-tabs">
							<li ><a href="peramalan-produk.php?idPeramalan=<?=($_REQUEST['idPeramalan']) ? $_REQUEST['idPeramalan'] : ''?>">Ekstraksi</a></li>
							<li class="active"><a href="#peramalan" data-toggle="tab">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="peramalan">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Hasil Peramalan berdasarkan Produk</h3>
									</div>
									
										<div class="box-body">
												<input type="hidden" name="idPeramalan" value="<?=$_REQUEST['idPeramalan'];?>"/>
												<input type="hidden" class="form-control" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
												<?php
													$bp->setHidden(7);
													foreach ($db->table("SELECT a.idPelatihan, a.x1,a.x2,a.x3,a.y FROM inputpelatihan AS a WHERE a.idPelatihan = '%s'",$_REQUEST['idPelatihan']) as $k=>$v){
														if ($bp->minInput[0] > $v->x1 || $k==0){
															$bp->minInput[0] = $v->x1;
														}
														if ($bp->minInput[1] > $v->x2 || $k==0){
															$bp->minInput[1] = $v->x2;
														}
														if ($bp->minInput[2] > $v->x3 || $k==0){
															$bp->minInput[2] = $v->x3;
														}
														if ($bp->maxInput[0] < $v->x1 || $k==0){
															$bp->maxInput[0] = $v->x1;
														}
														if ($bp->maxInput[1] < $v->x2 || $k==0){
															$bp->maxInput[1] = $v->x2;
														}
														if ($bp->maxInput[2] < $v->x3 || $k==0){
															$bp->maxInput[2] = $v->x3;
														}
														if ($bp->minTarget[0] > $v->y || $k==0){
															$bp->minTarget[0] = $v->y;
														}
														if ($bp->maxTarget[0] < $v->y || $k==0){
															$bp->maxTarget[0] = $v->y;
														}
													}
													//$bp->normalize();
													foreach ($db->table("SELECT * FROM bobotpelatihan WHERE jenis='v' AND idPelatihan='%s'",$_REQUEST['idPelatihan']) as $v){
														$bp->v[$v->neuronFrom][$v->neuronTo] = $v->weight;
													}
													foreach ($db->table("SELECT * FROM bobotpelatihan WHERE jenis='w' AND idPelatihan='%s'",$_REQUEST['idPelatihan']) as $w){
														$bp->w[$w->neuronFrom][$w->neuronTo] = $w->weight;
													}
													
												?>
												<section>
											<?php
												$peramalanSummary = $db->row("SELECT a.tanggalTujuan,b.namaProduk,c.size
												FROM peramalan AS a
												LEFT JOIN produk AS b ON a.idProduk = b.idProduk 
												LEFT JOIN sizeproduk AS c ON a.idSize = c.idSize 
												WHERE a.idPeramalan = '%s'",$_REQUEST['idPeramalan']);
												$pelatihanSummary = $db->row("SELECT a.learningRate,a.hiddenNeuron
												FROM pelatihan AS a
												WHERE a.idPelatihan = '%s'",$_REQUEST['idPelatihan']);
											?>
												<table border="0" class="table table-bordered dataTable">
													<tr>
														<td colspan="4" align="center"><strong><?=$peramalanSummary->namaProduk;?>,Size <?=$peramalanSummary->size;?></strong></td>
													</tr>
													<tr>
														<td width="20%">Tanggal Tujuan</td>
														<td width="30%"><?=Template::format($peramalanSummary->tanggalTujuan,"date");?></td>
														<td width="20%">Hidden Neuron</td>
														<td width="30%"><?=$pelatihanSummary->hiddenNeuron;?></td>
													</tr>
													<tr>
														<td width="20%">Learning Rate</td>
														<td width="30%"><?=$pelatihanSummary->learningRate;?></td>
													</tr>
												</table>
											</section>
												<p style="margin-top:10px;">Hasil</p>
												<table class="table table-bordered table-striped dataTable"  border="1">
													<thead>
														<tr>
															<th>Produk Terjual Bulan Lalu</th>
															<th>Harga Produk</th>
															<th>Stok pada Bulan Lalu</th>
															<th>Ramalan Produk Terjual</th>
														</tr>
													</thead>
													<tbody>
													<?php
													$bp->x=array(0,0,0,1);
													$bp->y=array(0);
													foreach ($db->table("SELECT a.idPelatihan, a.x1,a.x2,a.x3,a.idPeramalan FROM inputperamalan AS a WHERE a.idPeramalan = '%s'",$_REQUEST['idPeramalan']) as $v){
														$input = array($v->x1, $v->x2, $v->x3);
														$bp->setInput($input);
														$bp->oneStepForward();
														$output = $bp->getOutput();
														$db->exec("UPDATE peramalan SET hasil_y = '%s' WHERE idPeramalan = '%s'",$output[0],$_REQUEST['idPeramalan']);
														echo "<tr>";
															
															echo "<td>";echo $v->x1; echo"</td>";
															echo "<td>";echo $v->x2; echo"</td>";
															echo "<td>";echo $v->x3; echo"</td>";
															echo "<td>";echo $output[0]; echo"</td>";
														echo "</tr>";
													}
													?>
													</tbody>
												</table>
												
												<?php showMessageAndErrors($message,$error);?>
												<div class="box-footer">
													<a href="parameter-peramalan.php"class="btn btn-info pull-left">Back</a>
													
												</div>
										</div>
								</div>
							</div>
						</div> 
					</div>
				</div>
			</div>
		</section>
	</div>
