<?php
//$data = array(
//	(object) array('terjualLalu'=>74, 'harga'=>180000, 'stokAwal'=>202, 'terjual'=>47),
//	(object) array('terjualLalu'=>47, 'harga'=>180000, 'stokAwal'=>155, 'terjual'=>66),
//	(object) array('terjualLalu'=>66, 'harga'=>135000, 'stokAwal'=>305, 'terjual'=>125)
//);

$min = new StdClass();
$max = new StdClass();

function normalized($data){
	foreach (array_values($data) as $k=>$v){
		if ($k){
			$min->terjualLalu = min($min->terjualLalu, $v->terjualLalu);
			$max->terjualLalu = max($max->terjualLalu, $v->terjualLalu);
			$min->harga = min($min->harga, $v->harga);
			$max->harga = max($max->harga, $v->harga);
			$min->stokAwal = min($min->stokAwal, $v->stokAwal);
			$max->stokAwal = max($max->stokAwal, $v->stokAwal);
			$min->terjual = min($min->terjual, $v->terjual);
			$max->terjual = max($max->terjual, $v->terjual);
		} else {
			$min->terjualLalu = $max->terjualLalu = $v->terjualLalu;
			$min->harga = $max->harga = $v->harga;
			$min->stokAwal = $max->stokAwal = $v->stokAwal;
			$min->terjual = $max->terjual = $v->terjual;
		}
	}

	$normalized = array();
	foreach ($data as $k=>$v){
		$normalized[$k] = new StdClass();
		$normalized[$k]->terjualLalu = ($v->terjualLalu - $min->terjualLalu)
			/ ($max->terjualLalu - $min->terjualLalu);
		$normalized[$k]->harga = ($v->harga - $min->harga)
			/ ($max->harga - $min->harga);
		$normalized[$k]->stokAwal = ($v->stokAwal - $min->stokAwal)
			/ ($max->stokAwal - $min->stokAwal);
		$normalized[$k]->terjual = ($v->terjual - $min->terjual)
			/ ($max->terjual - $min->terjual);
	}
	return $normalized;
}

