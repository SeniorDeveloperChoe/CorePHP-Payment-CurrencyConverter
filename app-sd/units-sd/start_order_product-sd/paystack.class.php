<?php

class PayStack {
    // Paystack Secret Key
    const SECRET = 'sk_test_8ba58280eeba04afae96c483e99ca2ef9e3475b1';
    function ProcessPayment($amount, $email, $order_id) {
        
        $curl = curl_init();
        $callback_url = 'http://wimart247.com/product-buy-now/my-new-products?order_id='.$order_id.'&';  
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode([
            'amount'=>$amount*100,
            'email'=>$email,
            'order_id'=>$order_id,
            'callback_url'=>$callback_url
          ]),
          CURLOPT_HTTPHEADER => [
            "authorization: Bearer ".self::SECRET,
            "content-type: application/json",
            "cache-control: no-cache"
          ],
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        if($err){
          die('Curl returned error: ' . $err);
        }

        $tranx = json_decode($response, true);
        return $tranx['data']['authorization_url'];
    }
    
    function VerifyPayment($reference) {
        $curl = curl_init();        
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "authorization: Bearer ".self::SECRET,
            "cache-control: no-cache"
          ],
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        if($err){
          die('Curl returned error: ' . $err);
        }
        $tranx = json_decode($response, true);
        return $tranx;
    }
}

?>