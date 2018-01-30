<?php
session_start();
SET_TIME_LIMIT(2400);
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
				Pelatihan Jaringan Syaraf Tiruan
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
							<li><a  href="hasil-pelatihan-pengujian.php?idPelatihan=<?=($_REQUEST['idPelatihan']) ? $_REQUEST['idPelatihan'] : ''?>">Hasil</a></li>
						</ul>
						<div class="tab-content">
							<div class="active tab-pane" id="bobot-pelatihan">
								<div class="box box-info">
									<div class="box-header with-border">
										<h3 class="box-title">Pelatihan Kembali Pengujian dari data Opname berdasarkan Jenis Produk Produk</h3>
									</div>
									
										<div class="box-body">
												<input type="hidden" name="idPel" value="<?=$_REQUEST['idPelatihan'];?>"/>
												<input type="hidden" class="form-control" name="mode" value="<?=($_REQUEST['mode'] == 'edit')? 'edit' : 'insert';?>"/>
												<?php
													foreach ($db->table("SELECT a.idPelatihan, a.x1,a.x2,a.x3,a.y FROM inputpelatihan AS a WHERE a.idPelatihan = '%s'",$_REQUEST['idPelatihan']) as $v){
														$inputTargets[] = array($v->x1, $v->x2, $v->x3);
														$inputTargets[] = array($v->y);
													}
													
													$bp->setInputTarget($inputTargets);
													$bp->normalize();
													$bp->setHidden(7);
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
																100000
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
												<?php
													$MAXEPOCH = 100000;
													$db->exec("DELETE FROM parameterpelatihan WHERE idPelatihan=%d", $_REQUEST['idPelatihan']);
													$db->exec("DELETE FROM msepelatihan WHERE idPelatihan=%d", $_REQUEST['idPelatihan']);
													$db->begin();
													for ($i=0; $i<$MAXEPOCH; $i++){
														$se = 0;
														for ($j=0; $j<$bp->nTrainings; $j++){
															$bp->loadNthTrainData($j); 
															$bp->oneStepForward();
															//$db->exec("INSERT INTO parameterpelatihan(idPelatihan,name,value,epoch,forback) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['idPelatihan'], 'v',json_encode($bp->v),$i, 1);
															//$db->exec("INSERT INTO parameterpelatihan(idPelatihan,name,value,epoch,forback) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['idPelatihan'], 'w',json_encode($bp->w),$i, 1);
															$bp->twoStepsBack();
															//$db->exec("INSERT INTO parameterpelatihan(idPelatihan,name,value,epoch,forback) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['idPelatihan'], 'v',json_encode($bp->v),$i, 0);
															//$db->exec("INSERT INTO parameterpelatihan(idPelatihan,name,value,epoch,forback) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['idPelatihan'], 'w',json_encode($bp->w),$i, 0);
															$se += $bp->se;
															//$db->exec("INSERT INTO parameterpelatihan(idPelatihan,name,value,epoch,forback) VALUES ('%s','%s','%s','%s','%s')",$_REQUEST['idPelatihan'], 'se',json_encode($bp->se),$i, 0);
														}
														$mse = $se/$bp->nTrainings; 
														echo "((Epoch $i --> MSE : $mse)) \r\n";
														$db->exec("INSERT INTO msepelatihan(idPelatihan,epoch,MSE) VALUES ('%s','%s','%s')",$_REQUEST['idPelatihan'], $i,$mse);
														if ($mse <= 1e-8) break;
													}
													$db->commit();
													$idmsePel = $db->row("SELECT a.idMsePelatihan FROM msepelatihan AS a WHERE a.idPelatihan = '%s' ORDER BY a.idMsePelatihan DESC LIMIT 1",$_REQUEST['idPelatihan']);
													$mseTerakhir = $db->row("SELECT a.epoch, a.MSE FROM msepelatihan AS a WHERE idMsePelatihan = '%s'",$idmsePel->idMsePelatihan);
													$db->exec("DELETE FROM bobotpelatihan WHERE idPelatihan = '%s'",$_REQUEST['idPelatihan']);
													foreach ($bp->v as $i=>$v){
														foreach ($v as $j=>$vv)
															//$db->exec("UPDATE bobotpelatihan SET neuronFrom = '%s',neuronTo = '%s', weight = '%s', jenis='%s' WHERE idPelatihan = '%s'",$i,$j,$vv,'v',$_REQUEST['idPelatihan']);
															$db->exec("INSERT INTO `bobotpelatihan`(`idPelatihan`, `neuronFrom`, `neuronTo`, `weight`, `jenis`) VALUES ('%s','%d','%d', '%f', '%s')",
																	$_REQUEST['idPelatihan'], $i, $j, $vv,'v');
													}
													foreach ($bp->w as $i=>$w){
														foreach ($w as $j=>$ww)
															//$db->exec("UPDATE bobotpelatihan SET neuronFrom = '%s',neuronTo = '%s', weight = '%s', jenis='%s' WHERE idPelatihan = '%s'",$i,$j,$ww,'w',$_REQUEST['idPelatihan']);
															$db->exec("INSERT INTO `bobotpelatihan`(`idPelatihan`, `neuronFrom`, `neuronTo`, `weight`, `jenis`) VALUES ('%s','%d','%d', '%f', '%s')",
																	$_REQUEST['idPelatihan'], $i, $j, $ww,'w');
													}
													$message = "Sukses ! Berhenti pada epoch ke ".$mseTerakhir->epoch." dengan MSE ".$mseTerakhir->MSE.". Harap close page ini setelah melakukan perhitungan!";
												?>
												<?php showMessageAndErrors($message,$error);?>
												<div class="box-footer">
													<a href="pengujian.php"class="btn btn-info pull-left">Back</a>
													
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
?>