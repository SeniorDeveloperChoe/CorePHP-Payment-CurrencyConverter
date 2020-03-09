<?php
	$reqAuth = true;
	$module = 'wallet-sd';
	$reqAuthXml = $_SERVER["SERVER_NAME"].'##'.$module;
	require_once "../../requires-sd/config-sd.php";
	require_once DIR_CLASS."wallet-sd.lib.php";
	include_once("../start_order_product-sd/process.php");
	include_once("../start_order_product-sd/paypal.class.php");
	$table = "tbl_payment_history";
 	
	$mainObj = new wallet($module);
	$winTitle = WALLET.' - ' . SITE_NM;
    $headTitle = WALLET;
    $metaTag = getMetaTags(array("description" => $winTitle, "keywords" => $headTitle, "author" => AUTHOR));  

	$pageContent = $mainObj->getPageContent();
	$pageContent = $pageContent['main_content'];
	require_once DIR_TMPL . "compiler-sd.skd";
?>