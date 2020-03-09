<?php
require_once "../../requires-sd/config-sd.php";

	class MyPayPal {
		
		function GetItemTotalPrice($item){
		
			//(Item Price x Quantity = Total) Get total amount of product;
			return $item['ItemPrice'] * $item['ItemQty']; 
		}
		
		function GetProductsTotalAmount($products){
		
			$ProductsTotalAmount=0;

			foreach($products as $p => $item){
				
				$ProductsTotalAmount = $ProductsTotalAmount + $this -> GetItemTotalPrice($item);	
			}
			
			return $ProductsTotalAmount;
		}
		
		function GetGrandTotal($products, $charges){
			
			//Grand total including all tax, insurance, shipping cost and discount
			
			$GrandTotal = $this -> GetProductsTotalAmount($products);
			
			foreach($charges as $charge){
				
				$GrandTotal = $GrandTotal + $charge;
			}
			
			return $GrandTotal;
		}
		function SetExpressCheckout($products, $charges, $noshipping='1'){

			/*print_r($products);exit();*/

			//Parameters for SetExpressCheckout, which will be sent to PayPal
			// echo "<pre>";
			// print_r($products);exit();
			
			$padata  = 	'&METHOD=SetExpressCheckout';
			
			$padata .= 	'&RETURNURL='.urlencode(RETURN_URL);
			$padata .=	'&CANCELURL='.urlencode(CANCEL_RETURN_URL);

			$padata .=	'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE");
			
			foreach($products as $p => $item){
				
				$padata .=	'&L_PAYMENTREQUEST_0_NAME'.$p.'='.urlencode($item['ItemName']);
				$padata .=	'&L_PAYMENTREQUEST_0_NUMBER'.$p.'='.urlencode($item['ItemNumber']);
				$padata .=	'&L_PAYMENTREQUEST_0_DESC'.$p.'='.urlencode($item['ItemDesc']);
				$padata .=	'&L_PAYMENTREQUEST_0_AMT'.$p.'='.urlencode($item['ItemPrice']);
				$padata .=	'&L_PAYMENTREQUEST_0_QTY'.$p.'='. urlencode($item['ItemQty']);
			}		

			
			/* 
			
			//Override the buyer's shipping address stored on PayPal, The buyer cannot edit the overridden address.
			
			$padata .=	'&ADDROVERRIDE=1';
			$padata .=	'&PAYMENTREQUEST_0_SHIPTONAME=J Smith';
			$padata .=	'&PAYMENTREQUEST_0_SHIPTOSTREET=1 Main St';
			$padata .=	'&PAYMENTREQUEST_0_SHIPTOCITY=San Jose';
			$padata .=	'&PAYMENTREQUEST_0_SHIPTOSTATE=CA';
			$padata .=	'&PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE=US';
			$padata .=	'&PAYMENTREQUEST_0_SHIPTOZIP=95131';
			$padata .=	'&PAYMENTREQUEST_0_SHIPTOPHONENUM=408-967-4444';
			
			*/
						
			$padata .=	'&NOSHIPPING='.$noshipping; //set 1 to hide buyer's shipping address, in-case products that does not require shipping
						
			$padata .=	'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($this -> GetProductsTotalAmount($products));
			
			$padata .=	'&PAYMENTREQUEST_0_TAXAMT='.urlencode($charges['TotalTaxAmount']);
			$padata .=	'&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($charges['ShippinCost']);
			$padata .=	'&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($charges['HandalingCost']);
			$padata .=	'&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($charges['ShippinDiscount']);
			$padata .=	'&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($charges['InsuranceCost']);
			$padata .=	'&PAYMENTREQUEST_0_AMT='.urlencode($this->GetGrandTotal($products,$charges));
			$padata .=	'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode(CURRENCY_CODE);
			
			//paypal custom template
			
			$padata .=	'&LOCALECODE='.PAYPAL_LANG; //PayPal pages to match the language on your website;
			$padata .=	'&LOGOIMG=""'; //site logo
			$padata .=	'&CARTBORDERCOLOR=FFFFFF'; //border color of cart
			$padata .=	'&ALLOWNOTE=1';
						
			############# set session variable we need later for "DoExpressCheckoutPayment" #######
			
			$_SESSION['ppl_products'] =  $products;
			$_SESSION['ppl_charges'] 	=  $charges;
			
			$httpParsedResponseAr = $this->PPHttpPost('SetExpressCheckout', $padata);
			// echo "<pre>";
			// print_r($httpParsedResponseAr);exit();
			
			//Respond according to message we receive from Paypal
			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])){

				$paypalmode = (SANDBOX_MODE_ENABLED=='y') ? '.sandbox' : '';
			
				//Redirect user to PayPal store with Token received.
				
				$paypalurl ='https://www'.$paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$httpParsedResponseAr["TOKEN"].'';
				// print_r($paypalurl);exit();
				header('Location: '.$paypalurl);
			}
			else{
				
				//Show error message
				
				echo '<div style="color:red"><b>Error : </b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
				
				echo '<pre>';
					
					print_r($httpParsedResponseAr);
				
				echo '</pre>';
			}	
		}		
		
			
		function DoExpressCheckoutPayment(){

			// print_r("YES YES");exit();
		if(!empty(_SESSION('ppl_products'))&&!empty(_SESSION('ppl_charges'))){
				
				$products=_SESSION('ppl_products');
				
				$charges=_SESSION('ppl_charges');
				
				
				$padata  = 	'&TOKEN='.urlencode(_GET('token'));
				$padata .= 	'&PAYERID='.urlencode(_GET('PayerID'));
				$padata .= 	'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE");
				
				//set item info here, otherwise we won't see product details later	
				
				foreach($products as $p => $item){
					
					$padata .=	'&L_PAYMENTREQUEST_0_NAME'.$p.'='.urlencode($item['ItemName']);
					$padata .=	'&L_PAYMENTREQUEST_0_NUMBER'.$p.'='.urlencode($item['ItemNumber']);
					$padata .=	'&L_PAYMENTREQUEST_0_DESC'.$p.'='.urlencode($item['ItemDesc']);
					$padata .=	'&L_PAYMENTREQUEST_0_AMT'.$p.'='.urlencode($item['ItemPrice']);
					$padata .=	'&L_PAYMENTREQUEST_0_QTY'.$p.'='. urlencode($item['ItemQty']);
				}
				
				$padata .= 	'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($this -> GetProductsTotalAmount($products));
				$padata .= 	'&PAYMENTREQUEST_0_TAXAMT='.urlencode($charges['TotalTaxAmount']);
				$padata .= 	'&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($charges['ShippinCost']);
				$padata .= 	'&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($charges['HandalingCost']);
				$padata .= 	'&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($charges['ShippinDiscount']);
				$padata .= 	'&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($charges['InsuranceCost']);
				$padata .= 	'&PAYMENTREQUEST_0_AMT='.urlencode($this->GetGrandTotal($products, $charges));
				$padata .= 	'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode(CURRENCY_CODE);
				
				//We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
				
				$httpParsedResponseAr = $this->PPHttpPost('DoExpressCheckoutPayment', $padata);
					
				//vdump($httpParsedResponseAr);
				// print_r(strtoupper($httpParsedResponseAr["ACK"]));exit();
				//Check if everything went ok..
				if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])){

					echo '<h2>Success</h2>';
					echo 'Your Transaction ID : '.urldecode($httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"]);
					// exit;
					/*
					//Sometimes Payment are kept pending even when transaction is complete. 
					//hence we need to notify user about it and ask him manually approve the transiction
					*/
					
					if('Completed' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"]){
						
						echo '<div style="color:green">'.PAYMENT_RECEIVED_YOUR_PRODUCT_WILL_BE_SENT.'</div>';
					}
					elseif('Pending' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"]){
						
						echo '<div style="color:red">Transaction Complete, but payment may still be pending! '.
						'If that\'s the case, You can manually authorize this payment in your <a target="_new" href="http://www.paypal.com">Paypal Account</a></div>';
					}
					
					$this->GetTransactionDetails();
				}
				else{
						
					echo '<div style="color:red"><b>Error : </b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
					
					echo '<pre>';
					
						print_r($httpParsedResponseAr);
						
					echo '</pre>';
				}
			}
			else{
				
				// Request Transaction Details
				
				$this->GetTransactionDetails();
			}
		}
				
		function GetTransactionDetails(){
		
			global $db,$sessUserId;
			// we can retrive transection details using either GetTransactionDetails or GetExpressCheckoutDetails
			// GetTransactionDetails requires a Transaction ID, and GetExpressCheckoutDetails requires Token returned by SetExpressCheckOut
			
			$padata = 	'&TOKEN='.urlencode(_GET('token'));
			
			$httpParsedResponseAr = $this->PPHttpPost('GetExpressCheckoutDetails', $padata, PAYPAL_USERNAME, PAYPAL_PASSWORD, PAYPAL_SIGNATURE, SANDBOX_MODE_ENABLED);


			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])){
				
				// print "Way to use decimal in output ".sprintf("%4.2f", $httpParsedResponseAr['AMT']);

					/*Insert Data in payment history*/
						$data = new stdClass();
						$amount=$order_id=$temp_order_id=0;
						$itemName=(string)$httpParsedResponseAr['L_NAME0'];
						$tmp_order_id=$ids=$orderIds="";
						if($itemName!="creditamount"){
							$temp_order_id = $httpParsedResponseAr['L_PAYMENTREQUEST_0_NUMBER0'];
							$data->payment_type = 'pp';
							$date = date("Y-m-d H:i:s");

							/*For Cart Product*/
							
							if($itemName=="cart"){
								$tmp_order_id=base64_decode(base64_decode(base64_decode($temp_order_id)));
								$ids=explode('-', $tmp_order_id);

								/*Add amount to wallet for cart*/
									/*$data->item_id = "0";
									$data->payment_type = 'w';
									$data->user_id =(int)$_SESSION['userId'];
									$data->amount = $httpParsedResponseAr['AMT'];;
									$data->transaction_id = $httpParsedResponseAr['PAYMENTREQUEST_0_TRANSACTIONID'];
									$data->payment_date = date('Y-m-d H:i:s');
									$data->status = "c";
									$data->wallet_status = "paid";
									$data->order_id = "";
									$data->ip_address = get_ip_address();
									$db->insert('tbl_payment_history', (array)$data);*/
								/*End Add amount to wallet for cart*/

									foreach ($ids as $key => $value) {
										$uniqueOrderId = getUniqueOrderId();
										if(!empty($value)){
											$get_order_detail=$db->pdoQuery("SELECT * FROM tbl_temp_orders WHERE id=?",array($value))->result();
											$total_amount=$get_order_detail['wallet_amount']+$get_order_detail['paypal_amount'];
											if($total_amount==0 || $total_amount<1){
												$total_amount=$get_order_detail['total_amount'];
											}
											$order_id = $db->insert('tbl_manage_orders', array('order_type'=>'on','pay_status'=>'y','selected_country'=>$get_order_detail['selected_country'],'order_id'=>$uniqueOrderId,'product_id'=>$get_order_detail['product_id'],"buyer_id"=>$get_order_detail['buyer_id'],"supplier_id"=>$get_order_detail['supplier_id'],'quantity'=>$get_order_detail['quantity'],"order_status"=>$get_order_detail['order_status'],"request_approval_status"=>$get_order_detail['request_approval_status'],"your_offer"=>$get_order_detail['your_offer'],"description"=>$get_order_detail['description'],"shipping_method"=>$get_order_detail['shipping_method'],"total_amount"=>$total_amount,"created_date"=>$date))->lastInsertId();

											$orderIds.=$order_id."-";

											$data->payment_type = 'pp';
											$data->item_id=$get_order_detail['product_id'];
											$amount=$get_order_detail['total_amount'];
											$data->user_id =(int)$_SESSION['userId'];
											$data->amount =$amount;
											$data->transaction_id = $httpParsedResponseAr['PAYMENTREQUEST_0_TRANSACTIONID'];
											$data->payment_date = date('Y-m-d H:i:s');
											$data->status = "c";
											$data->wallet_status = "onHold";
											$data->order_id = $uniqueOrderId;
											$data->ip_address = get_ip_address();
											$db->insert('tbl_payment_history', (array)$data);

											$db->delete("tbl_temp_orders",array("id"=>$get_order_detail['id']));
										}
									}
							}else{
								$uniqueOrderId = getUniqueOrderId();

								$ref=urldecode($httpParsedResponseAr['L_DESC0']);
								/*For Buy Now*/
								$ref_id =  !empty($ref) ? $ref : 0;
								$get_temp_Order_detail=$db->select("tbl_temp_orders",array("*"),array('id'=>$temp_order_id))->result();
								$total_amount=$get_temp_Order_detail['wallet_amount']+$get_temp_Order_detail['paypal_amount'];
								$order_id = $db->insert('tbl_manage_orders', array('order_type'=>'on','pay_status'=>'y','selected_country'=>$get_temp_Order_detail['selected_country'],'order_id'=>$uniqueOrderId,'product_id'=>$get_temp_Order_detail['product_id'],"buyer_id"=>$get_temp_Order_detail['buyer_id'],"supplier_id"=>$get_temp_Order_detail['supplier_id'],'quantity'=>$get_temp_Order_detail['quantity'],"order_status"=>$get_temp_Order_detail['order_status'],"request_approval_status"=>$get_temp_Order_detail['request_approval_status'],"your_offer"=>$get_temp_Order_detail['your_offer'],"description"=>$get_temp_Order_detail['description'],"shipping_method"=>$get_temp_Order_detail['shipping_method'],"total_amount"=>$total_amount,"created_date"=>$date, "buying_request_id" => $ref_id))->lastInsertId();

								if(!empty($ref) && $ref!="null"){
									$db->update("tbl_quotes",array("status"=>"a"),array("id"=>$ref));
								}

								/*Amount Add to wallet*/
									$userWalletAmount = getTablevalue('tbl_users', 'wallet_amount', array('id'=>$_SESSION['userId']));

									$data->item_id=$get_temp_Order_detail['product_id'];									
									$data->user_id =(int)$_SESSION['userId'];
									$data->amount =$httpParsedResponseAr['AMT'] + $userWalletAmount;
									$data->transaction_id = $httpParsedResponseAr['PAYMENTREQUEST_0_TRANSACTIONID'];
									$data->payment_date = date('Y-m-d H:i:s');
									$data->payment_type = 'pp';
									$data->status = "c";
									$data->wallet_status = "onHold";
									$data->order_id = $uniqueOrderId;
									$data->ip_address = get_ip_address();
								/*Amount debit from wallet*/
									$db->insert('tbl_payment_history', (array)$data);

								/*Amount debit from wallet*/


								/*End For Buy Now*/

							}
							/*End For Cart Product*/

						}elseif($itemName=="creditamount"){
							$data->item_id = "0";
							$data->payment_type = 'w';
							$amount=urldecode($httpParsedResponseAr['AMT']);
						}

						if($itemName=="creditamount"){
							$data->user_id =(int)$_SESSION['userId'];
							$data->amount =$amount;
							$data->transaction_id = $httpParsedResponseAr['PAYMENTREQUEST_0_TRANSACTIONID'];
							$data->payment_date = date('Y-m-d H:i:s');
							$data->status = "c";
							$data->wallet_status = "paid";
							$data->order_id = !empty($get_temp_Order_detail['order_id'])?$get_temp_Order_detail['order_id']:"";
							$data->ip_address = get_ip_address();
							$db->insert('tbl_payment_history', (array)$data);
						}
					/*End Insert Data in payment history*/


						if($itemName!="creditamount"){

							if($itemName=="cart"){
								$orders=explode("-", $orderIds);
							}else{
								$orders=array("order_id"=>$order_id);
							}

							foreach ($orders as $key => $value) {
								if(!empty($value)){
									if($itemName!="cart"){
										$value=$value;
									}

									/*$db->update('tbl_manage_orders', array("pay_status"=>"y"),array('id'=>$order_id));*/
									$getOrderDetail=$db->select("tbl_manage_orders",array("*"),array('id'=>$value))->result();

									/*Send Notifications*/
							   		$userDate=$db->pdoQuery("SELECT first_name,last_name FROM tbl_users WHERE id=?",array($getOrderDetail['buyer_id']))->result();
							   		$pronm=$db->pdoQuery("SELECT product_title,user_id FROM tbl_products WHERE id=? ",array($getOrderDetail['product_id']))->result();
							   		$order_request_noti=$db->pdoQuery("SELECT first_name,last_name,order_request_noti,email FROM tbl_users WHERE id=? AND (user_type = ? OR user_type = ?)",array($getOrderDetail['supplier_id'],2,3))->result();
									
									$notifyConstant = 'New order is placed on '.$pronm['product_title']. ' by '.$userDate['first_name'].' '.$userDate['last_name'];

									$notifyUrl = 'manage_orders-sd?notiId='.$value;
									add_admin_notification($notifyConstant, $notifyUrl);

							   		/*$db->insert('tbl_admin_notifications', array("notify_constant"=>'New order is placed on '.$pronm['product_title']. ' by '.$userDate['first_name'].' '.$userDate['last_name'],"uId"=>$_SESSION['userId'],"createdDate"=>Date('Y-m-d H:i:s'),"isActive"=>'y'));   		*/
									$msg_place_quote =str_replace(
													array("#USERNAME#", "#PRODUCT_TITLE#"),
													array($userDate['first_name'].' '.$userDate['last_name'], $pronm['product_title']),
													START_ORDER_BY_SUPPLIER
										);
									$notifyUrl = 'supplier-manage-order';
									add_notification($msg_place_quote, $pronm['user_id'], $notifyUrl);

									$start_order_your_product =str_replace(
										array("#PRODUCT_TITLE#"),
										array($pronm['product_title']),
										START_ORDER_YOUR_PRODUCT
									);
									$notifyUrl = 'buyer-manage-order';

									add_notification($start_order_your_product, $_SESSION['userId'], $notifyUrl);
									
									/*$db->insert('tbl_notification', array("user_id"=>$pronm['user_id'],"created_date"=>Date('Y-m-d H:i:s'),"message"=>$msg_place_quote));	
									$db->insert('tbl_notification', array("user_id"=>$_SESSION['userId'],"created_date"=>Date('Y-m-d H:i:s'),"message"=>$start_order_your_product));*/

									if($order_request_noti['order_request_noti'] == 'y'){
										$arrayCont = array("Username"=>ucfirst($userDate['first_name'].' '.$userDate['last_name']),"greetings"=>ucfirst($order_request_noti['first_name'].' '.$order_request_noti['last_name']), "productDetailLink"=>SITE_URL.'supplier-manage-order');
										sendMail($order_request_noti['email'], 'notify_order_request_noti', $arrayCont);	
					   				}

					   				$supplier_id=getTablevalue('tbl_products', 'user_id', array('id'=>$getOrderDetail['product_id']));

					   				$admin_commision_amount=(ADMIN_COMMISION*$getOrderDetail['total_amount'])/100;
				  	 				$supplier_amount=$getOrderDetail['total_amount']-$admin_commision_amount;

					   				$db->pdoQuery('UPDATE tbl_users set wallet_amount="0" WHERE id=?',array($_SESSION['userId']));

					   				$db->pdoQuery('UPDATE tbl_users set wallet_amount="0" WHERE id=?',array($getOrderDetail['buyer_id']));
					   				$db->insert("tbl_payment_history",array('order_id'=>$getOrderDetail['order_id'],'transaction_id'=>$httpParsedResponseAr['PAYMENTREQUEST_0_TRANSACTIONID'],'user_id'=>$supplier_id,'amount'=>(string)$supplier_amount,'item_id'=>$getOrderDetail['product_id'],'payment_type'=>'sp','status'=>'c','wallet_status'=>'onHold','payment_date'=>date('Y-m-d H:i:s'),"ip_address"=>get_ip_address()));
					   				if($itemName!="cart"){
					   					$db->delete("tbl_temp_orders",array("id"=>$temp_order_id));
									}
								}
							}
							$msgType = $_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>YOUR_ORDER_HAS_BEEN_SUCCESSFULLY_PLACED));
							redirectPage(SITE_URL."buyer-manage-order");

						}elseif($itemName=="creditamount"){
							$amount=urldecode($httpParsedResponseAr['AMT']);
							$getUserWalletAmount=$db->select("tbl_users",array("wallet_amount"),array("id"=>$_SESSION['userId']))->result();
							$user_wallet_amount=sprintf("%4.2f", $getUserWalletAmount['wallet_amount']);
							
							$final_amount=$user_wallet_amount+$amount;
							$db->exec("update tbl_users set wallet_amount='".(string)$final_amount."' where id='".$_SESSION['userId']."'");
							
							$notifyUrl = 'wallet';
							$addAmountMsg =str_replace(
										array("#AMOUNT#", "#CURRENCY_CODE#"),
										array($amount, CURRENCY_CODE),
										AMOUNT_ADDED_WALLET
									);
							add_notification($addAmountMsg, $_SESSION['userId'], $notifyUrl);
							
							//$db->insert('tbl_notification', array("user_id"=>$_SESSION['userId'],"created_date"=>Date('Y-m-d H:i:s'),"message"=>"You have successfully added ".$amount.' '.CURRENCY_CODE." in your wallet"));
							$msgType = $_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>AMOUNT_HAS_BEEN_SUCCESSFULLY_ADDED_TO_WALLET));
							redirectPage(SITE_URL."wallet");

						}

					
				redirectPage(SITE_URL."transaction-successful");

			} 
			else  {
				
				echo '<div style="color:red"><b>GetTransactionDetails failed:</b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
				
				echo '<pre>';
				
					print_r($httpParsedResponseAr);
					
				echo '</pre>';
				redirectPage(CANCEL_RETURN_URL);
			}
		}
		
		function PPHttpPost($methodName_, $nvpStr_) {
				
				// Set up your API credentials, PayPal end point, and API version.
				$API_UserName = urlencode(PAYPAL_USERNAME);
				$API_Password = urlencode(PAYPAL_PASSWORD);
				$API_Signature = urlencode(PAYPAL_SIGNATURE);
				
				$paypalmode = (SANDBOX_MODE_ENABLED=='y') ? '.sandbox' : '';
		
				$API_Endpoint = "https://api-3t".$paypalmode.".paypal.com/nvp";
				$version = urlencode('109.0');
			
				// Set the curl parameters.
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
				//curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
				
				// Turn off the server and peer verification (TrustManager Concept).
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
			
				// Set the API operation, version, and API signature in the request.
				$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
			
				// Set the request as a POST FIELD for curl.
				curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
			
				// Get response from the server.
				$httpResponse = curl_exec($ch);
			
				if(!$httpResponse) {
					exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
				}
			
				// Extract the response details.
				$httpResponseAr = explode("&", $httpResponse);
			
				$httpParsedResponseAr = array();
				foreach ($httpResponseAr as $i => $value) {
					
					$tmpAr = explode("=", $value);
					
					if(sizeof($tmpAr) > 1) {
						
						$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
					}
				}
			
				if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
					
					exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
				}
			
			return $httpParsedResponseAr;
		}
	}
