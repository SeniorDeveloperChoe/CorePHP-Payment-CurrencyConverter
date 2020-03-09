<?php
	$reqAuth = false;
	$module = 'start_order_product-sd';
	require_once "../../requires-sd/config-sd.php";
	$reqAuthXml = $_SERVER["SERVER_NAME"].'##'.$module;
	require_once DIR_CLASS."start_order_product-sd.lib.php";
	require_once "paystack.class.php";
	$return_array = array();
    $ObjAddProduct = new ProductStartOrder();
   	extract($_POST);

   	if($action == 'chk-user-wallet'){
   		if (!empty($_SESSION['userId'])) {
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
			
			$return_array['wallet_amount'] = $userWalletAmount;
			$return_array['need_to_pay'] = $need_to_pay;
   		} else {
			$return_array['need_to_pay'] = 'login';
			$_SESSION['redirect'] = 'login_to_continue';
			$_SESSION["toastr_message"] = disMessage(array('type'=>'err','var'=>MSG_ER_LOGIN));
   		}
	}
	if ($action == 'payment_verification') {
		if ($order_id && $order_id != '' && $trxref && $trxref != '' && $reference != '' && $reference) {
			$paystack = new PayStack();
	  	 	$res = $paystack->VerifyPayment($trxref);
			$status = $res['data']['status'];
			$amount = $res['data']['amount'];
			$db->update("tbl_manage_orders",array("pay_status"=>"y"),array("order_id"=>$order_id));
			if ($status == 'success') {
				$return_array['status'] = 'success';
			} else {
				$return_array['status'] = 'failed';
			}
		} else {
			$return_array['status'] = 'failed';
		}
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

	   	if($_POST['buyNow']!="cod" && $_POST['buyNow']!="yes" && $_POST['buyNow']!="paypal" && $_POST['buyNow']!="paystack" && $_POST['buyNow']!="wallet"){

	   		$order_payment_type="on";
	   		if($total_payable_final_amount>START_ORDER_LIMIT){
	   			$order_payment_type="of";
			}
			   
	   		$order_id = getUniqueOrderId();

	   		$id = $db->insert('tbl_manage_orders', array('order_type' => $order_payment_type,'pay_status'=>'n','selected_country'=>$country_id,'order_id'=>$order_id,'product_id'=>$buying_req_id,"buyer_id"=>$buyer_id,"supplier_id"=>$supplier_id,'quantity'=>$quantity,"order_status"=>'placed',"request_approval_status"=>$request_approval_status,"your_offer"=>$your_offer,"description"=>$description,"shipping_method"=>$shipping_method,"total_amount"=>$total_payable_final_amount,"created_date"=>$date))->lastInsertId();
	   		$return_array['start_order'] = "yes";
			$ObjAddProduct->send_notification($buyer_id,$buying_req_id,$supplier_id, $id);
			if(!empty($ref)){
				$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
				$db->update("tbl_manage_orders",array("buying_request_id"=>$ref),array("id"=>$id));
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
	   	} elseif($_POST['buyNow']=="yes"){
			$userWalletAmount=getTableValue("tbl_users",'wallet_amount',array('id' => $_SESSION['userId']));
			$userEmail=getTableValue("tbl_users",'email',array('id' => $_SESSION['userId']));
	   		$updated_user_wallet_amount=$userWalletAmount-$total_payable_final_amount;
			if($userWalletAmount==0){
				$need_to_pay=$total_payable_final_amount;				
			}elseif($updated_user_wallet_amount<1){
				$need_to_pay=$total_payable_final_amount-$userWalletAmount;
			}
				
	   		$userWalletAmount=(float)$userWalletAmount;
			$need_to_pay=(float)$need_to_pay;

			$order_payment_type='on';
			   
	   		$order_id = getUniqueOrderId();

	   		$id = $db->insert('tbl_manage_orders', array(
				   'order_type' => $order_payment_type,
				   'pay_status'=>'n',
				   'selected_country'=>$country_id,
				   'order_id'=>$order_id,
				   'product_id'=>$buying_req_id,
				   "buyer_id"=>$buyer_id,
				   "supplier_id"=>$supplier_id,
				   'quantity'=>$quantity,
				   "order_status"=>'placed',
				   "request_approval_status"=>$request_approval_status,
				   "your_offer"=>$your_offer,
				   "description"=>$description,
				   "shipping_method"=>$shipping_method,
				   "total_amount"=>$total_payable_final_amount,
				   "created_date"=>$date
			))->lastInsertId();
			
			   
			// $ObjAddProduct->send_notification($buyer_id,$buying_req_id,$supplier_id, $id);
			// if(!empty($ref)){
			// 	$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
			// 	$db->update("tbl_manage_orders",array("buying_request_id"=>$ref),array("id"=>$id));
			// }

			$return_array['pay'] = "yes";
			$return_array['order_id'] = $order_id;
	  	 	$return_array['payment_id'] = base64_encode(base64_encode(base64_encode($order_id)));
		   	$paystack = new PayStack();
	  	 	$res = $paystack->ProcessPayment($need_to_pay, $userEmail, $order_id);
			$return_array['pay'] = "paystack";
			$return_array['pay_url'] = $res;
		} elseif($_POST['buyNow']=="paypal"){
			$userWalletAmount=getTableValue("tbl_users",'wallet_amount',array('id' => $_SESSION['userId']));
			$userEmail=getTableValue("tbl_users",'email',array('id' => $_SESSION['userId']));
	   		$updated_user_wallet_amount=$userWalletAmount-$total_payable_final_amount;
			if($userWalletAmount==0){
				$need_to_pay=$total_payable_final_amount;				
			}elseif($updated_user_wallet_amount<1){
				$need_to_pay=$total_payable_final_amount-$userWalletAmount;
			}
				
	   		$userWalletAmount=(float)$userWalletAmount;
			$need_to_pay=(float)$need_to_pay;

			$order_payment_type='on';
			   
	   		$order_id = getUniqueOrderId();

	   		$id = $db->insert('tbl_manage_orders', array(
				   'order_type' => $order_payment_type,
				   'pay_status'=>'n',
				   'selected_country'=>$country_id,
				   'order_id'=>$order_id,
				   'product_id'=>$buying_req_id,
				   "buyer_id"=>$buyer_id,
				   "supplier_id"=>$supplier_id,
				   'quantity'=>$quantity,
				   "order_status"=>'placed',
				   "request_approval_status"=>$request_approval_status,
				   "your_offer"=>$your_offer,
				   "description"=>$description,
				   "shipping_method"=>$shipping_method,
				   "total_amount"=>$total_payable_final_amount,
				   "created_date"=>$date
			))->lastInsertId();
			   
			// $ObjAddProduct->send_notification($buyer_id,$buying_req_id,$supplier_id, $id);
			// if(!empty($ref)){
			// 	$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
			// 	$db->update("tbl_manage_orders",array("buying_request_id"=>$ref),array("id"=>$id));
			// }

			$return_array['pay'] = "yes";
			$return_array['order_id'] = $order_id;
	  	 	$return_array['payment_id'] = base64_encode(base64_encode(base64_encode($order_id)));
			   
			
			$paypal = new MyPayPal();
	  	 	$res = $paypal->SetExpressCheckout($need_to_pay, $userEmail, $order_id);
			
		   	$return_array['pay'] = "paypal";
			$return_array['pay_url'] = $res;
		} elseif($_POST['buyNow']=="paystack"){
			if($selected_currency_code == "NGN") {
				$userWalletAmount=getTableValue("tbl_users",'wallet_amount',array('id' => $_SESSION['userId']));
				$userEmail=getTableValue("tbl_users",'email',array('id' => $_SESSION['userId']));
				$updated_user_wallet_amount=$userWalletAmount-$total_payable_final_amount;
				if($userWalletAmount==0){
					$need_to_pay=$total_payable_final_amount;				
				}elseif($updated_user_wallet_amount<1){
					$need_to_pay=$total_payable_final_amount-$userWalletAmount;
				}
					
				$userWalletAmount=(float)$userWalletAmount;
				$need_to_pay=(float)$need_to_pay;

				$order_payment_type='on';
				
				$order_id = getUniqueOrderId();

				$id = $db->insert('tbl_manage_orders', array(
					'order_type' => $order_payment_type,
					'pay_status'=>'n',
					'selected_country'=>$country_id,
					'order_id'=>$order_id,
					'product_id'=>$buying_req_id,
					"buyer_id"=>$buyer_id,
					"supplier_id"=>$supplier_id,
					'quantity'=>$quantity,
					"order_status"=>'placed',
					"request_approval_status"=>$request_approval_status,
					"your_offer"=>$your_offer,
					"description"=>$description,
					"shipping_method"=>$shipping_method,
					"total_amount"=>$total_payable_final_amount,
					"created_date"=>$date
				))->lastInsertId();
				
				// $ObjAddProduct->send_notification($buyer_id,$buying_req_id,$supplier_id, $id);
				// if(!empty($ref)){
				// 	$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
				// 	$db->update("tbl_manage_orders",array("buying_request_id"=>$ref),array("id"=>$id));
				// }

				$return_array['pay'] = "paystack";
				$return_array['order_id'] = $order_id;
				$return_array['payment_id'] = base64_encode(base64_encode(base64_encode($order_id)));
				$paystack = new PayStack();
				$res = $paystack->ProcessPayment($need_to_pay, $userEmail, $order_id);
				$return_array['pay'] = "paystack";
				$return_array['pay_url'] = $res;
			}
		} elseif($_POST['buyNow'] == 'cod') {
			$order_payment_type='cod';
			   
	   		$order_id = getUniqueOrderId();

	   		$id = $db->insert('tbl_manage_orders', array('order_type' => $order_payment_type,'pay_status'=>'n','selected_country'=>$country_id,'order_id'=>$order_id,'product_id'=>$buying_req_id,"buyer_id"=>$buyer_id,"supplier_id"=>$supplier_id,'quantity'=>$quantity,"order_status"=>'placed',"request_approval_status"=>$request_approval_status,"your_offer"=>$your_offer,"description"=>$description,"shipping_method"=>$shipping_method,"total_amount"=>$total_payable_final_amount,"created_date"=>$date))->lastInsertId();
	   		$return_array['start_order'] = "yes";
			$ObjAddProduct->send_notification($buyer_id,$buying_req_id,$supplier_id, $id);
			if(!empty($ref)){
				$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
				$db->update("tbl_manage_orders",array("buying_request_id"=>$ref),array("id"=>$id));
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
		} elseif($_POST['buyNow']=="wallet"){

			$selected_currency_code = $_POST['selected_currency'];
			$selected_currency_rate = $_POST['selected_currency_rate'];

			$selected_currency_id = getTableValue("tbl_currency",'id',array('currency' => $selected_currency_code));

			$order_id = getUniqueOrderId();
	   		$updated_user_wallet_amount=0;
	   		$userWalletAmount=getTableValue("tbl_users",'wallet_amount',array('id' => $_SESSION['userId']));

	   		$ngn_total_payable_final_amount = $total_payable_final_amount;
			// [20-03-07 by Ming Lau...] change currency
			if($selected_currency_code == "NGN") {
				$updated_user_wallet_amount=$userWalletAmount-$total_payable_final_amount;				
			}
			else {
				$ngn_total_payable_final_amount = round(doubleval($total_payable_final_amount / $selected_currency_rate), 2); // change to NGN  
				$updated_user_wallet_amount = $userWalletAmount - $ngn_total_payable_final_amount;
			}

			// echo $updated_user_wallet_amount." : ".$ngn_total_payable_final_amount;
			// exit();

			if($updated_user_wallet_amount<0){
				$return_array['insufficient'] = "yes";
			}else{
				$uniqueTransactionId = getUniqueTransactionId();
				$id = $db->insert('tbl_manage_orders', array('selected_country'=>$country_id,'order_type' => 'on','order_id'=>$order_id, 'product_id'=>$buying_req_id,"buyer_id"=>$buyer_id,"supplier_id"=>$supplier_id,'quantity'=>$quantity,"order_status"=>'placed',"request_approval_status"=>$request_approval_status,"your_offer"=>$your_offer,"description"=>$description,"shipping_method"=>$shipping_method,"total_amount"=>"".$ngn_total_payable_final_amount,"created_date"=>$date))->lastInsertId();

				$db->exec("INSERT INTO `tbl_payment_history` (order_id,user_id,amount,item_id,payment_type,status,wallet_status,payment_date,ip_address, transaction_id, currency_id) VALUES ('".$order_id."',".$_SESSION['userId'].",".(string)$total_payable_final_amount.",".$buying_req_id.",'pp','c','onHold','".date('Y-m-d H:i:s')."','".get_ip_address()."', '".$uniqueTransactionId."',".$selected_currency_id.")");

		  	 	$admin_commision_amount=(ADMIN_COMMISION*$total_payable_final_amount)/100;
		  	 	$supplier_amount=$total_payable_final_amount-$admin_commision_amount;

		  	 	$db->insert("tbl_payment_history",array('order_id'=>$order_id,'user_id'=>$supplier_id,'amount'=>(string)$supplier_amount,'item_id'=>$buying_req_id,'payment_type'=>'sp','status'=>'c','wallet_status'=>'onHold','payment_date'=>date('Y-m-d H:i:s'),"ip_address"=>get_ip_address(), 'transaction_id'=>$uniqueTransactionId, 'currency_id' => $selected_currency_id));			
		  	 	$db->update("tbl_users",array("wallet_amount"=>(string)$updated_user_wallet_amount),array("id"=>$_SESSION['userId']));
		  	 	
		  	 	$db->update("tbl_manage_orders",array("pay_status"=>"y"),array("id"=>$id));
		  	 	if(!empty($ref)){
					$db->update("tbl_manage_orders",array("buying_request_id"=>$ref),array("id"=>$id));
				}
		  	 	$ObjAddProduct->send_notification($buyer_id,$buying_req_id,$supplier_id, $id);
		  	 	if(!empty($ref)){
					$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
				}
		  	 	$return_array['start_order'] = "yes";
		  	 	$_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>YOU_ARE_SUCCESSFULLY_PLACED_YOUR_ORDER));
			}
	   	}
	}
	// when user select the country in start-order-page, call 
	if($action == 'change_country_get_shipping_type' && !empty($quantity) && !empty($country_id) && !empty($product_id)){
		/*$getJsonData=$db->pdoQuery("SELECT p.shipping_type,p.shipping_detail FROM tbl_products AS p WHERE p.id = ?",array($product_id))->result();*/

		// get shipping method
		$getJsonData=$db->pdoQuery("SELECT * FROM tbl_shipping_management AS s WHERE s.product_id = ? AND min_range<=? AND max_range>=?",array($product_id,$quantity,$quantity))->result();
		$shipping_methods = '';
		if ($getJsonData['shipping_type']=='others') {
			$obj = (array)json_decode($getJsonData['shipping_detail'],true);
				foreach ($obj as $k=>$val){	  
					if($val['country_id'] == $country_id){

						/*if($val['shipping_methods'] == 'sea_freight'){
								$shipping_methods .= '<option value="'.$val['shipping_methods'].'">'.SEA_FREIGHT.'</option>';
						}if($val['shipping_methods'] == 'air_cargo'){
							$shipping_methods .= '<option value="'.$val['shipping_methods'].'">'.AIR_CARGO.'</option>';
						}*/

						if($val['shipping_methods'] == 'express'){
							$shipping_methods .= '<option value="'.$val['shipping_methods'].'">'.EXPRESS.'</option>';
						}if($val['shipping_methods'] == 'standard'){
							$shipping_methods .= '<option value="'.$val['shipping_methods'].'">'.LBL_STANDARD.'</option>';
						}
					}
				}
			
			$return_array['option'] = '<option value="">---'.SELECT_SHIPPING_METHODS.'---</option>'.$shipping_methods;
			$return_array['code'] = 200;
		}else{
			$return_array['code'] = 200;
			$shipping_methods = '<option value="express">'.EXPRESS.'</option><option value="standard">'.LBL_STANDARD.'</option>';

			$return_array['option'] = '<option value="">---'.SELECT_SHIPPING_METHODS.'---</option>'.$shipping_methods;
		}
	} else  if($action == 'change_shipping_type_get_price' && !empty($country_id) && !empty($product_id) && !empty($shipping_method)){
		/*$getJsonData=$db->pdoQuery("SELECT p.shipping_type,p.shipping_detail FROM tbl_products AS p WHERE p.id = ?",array($product_id))->result();*/
		$getJsonData=$db->pdoQuery("SELECT * FROM tbl_shipping_management AS s WHERE s.product_id = ? AND min_range<=? AND max_range>=?",array($product_id,$quantity,$quantity))->result();
		$shipping_methods = '';
		$amount = $shipping_days_text = "";
		if ($getJsonData['shipping_type']=='others'){
			$obj = (array)json_decode($getJsonData['shipping_detail'],true);
				foreach ($obj as $k=>$val){	  
					if($val['country_id'] == $country_id && $val['shipping_methods']==$shipping_method){
							$amount= $val['shipping_price'];
							$shipping_days_text= $val['shipping_days'];
						}
					}
		}else if($getJsonData['shipping_type']=='everywhere'){
			$amount= $getJsonData['shipping_detail'];
		}else if($getJsonData['shipping_type']=='free'){
			$amount= $getJsonData['shipping_detail'];
		}else{
			$getDefaultAmount= $db->pdoQuery("SELECT id,default_shipping_charge FROM tbl_products WHERE id = ?",array($product_id))->result();
			$amount=$getDefaultAmount['default_shipping_charge'];
		}

		$return_array['amount'] = $amount;
		$return_array['shipping_days_text'] = $shipping_days_text;

		$return_array['code'] = 200;
	}else  if($action == 'chk-shipping' && !empty($pid)){
		$chk_shipping=$db->pdoQuery("SELECT count(id) AS total FROM tbl_shipping_management WHERE product_id=?",array($pid))->result();
		
		if(empty($chk_shipping['total']) || $chk_shipping['total']==0){
			$return_array['response'] = "";
			$get_suppier=$db->pdoQuery("SELECT product_title,user_id FROM tbl_products WHERE id=?",array($pid))->result();
			$msg_noti =str_replace(
								array("#PRODUCT_NAME#"),
								array($get_suppier['product_title']),
								BUYER_TRIED_TO_PLACE_AN_ORDER
				);
			$db->insert('tbl_notification', array("user_id"=>$get_suppier['user_id'],"created_date"=>Date('Y-m-d H:i:s'),"message"=>$msg_noti));
		}else{
			$return_array['response'] = "success";
		}
	}
	// [20-03-03 by Ming Lau] update currency rate and get selected currency rate
	else if($action == 'update_currency_rate') {
		currencyConverter_fixer($db);
	}
	else if($action == 'get_selected_currency_rate') {
		$selected_currency_code = $_POST['currency_code'];
		$get_currency_rate = $db->pdoQuery("SELECT rate FROM tbl_currency WHERE currency=?",array($selected_currency_code))->result();
		$return_array['currency_rate'] = $get_currency_rate['rate'];
	}

	// [20-03-02 by Ming... ] currency converter functions list
	function currency_converter_iban(string $from_currency, string $to_currency, double $amount) {
		$curl = curl_init();
 	
		$post = [
		    'format' => 'json',
		    'api_key' => '[YOUR_API_KEY]',
		    'from'   => $from_currency,
		    'to' => $to_currency,
		    'amount' => $amount
		];

		curl_setopt_array($curl, array(
		    CURLOPT_URL => 'https://api.iban.com/clients/api/currency/convert/',
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_POSTFIELDS => $post
		));
		 
		$output = curl_exec($curl);
		$result = json_decode($output);
		 
		return $result;
		 
		curl_close($curl);
	}

	function currency_converter_google_curl($from_currency, $to_currency, $amount) {

		$amount    	= urlencode($amount);
		$from    	= urlencode($from_currency);
		$to        	= urlencode($to_currency);

		$amount    	= $amount;
		$from    	= $from_currency;
		$to        	= $to_currency;

		$url = "http://www.google.com/finance/converter?a=$amount&from=$from&to=$to"; 
 
		 $request = curl_init(); 
		 $timeOut = 0; 
		 curl_setopt ($request, CURLOPT_URL, $url); 
		 curl_setopt ($request, CURLOPT_RETURNTRANSFER, 1); 
		 
		 curl_setopt ($request, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)"); 
		 curl_setopt ($request, CURLOPT_CONNECTTIMEOUT, $timeOut); 
		 $response = curl_exec($request); 
		 curl_close($request); 
		 
		 return $response;
	} 

	if(isset($_POST['convert_currency']))
	{
		$amount=$_POST['amount'];
		$from=$_POST['convert_from'];
		$to=$_POST['convert_to'];
			
		$rawData = currency_converter($from,$to,$amount);
		$regex = '#\<span class=bld\>(.+?)\<\/span\>#s';
		preg_match($regex, $rawData, $converted);
		$result = $converted[0];
 		echo $result;
	}

	function currencyConverter_google($from_currency, $to_currency, $amount) {
		$from_Currency = urlencode($from_currency);
		$to_Currency = urlencode($to_currency);
		$encode_amount = urlencode($amount);

		$url = "https://www.google.com/finance/converter?a=$encode_amount&from=$from_Currency&to=$to_Currency";
		$get = file_get_contents($url);
		$preg_match("/<span class=bid>(.*)<\/span>/",$data, $converted);
		return $converted[1];
	}

	function convertCurrency_currconv($from_currency, $to_currency){
	  $apikey = '1305a3510e4497c78a04';

	  $from_Currency = urlencode($from_currency);
	  $to_Currency = urlencode($to_currency);
	  $query =  "{$from_Currency}_{$to_Currency}";

	  // change to the free URL if you're using the free version
	  $json = file_get_contents("https://free.currconv.com/api/v7/convert?q=".$query."&compact=ultra&apiKey=".$apikey);
	  $obj = json_decode($json, true);

	  $currency_rate = floatval($obj["$query"]);

	  return $currency_rate;
	}

	function currencyConverter_fixer($db) {
		// set API Endpoint and API key 
		$endpoint = 'latest';
		$access_key = 'd70691805608e9080911388dc1660dbb';
		$base_currency = "NGN";

		//--- Initialize CURL:
		// default -> EUR
		// $ch = curl_init('http://data.fixer.io/api/'.$endpoint.'?access_key='.$access_key.'');

		// default -> NGN
		$ch = curl_init('http://data.fixer.io/api/'.$endpoint.'?access_key='.$access_key.'&base='.$base_currency.'');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Store the data:
		$json = curl_exec($ch);
		curl_close($ch);

		// Decode JSON response:
		$exchangeRates = json_decode($json, true);

		if($exchangeRates['base'] == "NGN") {
			if($exchangeRates['success']){
				foreach ($exchangeRates['rates'] as $currency_code => $rate) {
					
					$currency_id = "";
					if($currency_code == "USD" || $currency_code == "EUR" || $currency_code == "CAD" || $currency_code == "HKD" || $currency_code == "RUB" || $currency_code == "ILS" || $currency_code == "SGD" || $currency_code == "GBP" || $currency_code == "JPY") {

						echo $currency_code.":".round($rate, 10).", ";
						$currency_id = $db->query("UPDATE tbl_currency SET rate=".$rate." WHERE currency='".strtoupper($currency_code)."'");	
					}
				}
			}
			else {
				return 'Currency updating is failed.';	
			}
		}
		else {
			return 'Currency updating is failed.';	
		}
	}

	echo json_encode($return_array);
	exit;
?>