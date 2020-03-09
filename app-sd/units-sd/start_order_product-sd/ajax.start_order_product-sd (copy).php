<?php
	$reqAuth = false;
	$module = 'start_order_product-sd';
	require_once "../../requires-sd/config-sd.php";
	$reqAuthXml = $_SERVER["SERVER_NAME"].'##'.$module;
	require_once DIR_CLASS."start_order_product-sd.lib.php";
	$return_array = array();
    $ObjAddProduct = new ProductStartOrder();
   	extract($_POST);

   	if($action == 'chk-user-wallet'){
			$userWalletAmount=getTableValue("tbl_users",'wallet_amount',array('id' => $_SESSION['userId']));
	   		$updated_user_wallet_amount=$userWalletAmount-$amount;
			$need_to_pay="";
			if($userWalletAmount==0){
				$need_to_pay=$amount;
				$return_array['from'] = "full_payment_from_paypal";
			}elseif($updated_user_wallet_amount<1){
				$need_to_pay=$amount-$userWalletAmount;
				$return_array['from'] = "half_payment_from_paypal";
			}	
			$return_array['need_to_pay'] = $need_to_pay;
   	}
     if($action == 'start_order' && !empty($quantity) && !empty($your_offer) && !empty($description) && !empty($shipping_method) && !empty($buying_req_id)){
   		$date = date("Y-m-d H:i:s");
   		$pronm=$db->pdoQuery("SELECT isNegotiable FROM tbl_products WHERE id=? ",array($buying_req_id))->result();
   		$request_approval_status=$order_id="";

   		$orderUniqueId  = $db->pdoQuery('select MAX(id) as oid From tbl_manage_orders')->result();
        $order_id = 'OI000001';
        $len   = $orderUniqueId['oid'] + 1;
        $clen  = strlen($len);
        if ($clen > 6) {
            $diff    = $clen - 6;
            $suffixd = '';
            for ($i = 0; $i < $diff; $i++) {
                $suffixd .= '0';
            }
            $order_id .= $suffixd;
        }
        $order_id = substr_replace($order_id, $len, -$clen);

   		if($pronm['isNegotiable'] == 'y'){
			$request_approval_status = 'pending';
   		} else{
   			$request_approval_status = 'accept';
   		}
	   	$return_array['code'] = 200;	


	   	if($_POST['buyNow']!="yes" && $_POST['buyNow']!="wallet"){

	   	$id = $db->insert('tbl_manage_orders', array('selected_country'=>$country_id,'order_id'=>$order_id,'product_id'=>$buying_req_id,"buyer_id"=>$buyer_id,"supplier_id"=>$supplier_id,'quantity'=>$quantity,"order_status"=>'placed',"request_approval_status"=>$request_approval_status,"your_offer"=>$your_offer,"description"=>$description,"shipping_method"=>$shipping_method,"total_amount"=>$total_payable_final_amount,"created_date"=>$date))->lastInsertId();
	   		$return_array['start_order'] = "yes";
			$ObjAddProduct->send_notification($buyer_id,$buying_req_id,$supplier_id);
			if(!empty($ref)){
				$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
			}

	   		/*$userDate=$db->pdoQuery("SELECT first_name,last_name FROM tbl_users WHERE id=?",array($buyer_id))->result();
	   		$pronm=$db->pdoQuery("SELECT product_title,user_id FROM tbl_products WHERE id=? ",array($buying_req_id))->result();
	   		$order_request_noti=$db->pdoQuery("SELECT first_name,last_name,order_request_noti,email FROM tbl_users WHERE id=? AND (user_type = ? OR user_type = ?)",array($supplier_id,2,3))->result();
	  
	   		$db->insert('tbl_admin_notifications', array("notify_constant"=>'New order is placed on '.$pronm['product_title']. ' by '.$userDate['first_name'].' '.$userDate['last_name'],"uId"=>$_SESSION['userId'],"createdDate"=>Date('Y-m-d H:i:s'),"isActive"=>'y'));   		
			$msg_place_quote =str_replace(
							array("#USERNAME#", "#PRODUCT_TITLE#"),
							array($userDate['first_name'].' '.$userDate['last_name'], $pronm['product_title']),
							START_ORDER_BY_SUPPLIER
				);
			$start_order_your_product =str_replace(
							array("#PRODUCT_TITLE#"),
							array($pronm['product_title']),
							START_ORDER_YOUR_PRODUCT
				);
			$db->insert('tbl_notification', array("user_id"=>$pronm['user_id'],"created_date"=>Date('Y-m-d H:i:s'),"message"=>$msg_place_quote));	
			$db->insert('tbl_notification', array("user_id"=>$_SESSION['userId'],"created_date"=>Date('Y-m-d H:i:s'),"message"=>$start_order_your_product));

	   		
	   	if($order_request_noti['order_request_noti'] == 'y'){
				$arrayCont = array("Username"=>ucfirst($userDate['first_name'].' '.$userDate['last_name']),"greetings"=>ucfirst($order_request_noti['first_name'].' '.$order_request_noti['last_name']), "productDetailLink"=>SITE_URL.'supplier-manage-order');
				sendMail($order_request_noti['email'], 'notify_order_request_noti', $arrayCont);	
   			}*/
	   		$_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>ORDER_SUBMIT_SUCCESS));
	   	}elseif($_POST['buyNow']=="yes"){
	   		$userWalletAmount=getTableValue("tbl_users",'wallet_amount',array('id' => $_SESSION['userId']));
	   		$updated_user_wallet_amount=$userWalletAmount-$total_payable_final_amount;
			if($userWalletAmount==0){
				$need_to_pay=$total_payable_final_amount;				
			}elseif($updated_user_wallet_amount<1){
				$need_to_pay=$total_payable_final_amount-$userWalletAmount;
			}
				
	   		$userWalletAmount=(float)$userWalletAmount;
			$need_to_pay=(float)$need_to_pay;

			
			$temp_order_id = $db->insert('tbl_temp_orders', array(
				'product_id' => $buying_req_id,
				'buyer_id' => $buyer_id,
				'supplier_id' => $supplier_id,
				'quantity' => $quantity,
				'order_status' => 'placed',
				'your_offer' => $your_offer,
				'description' => $description,
				'shipping_method' => $shipping_method,
				'wallet_amount' => (string)$userWalletAmount,
				'paypal_amount' => (string)$need_to_pay,
				'created_date' => $date,
				'request_approval_status' => $request_approval_status,
				'order_id' => $order_id
			))->lastInsertId(); 
	   	/*	$temp_order_id = $db->exec("INSERT INTO tbl_temp_orders 
	   			(product_id,buyer_id,supplier_id,quantity,order_status,your_offer,description,shipping_method,wallet_amount,paypal_amount,created_date,request_approval_status,order_id) 
	   			VALUES (".$buying_req_id.",".$buyer_id.",".$supplier_id.",".$quantity.",'placed','".$your_offer."','".$description."','".$shipping_method."',".$userWalletAmount.",".$need_to_pay.",'".$date."','".$request_approval_status."','".$order_id."')");*/

	  	 	$return_array['pay'] = "yes";
	  	 	$return_array['payment_id'] = base64_encode(base64_encode(base64_encode($temp_order_id)));
	   	}elseif($_POST['buyNow']=="wallet"){

	   		$id = $db->insert('tbl_manage_orders', array('selected_country'=>$country_id,'order_id'=>$order_id,'product_id'=>$buying_req_id,"buyer_id"=>$buyer_id,"supplier_id"=>$supplier_id,'quantity'=>$quantity,"order_status"=>'placed',"request_approval_status"=>$request_approval_status,"your_offer"=>$your_offer,"description"=>$description,"shipping_method"=>$shipping_method,"total_amount"=>$total_payable_final_amount,"created_date"=>$date))->lastInsertId();
	   		$updated_user_wallet_amount=0;
	   		$userWalletAmount=getTableValue("tbl_users",'wallet_amount',array('id' => $_SESSION['userId']));
	   		$updated_user_wallet_amount=$userWalletAmount-$total_payable_final_amount;

			if($updated_user_wallet_amount<=0){
				$return_array['insufficient'] = "yes";
			}else{
				$db->exec("INSERT INTO `tbl_payment_history` (order_id,user_id,amount,item_id,payment_type,status,wallet_status,payment_date,ip_address) VALUES ('".$order_id."',".$_SESSION['userId'].",".(string)$total_payable_final_amount.",".$buying_req_id.",'pp','c','paid','".date('Y-m-d H:i:s')."','".get_ip_address()."')");

		  	 	$admin_commision_amount=(ADMIN_COMMISION*$total_payable_final_amount)/100;
		  	 	$supplier_amount=$total_payable_final_amount-$admin_commision_amount;

		  	 	$db->insert("tbl_payment_history",array('order_id'=>$order_id,'user_id'=>$supplier_id,'amount'=>(string)$supplier_amount,'item_id'=>$buying_req_id,'payment_type'=>'sp','status'=>'c','wallet_status'=>'onHold','payment_date'=>date('Y-m-d H:i:s'),"ip_address"=>get_ip_address()));

		  	 	$db->insert("tbl_payment_history",array('order_id'=>$order_id,'user_id'=>"0",'amount'=>(string)$admin_commision_amount,'item_id'=>$buying_req_id,'payment_type'=>'a','status'=>'c','wallet_status'=>'onHold','payment_date'=>date('Y-m-d H:i:s'),"ip_address"=>get_ip_address()));			
		  	 	
		  	 	$db->update("tbl_users",array("wallet_amount"=>(string)$updated_user_wallet_amount),array("id"=>$_SESSION['userId']));
		  	 	$ObjAddProduct->send_notification($buyer_id,$buying_req_id,$supplier_id);
		  	 	if(!empty($ref)){
					$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
				}
		  	 	$return_array['start_order'] = "yes";
		  	 	$_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>ORDER_SUBMIT_SUCCESS));
			}	 	
	   	}
	} 
	if($action == 'change_country_get_shipping_type' && !empty($quantity) && !empty($country_id) && !empty($product_id)){
		$getJsonData=$db->pdoQuery("SELECT p.shipping_type,p.shipping_detail FROM tbl_products AS p WHERE p.id = ?",array($product_id))->result();
		$shipping_methods = '';
		if ($getJsonData['shipping_type']=='others') {
			$obj = (array)json_decode($getJsonData['shipping_detail'],true);
				foreach ($obj as $k=>$val){	  
					if($val['country_id'] == $country_id){
						if($val['shipping_methods'] == 'sea_freight'){
								$shipping_methods .= '<option value="'.$val['shipping_methods'].'">'.SEA_FREIGHT.'</option>';
						}if($val['shipping_methods'] == 'air_cargo'){
							$shipping_methods .= '<option value="'.$val['shipping_methods'].'">'.AIR_CARGO.'</option>';
						}if($val['shipping_methods'] == 'express'){
							$shipping_methods .= '<option value="'.$val['shipping_methods'].'">'.EXPRESS.'</option>';
						}if($val['shipping_methods'] == 'land_transport'){
							$shipping_methods .= '<option value="'.$val['shipping_methods'].'">'.LAND_TRANSPORTATION.'</option>';
						}
					}
			}
			$return_array['option'] = '<option value="">---'.SELECT_SHIPPING_METHODS.'---</option>'.$shipping_methods;
			$return_array['code'] = 200;
		}
	} else  if($action == 'change_shipping_type_get_price' && !empty($country_id) && !empty($product_id) && !empty($shipping_method)){
		$getJsonData=$db->pdoQuery("SELECT p.shipping_type,p.shipping_detail FROM tbl_products AS p WHERE p.id = ?",array($product_id))->result();
		$shipping_methods = '';
		if ($getJsonData['shipping_type']=='others') {
			$obj = (array)json_decode($getJsonData['shipping_detail'],true);
				foreach ($obj as $k=>$val){	  
					if($val['country_id'] == $country_id && $val['shipping_methods']==$shipping_method){
							$amount= $val['shipping_price'];
						}
					}
			}
			$return_array['amount'] = $amount;
			$return_array['code'] = 200;
	}
	
	echo json_encode($return_array);
	exit;
?>