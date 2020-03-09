<?php
	$reqAuth = true;
	$module = 'wallet-sd';
	require_once "../../requires-sd/config-sd.php";
	$reqAuthXml = $_SERVER["SERVER_NAME"].'##'.$module;
	require_once DIR_CLASS."wallet-sd.lib.php";
	require_once "paystack.class.php";
	$return_array = array('code'=>100, 'message'=>SOMETHING_WENT_WRONG);

	$paystack = new PayStack();
	$mainObj = new wallet($module);
	extract($_POST);



	$selected_currency_code = $_POST['currency_code'];
	$selected_currency_rate =  $_POST['currency_rate'];

	$payment_date = date("Y-m-d H:i:s");
	$return_array['history_date'] = $payment_date;

	$query = $db->pdoQuery("SELECT id, rate FROM tbl_currency WHERE currency=?", array('USD'))->result();
	$usd_currency_rate = $query['rate'];

	$query = $db->pdoQuery("SELECT id, rate FROM tbl_currency WHERE currency=?", array($selected_currency_code))->result();
	$selected_currency_id = $query['id'];
	
	$return_array['changed_amount'] = $amount.' '.$selected_currency_code;

	if($action=="Request_for_redeem" && !empty($amount)){
		
		$changed_amount = round(floatval($amount / $selected_currency_rate), 2); // change to NGN 

		if($selected_currency_code != "NGN") {
			echo "redeem_no_ngn";
			exit();
			// paypal 
			$qry = $db->pdoQuery("SELECT wallet_amount,paypal_id FROM tbl_users WHERE id=?", array($sessUserId))->result();
			if(!empty($qry['paypal_id'])){
				if($qry['wallet_amount']>= $changed_amount){

					$db->insert('tbl_payment_history', array('user_id'=>$sessUserId,"payment_type"=>"r","payment_date"=>$payment_date,"amount"=>"".$changed_amount,'ip_address'=>get_ip_address(),'wallet_status'=>"pending", 'currency_id' => $selected_currency_id));

					$changed_wallet_amount = round(floatval($qry['wallet_amount'] * $selected_currency_rate), 2); // change to selected_currency
					$final_amount_ngn = $qry['wallet_amount'] - $changed_amount;
					$db->update("tbl_users",array("wallet_amount"=>(string)$final_amount_ngn),array("id"=>$sessUserId));
					$total = $changed_wallet_amount - $amount;

					$return_array['amount'] = round($total, 2).' '.$selected_currency_code;
					$return_array['code'] = 200;
					$_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>REDEEM_REQUEST_SUCCESS));	
				}else{
					$return_array['amount'] = $qry['wallet_amount'].' '.$selected_currency_code;
					$return_array['code'] = 300;
					$_SESSION["toastr_message"] = disMessage(array('type'=>'err','var'=>LBL_INSUFFICIENT_MSG));
				}			
			} else{
				$return_array['code'] = 400;
				$_SESSION["toastr_message"] = disMessage(array('type'=>'err','var'=>MSG_ADD_PAYPAL_FOR_COMPL_REDEEM_REQ));
			}
		}
		else {
			// echo "redeem_ngn";
			// exit();
			// paystack
			$query = $db->pdoQuery("SELECT wallet_amount, email FROM tbl_users WHERE id=?", array($sessUserId))->result();
			$userWalletAmount = $query['wallet_amount'];
			$userEmail = $query['email'];

			if(!empty($userEmail)) {
				if($query['wallet_amount'] >= $amount) {
					// calling paystack refund
					// $bank_info = $db->pdoQuery("SELECT bank_account_number, bank_id FROM tbl_company WHERE id=?", array($sessUserId))->result();
					// $bank_account_number = $bank_info['bank_account_number'];
					
					// $bank_code_info = $db->pdoQuery("SELECT code FROM tbl_bank WHERE id=?", array($bank_info['bank_id']))->result();
					// $bank_code = $bank_info['code'];

					// TODO Refund API no test
	  	 			$res_paystack = $paystack->createTransferRecipient();
		  	 		// $return_array['pay_url'] = $res_paystack;

		  	 		// $db->insert('tbl_payment_history', array('user_id'=>$sessUserId,"payment_type"=>"r","payment_date"=>$payment_date,"amount"=>"".$amount,'ip_address'=>get_ip_address(),'wallet_status'=>"pending", 'currency_id' => $selected_currency_id));

					$final_amount = $userWalletAmount - $amount;
					// $db->update("tbl_users",array("wallet_amount"=>(string)$final_amount),array("id"=>$sessUserId));

					$return_array['amount'] = $final_amount.' '.CURRENCY_CODE;
					$return_array['code'] = 200;
					
					$_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>REDEEM_REQUEST_SUCCESS));
				}
				else {
					$return_array['amount'] = $query['wallet_amount'].' '.CURRENCY_CODE;
					$return_array['code'] = 300;
					$_SESSION["toastr_message"] = disMessage(array('type'=>'err','var'=>LBL_INSUFFICIENT_MSG));
				}
			}
			else{
				$return_array['code'] = 400;
				$_SESSION["toastr_message"] = disMessage(array('type'=>'err','var'=>"Please add your PayStack email account first to complete redeem request"));
			}
		}
	}
	else if($action=="Request_for_add" && !empty($amount)) {
		$wallet_status = "inWallet";
		if($selected_currency_code != "NGN") {
			// echo "amount_usd";
			// exit();
			// paypal 
			$changed_amount = round(doubleval($amount / $selected_currency_rate), 2); // change to NGN 
			
			$qry = $db->pdoQuery("SELECT wallet_amount,paypal_id FROM tbl_users WHERE id=?", array($sessUserId))->result();
			if(!empty($qry['paypal_id'])){

				$db->insert('tbl_payment_history', array('user_id'=>$sessUserId,"payment_type"=>"aw","payment_date"=>date("Y-m-d H:i:s"),"amount"=>"".$amount,'ip_address'=>get_ip_address(),'wallet_status'=>$wallet_status, 'currency_id' => $selected_currency_id));

				$changed_wallet_amount = round(floatval($qry['wallet_amount'] * $selected_currency_rate), 2); // change to selected_currency
				$final_amount =$qry['wallet_amount'] + $changed_amount;
				$db->update("tbl_users",array("wallet_amount"=>(string)$final_amount),array("id"=>$sessUserId));
				$total = $changed_wallet_amount + $amount;

				$return_array['amount'] = round($total, 2).' '.$selected_currency_code;
				$return_array['code'] = 200;
				$_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>"Addion request has been submitted successfully"));	
			} else{
				$return_array['code'] = 400;
				$_SESSION["toastr_message"] = disMessage(array('type'=>'err','var'=>"Please add your PayPal email account first to complete addition request."));
			}
		}
		else {
			echo "amount_ngn";
			exit();
			// paystack
			$query = $db->pdoQuery("SELECT wallet_amount, email FROM tbl_users WHERE id=?", array($sessUserId))->result();
			$userWalletAmount = $query['wallet_amount'];
			$userEmail = $query['email'];

			// echo $selected_currency_id;
			// exit();

			if(!empty($query['email'])) {
				$db->insert('tbl_payment_history', array('user_id'=>$sessUserId,"payment_type"=>"aw","payment_date"=>date("Y-m-d H:i:s"),"amount"=>"".$amount,'ip_address'=>get_ip_address(),'wallet_status'=>$wallet_status, 'currency_id' => $selected_currency_id));

				$final_amount = doubleval($userWalletAmount + $amount);
				$db->update("tbl_users",array("wallet_amount"=>"".$final_amount),array("id"=>$sessUserId));

				$return_array['amount'] = $final_amount.' '.CURRENCY_CODE;
				
				// calling paystack charge to admin
	  	 		$res_paystack = $paystack->ProcessPayment($amount, $userEmail);
	  	 		$return_array['pay_url'] = $res_paystack;
	  	 		$return_array['code'] = 200;
	  	 		$_SESSION["toastr_message"] = disMessage(array('type'=>'suc','var'=>"Addion request has been submitted successfully"));
			}
			else {
				$return_array['code'] = 400;
				$_SESSION["toastr_message"] = disMessage(array('type'=>'err','var'=>"Please add your PayStack email account first to complete addition request"));
			} 
		}
	}

	echo json_encode($return_array);
	exit;
?>