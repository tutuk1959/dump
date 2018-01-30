<?php

include_once "classes/db.class.php";
include_once "classes/hak-akses.inc.php";
include_once "classes/template.class.php";
include_once "classes/class.file.php";
include_once "pagination.php";
include_once "ekstraksiPelatihan.function.php";
include_once "kuda.php";


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
$db = new DB();
foreach ($db->table("SELECT * FROM bobotpelatihan WHERE jenis='v' AND idPelatihan=1") as $v){
	$bp->v[$v->neuronFrom][$v->neuronTo] = $v->weight;
}

print_r($bp->v);
?>