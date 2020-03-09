<?php
	//$reqAuth = true;
	$module = 'cart-sd';
	$reqAuthXml = $_SERVER["SERVER_NAME"].'##'.$module;
	require_once "../../requires-sd/config-sd.php";
	require_once DIR_CLASS."cart-sd.lib.php";
	$table = "tbl_cart";

	$mainObj = new Cart($module);

	/*$scripts = array(
		array("droppable.js")
	);
*/
	$winTitle = MANAGE_CART.' - ' . SITE_NM;
    $headTitle = MANAGE_CART;
    $metaTag = getMetaTags(array("description" => $winTitle, "keywords" => $headTitle, "author" => AUTHOR));

    if(!empty($_POST)){
    		$mainObj->addProduct($_POST);
    }

	$pageContent = $mainObj->getPageContent();
	/*$pageContent = $pageContent['main_content'];*/
	require_once DIR_TMPL . "compiler-sd.skd";
?>