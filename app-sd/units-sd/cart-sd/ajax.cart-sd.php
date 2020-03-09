<?php
	$reqAuth = false;
	$module = 'cart-sd';
	require_once "../../requires-sd/config-sd.php";
	$reqAuthXml = $_SERVER["SERVER_NAME"].'##'.$module;
	require_once DIR_CLASS."cart-sd.lib.php";
	$return_array = array();
    $ObjCart = new Cart();

   extract($_POST);   

   if($action=="getSupplierDetail" && !empty($supplierid)){
   		$supID=base64_decode(base64_decode($supplierid));
   		$return_array=$db->pdoQuery("SELECT c.location,c.company_name,c.contact_no_1,c.contact_person_name,CONCAT(u.first_name,' ',u.last_name) AS supplierName
   										FROM tbl_company AS c 
   										INNER JOIN tbl_users AS u ON u.id=c.user_id
   										WHERE c.id=?",array($supID))->result();

   }elseif($action=="deleteItem" && !empty($itemid)){
   		$db->delete("tbl_cart",array("id"=>base64_decode(base64_decode($itemid))));
   		$return_array['content'] = "success";
   }elseif($action == 'upload_orderDocs') {
      $return_array['code'] = 100;
      $file_array = array(
                  "name" => $_FILES['attechments']['name'],
                  "type" => $_FILES['attechments']['type'],
                  "tmp_name" => $_FILES['attechments']['tmp_name'],
                  "error" => $_FILES['attechments']['error'],
                  "size" => $_FILES['attechments']['size']
               );

         $file_name = uploadFile($file_array, DIR_MSG_DOCS, SITE_MSG_DOCS);
         $mainToken=$itemid.'_'.$token;
         $db->insert('temp_product_images', array('user_id'=>$_SESSION['userId'],'token'=>$itemid.'_'.$token,'actual_file_name'=>!empty($file_array['name'])?$file_array['name']:"", 'file_name'=>$file_name['file_name']));
         $return_array['src'] = getImage(DIR_MSG_DOCS.$file_name['file_name'], 24, 24);
         $return_array['code'] = 200;
         $return_array['all_photos'] = $ObjCart->getAllPics($mainToken);

   }else if($action == 'del_image' && !empty($id)){
         $mainToken=$itemid.'_'.$token;
         $db->delete('temp_product_images', array('user_id'=>$sessUserId,'token'=>$mainToken, 'id'=>$id));
         $return_array['code'] = 200;
         $return_array['id'] = $id;
   }

   
	echo json_encode($return_array);
	exit;
?>