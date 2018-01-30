<?php
include_once "db.class.php";
$db = new DB();

if ($_REQUEST['action'] == "popup") {
	$data = new StdClass();
	$data->node = $db->row("SELECT id, nama label, alpha3, omega3
			FROM wilayah WHERE id=%d", $_REQUEST['id']);
	if (! $data->node->id)
		$data->node = $db->row("SELECT id, nama label, alpha3, omega3
				FROM wilayah WHERE keterangan LIKE '%DEFAULT%' LIMIT 1");
	$data->parent = $db->row("SELECT id, nama label, alpha3, omega3 FROM wilayah
			WHERE alpha3<%d AND omega3>%d ORDER BY omega3-alpha3 LIMIT 1",
			$data->node->alpha3, $data->node->omega3);
	if (! $data->parent) unset($data->parent);
	$data->children = array();
	for ($i=$data->node->alpha3 + 1; $i<$data->node->omega3;){
		$child = $db->row("SELECT id, nama label, alpha3, omega3 FROM wilayah
			WHERE alpha3=%d", $i);
		$data->children[] = $child;
		$i = $child->omega3 + 1;
	}
	die(json_encode($data));
}
