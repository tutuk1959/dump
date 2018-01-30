<?php
	function enarray(&$x){
		if (! is_array($x)) $x = array();
	}
	const REQUIRED = 1;
	function text(&$errors, $value, $errMsg, $maxLength, $required=0){
		$len = strlen($value);
		if ($len > $maxLength or $required==REQUIRED && ! $len)
			$errors[] = $errMsg;
	}
	
	const INT_MODE = 1;
	function number(&$errors, $value, $errMsg, $min=null, $max=null, $integer=0){
		if (! is_numeric($value) or $min!==null && $value<$min or $max!==null && $value>$max or
				$integer == INT_MODE && $value!=(int)$value)
			$errors[] = $errMsg;
	}
	
	function ensure(&$errors, $value, $errMsg){
		if (! $value) $errors[] = $errMsg;
	}
	
	function showMessage($message){
		if (!$message) return;
		?>
		<div class="alert alert-success alert-dismissible">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			<h4><i class="icon fa fa-check"></i> Sukses !</h4>
			<?=$message;?>
		</div>
		<?php
	}
	
	function showErrors($errors){
		if (! is_array($errors) || ! count($errors)) return;
		?>
		<?php foreach($errors as $v) :?>
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<h4><i class="icon fa fa-ban"></i> Gagal !</h4>
				<?=$v;?>
			</div>
		<?php endforeach;?>
		<?php
	}
	function showMessageAndErrors($message, $errors){
		showMessage($message);
		showErrors($errors);
	}
	
	function _validation($namaOutlet,$alamatOutlet,&$errors){
		enarray($errors);
		text($errors, $namaOutlet, 'Nama Outlet tidak boleh kosong!', 100, REQUIRED);
		text($errors, $alamatOutlet, 'Alamat Outlet tidak boleh kosong!', 200, REQUIRED);
	}
	
	function kickView($hakAkses){
		if (!in_array('VIEW_OUTLET', $hakAkses)){?>
			<script>
				location = 'dashboard.php';
				alert('Anda tidak memiliki hak akses!');
			</script>
		<?php
		}
	}
	
	function kickInsert($hakAkses){
		if (!in_array('INSERT_OUTLET', $hakAkses)){?>
			<script>
				location = 'dashboard.php';
				alert('Anda tidak memiliki hak akses!');
			</script>
		<?php
		}
	}
	
	function kickManage($hakAkses){
		if (!in_array('MANAGE_OUTLET', $hakAkses)){?>
			<script>
				location = 'dashboard.php';
				alert('Anda tidak memiliki hak akses!');
			</script>
		<?php
		}
	}
?>