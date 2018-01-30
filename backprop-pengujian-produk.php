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
				Pengujian Jaringan Syaraf Tiruan
			</h1>
		</section>
		<section class="content">
			<div class="row">
				<div class="col-lg-12">
					<?php showMessageAndErrors($message,$error);?>
					<div class="nav-tabs-custom">
						<ul class="nav nav-tabs">
							<li ><a href="parameter-pengujian-produk.php?produk=<?=$_REQUEST['idProduk'];?>&size=<?=$_REQUEST['idSize'];?>&outlet=<?=$_REQUEST['idOutlet']?>&idPelatihan=<?=$_REQUEST['idPelatihan'];?>">Parameter</a></li>
							<li class="active"><a href="#ekstraksi-pelatihan" data-toggle="tab">Ekstraksi</a></li>
							<li><a  href="hasil-pengujian-produk.php?idPelatihan=<?=($_REQUEST['idPelatihan']) ? $_REQUEST['idPelatihan'] : ''?>&idPengujian=<?=$_REQUEST['idPengujian']?>">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="ekstraksi-pelatihan">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Pengujian dari data Opname berdasarkan Produk</h3>
									</div>
									
										<div class="box-body">
												<input type="hidden" name="idPengujian" value="<?=$_REQUEST['idPengujian'];?>"/>
												<input type="hidden" class="form-control" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
												<?php
													//$maxInput = 1;
													//$minInput = 0;
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
												<p>Initialization</p>
												<table class="table table-bordered table-striped dataTable"  border="1">
													<thead>
														<tr>
															<th>Max Epoch</th>
															<th>Hidden Neuron</th>
															<th>Learning Rate</th>
															<th>Ambang MSE</th>
														</tr>
													</thead>
													<tbody>
														<tr>
															<td>
																10000
															</td>
															<td>
																7
															</td>
															<td>
																0.2
															</td>
															<td>
																0.000000001
															</td>
														</tr>
													</tbody>
												</table>
												<p style="margin-top:10px;">Hasil</p>
												<table class="table table-bordered table-striped dataTable"  border="1">
													<thead>
														<tr>
															<th>Target Asli</th>
															<th>Target Hasil</th>
															<th>MAD</th>
															<th>Tracking Signal</th>
														</tr>
													</thead>
													<tbody>
													<?php
													$bp->x=array(0,0,0,1);
													$bp->y=array(0);
													$db->exec("DELETE FROM graphpengujian WHERE idPengujian='%s'",$_REQUEST['idPengujian']);
													foreach ($db->table("SELECT a.idPengujian, a.x1,a.x2,a.x3,a.y FROM inputpengujian AS a WHERE a.idPengujian = '%s'",$_REQUEST['idPengujian']) as $v){
														$input = array($v->x1, $v->x2, $v->x3);
														$bp->setInput($input);
														$bp->oneStepForward();
														$output = $bp->getOutput();
														
														echo "<tr>";
															
															echo "<td>";echo $v->y; echo"</td>";
															echo "<td>";echo $output[0]; echo"</td>";
															
															$db->exec("INSERT INTO graphpengujian(idPengujian,asli,ramal) VALUES ('%s','%s','%s')",$_REQUEST['idPengujian'],$v->y,$output[0]);
															$er = ($v->y - $output[0])/$v->y;
															if ($er < 0){
																$er = $er * -1;
															}
															$sumer = $sumer + $er;
															echo "<td>";print_r($sumer); echo"</td>";
															$trackingsignal = ($v->y - $output[0]) / $sumer;
															echo "<td>";print_r($trackingsignal); echo"</td>";
															$sumTracking = $sumTracking + $trackingsignal;
															$count += 1;
														echo "</tr>";
													}
													$as = $sumer / $count;
													$mape = $as * 100;
													echo "<tr>";
														echo "<td colspan=\"3\" align=\"center\">MAPE</td>";
														echo "<td>";echo $mape; echo" % </td>";
													echo "</tr>";
													if ($trackingsignal > 4 || $trackingsignal < -4){
														$isValid = 0;
													} else {
														$isValid = 1;
													}
													$db->exec("UPDATE pengujian SET MAPE = '%s', trackingsignal = '%s', isValid = '%s'",$mape,$trackingsignal,$isValid);
													?>
													</tbody>
												</table>
												<?php showMessageAndErrors($message,$error);?>
												<div class="box-footer">
													<a href="ekstraksi-pengujian-produk.php?idProduk=<?=$v->idProduk;?>&idSize=<?=$v->idSize;?>&idOutlet=<?=$v->idOutlet?>&idPelatihan=<?=$v->idPelatihan;?>"class="btn btn-info pull-left">Back</a>
													
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
<?php

//$bp->setInput(array(74, 180000, 202));
//$bp->oneStepForward();
//echo "SET INPUT (74, 180000, 202) TARGET (47) HASIL = ".json_encode($bp->getOutput());
?>