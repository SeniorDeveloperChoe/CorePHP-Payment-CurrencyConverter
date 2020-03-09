<?php
	$reqAuth = true;
	require_once "../../requires-sd/config-sd.php";
	require_once DIR_CLASS."start_order_product-sd.lib.php";

	$mail_data = '';
	foreach ($_POST as $key => $value) {
		$mail_data .= "$key => $value <br />";
	}
	/*mail('vishalnakum.sukhdaam@gmail.com', "Cancel data - ".date('Y-m-d H:i:s'), $mail_data);*/
	$_SESSION["toastr_message"] = disMessage(array('type'=>'err', 'var'=>MSG_PAYMENT_FAILED));
	redirectPage(SITE_URL);
?>