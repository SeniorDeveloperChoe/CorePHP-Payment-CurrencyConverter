<?php

include_once("paypal_functions.php");
include_once("paypal.class.php");

	$paypal= new MyPayPal();
	$action = isset($_REQUEST["action"]) ? trim($_REQUEST["action"]):"";
	$id = isset($_REQUEST["id"]) ? trim($_REQUEST["id"]):"";
	$encodedIds=$id;
	extract($_REQUEST);

	//Post Data received from product list page.
	if (!empty($action) && ($action == 'payNow' || $action=="wallet" || $action=="cart")) {


		$PayPalData=$ItemName=$ItemPrice=$ItemNumber=$ItemDesc="";
		$total_order_amount= $partialWalletAmount = 0;
		if((isset($id) && $id!="") || $action=="wallet"){
			if(!empty($id)){
				$order_id=base64_decode(base64_decode(base64_decode($id)));
				if($action=="cart"){
					$ids=explode('-', $order_id);
					foreach ($ids as $key => $value) {
						if(!empty($value)){
							$get_product_amount=$db->pdoQuery("SELECT total_amount FROM tbl_temp_orders WHERE id=?",array($value))->result();
							$total_order_amount+=$get_product_amount['total_amount'];
						}
					}
				}
			}else{
				$id=$_SESSION['userId'];
			}

			if($action == 'payNow'){
				$id = base64_decode(base64_decode(base64_decode($id)));
			 	$PayPalData=$db->pdoQuery("SELECT mo.wallet_amount,mo.paypal_amount,p.product_slug,p.product_title FROM tbl_temp_orders  AS mo INNER JOIN tbl_products AS p ON p.id=mo.product_id WHERE  mo.id=$id")->result();
			 	$ItemName=$PayPalData['product_title'];;
			 	$ItemPrice=$PayPalData['paypal_amount'];
			 	$ItemNumber=$id;
			 	if(!empty($ref) && $ref!="null"){
			 		$ItemDesc=$ref;
			 	}else{
			 		$ItemDesc="Goods item";
			 	}
			 	$partialWalletAmount = $PayPalData['wallet_amount'];
			}elseif($action == 'wallet'){
				$ItemName="creditamount";
				$ItemPrice=round($_POST['wallet_amount'],2);
				$ItemNumber=$_SESSION['userId'];
				$ItemDesc="Add to wallet";
			}elseif($action == 'cart'){	
				$userWalletAmount=getTableValue("tbl_users",'wallet_amount',array('id' => $_SESSION['userId']));
		   		$updated_user_wallet_amount=$userWalletAmount-$total_order_amount;
				$need_to_pay="";
				if($userWalletAmount==0){
					$need_to_pay=$total_order_amount;
				}elseif($updated_user_wallet_amount<1){
					$need_to_pay=$total_order_amount-$userWalletAmount;
				}

				$ItemName=$action;
				$ItemPrice=round($need_to_pay,2);
				$ItemNumber=$encodedIds;
				$ItemDesc="Cart orders";
			}

			 // echo "<pre>";
			 // print_r($PayPalData);exit();

			//-------------------- prepare products -------------------------

			//Mainly we need 4 variables from product page Item Name, Item Price, Item Number and Item Quantity.

			//Please Note : People can manipulate hidden field amounts in form,
			//In practical world you must fetch actual price from database using item id. Eg:
			//$products[0]['ItemPrice'] = $mysqli->query("SELECT item_price FROM products WHERE id = Product_Number");

			$products = [];



			// set an item via POST request

			$products[0]['ItemName'] = $ItemName; //Item Name
			$products[0]['ItemPrice'] =$ItemPrice; //Item Price
			$products[0]['ItemNumber'] = $ItemNumber; //Item Number
			$products[0]['ItemDesc'] = $ItemDesc; //Item Number
			$products[0]['ItemQty']	= 1; // Item Quantity
			$products[0]['PartialAmount']	= 1; // Item Quantity
			$products[0]['PartialWalletAmount']	= $partialWalletAmount; // Item Quantity



			/*
			$products[0]['ItemName'] = 'my item 1'; //Item Name
			$products[0]['ItemPrice'] = 0.5; //Item Price
			$products[0]['ItemNumber'] = 'xxx1'; //Item Number
			$products[0]['ItemDesc'] = 'good item'; //Item Number
			$products[0]['ItemQty']	= 1; // Item Quantity
			*/
			/*

			// set a second item

			$products[1]['ItemName'] = 'my item 2'; //Item Name
			$products[1]['ItemPrice'] = 10; //Item Price
			$products[1]['ItemNumber'] = 'xxx2'; //Item Number
			$products[1]['ItemDesc'] = 'good item 2'; //Item Number
			$products[1]['ItemQty']	= 3; // Item Quantity
			*/

			//-------------------- prepare charges -------------------------

			$charges = [];

			//Other important variables like tax, shipping cost
			$charges['TotalTaxAmount'] = 0;  //Sum of tax for all items in this order.
			$charges['HandalingCost'] = 0;  //Handling cost for this order.
			$charges['InsuranceCost'] = 0;  //shipping insurance cost for this order.
			$charges['ShippinDiscount'] = 0; //Shipping discount for this order. Specify this as negative number.
			$charges['ShippinCost'] = 0; //Although you may change the value later, try to pass in a shipping amount that is reasonably accurate.

			//------------------SetExpressCheckOut-------------------

			//We need to execute the "SetExpressCheckOut" method to obtain paypal token
            
			$paypal->SetExpressCheckOut($products, $charges);
		}else{
			$msgType = $_SESSION["msgType"] = disMessage(array('type'=>'err','var'=>TRANSACTION_CANCEL));
			redirectPage(SITE_URL."product-buy-now/".$PayPalData['product_slug']);
		}
	} elseif(_GET('token')!=''&&_GET('PayerID')!=''){

		//------------------DoExpressCheckoutPayment-------------------

		//Paypal redirects back to this page using ReturnURL, We should receive TOKEN and Payer ID
		//we will be using these two variables to execute the "DoExpressCheckoutPayment"
		//Note: we haven't received any payment yet.

		$paypal->DoExpressCheckoutPayment();
	}