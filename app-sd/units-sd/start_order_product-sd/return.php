<?php

	require_once "../../requires-sd/config-sd.php";

	require_once DIR_CLASS."start_order_product-sd.lib.php";
	
	$data = new stdClass();
	
	
	$msgType = $_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>PAYMENT_RECEIVED_YOUR_PRODUCT_WILL_BE_SENT));
	redirectPage(SITE_URL);
 	
	
?>