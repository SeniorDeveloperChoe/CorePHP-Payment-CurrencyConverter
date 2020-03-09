<?php
	$reqAuth = true;
	$module = 'start_order_product-sd';
	$reqAuthXml = $_SERVER["SERVER_NAME"].'##'.$module;
	require_once "../../requires-sd/config-sd.php";
	require_once DIR_CLASS."start_order_product-sd.lib.php";
	include_once("process.php");
	include_once("paypal.class.php");
	$table = "tbl_user";

	
	$getProductId="";
	if(!empty($_GET['slug'])){
		$getProductId=$db->pdoQuery("SELECT id FROM tbl_products WHERE product_slug=?",array($_GET['slug']))->result();
	}	
	$mainObj = new ProductStartOrder($module,$getProductId);
	$winTitle = START_ORDER.' - ' . SITE_NM;
    $headTitle = START_ORDER;
    $metaTag = getMetaTags(array("description" => $winTitle, "keywords" => $headTitle, "author" => AUTHOR));


	$pageContent = $mainObj->getPageContent($getProductId);
	require_once DIR_TMPL . "compiler-sd.skd";
?>