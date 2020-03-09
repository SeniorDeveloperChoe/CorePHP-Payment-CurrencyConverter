<?php

class PayStack {

  function genReference($qtd){
    //Under the string $Caracteres you write all the characters you want to be used to randomly generate the code.
    $Caracteres = 'ABCDEFGHIJKLMOPQRSTUVXWYZ0123456789';
    $QuantidadeCaracteres = strlen($Caracteres);
    $QuantidadeCaracteres--;

    $Hash=NULL;

    for($i=1; $i<=$qtd; $i++){
        $Posicao = rand(0,$QuantidadeCaracteres);
        $Hash .= substr($Caracteres,$Posicao,1);
    }
    return $Hash;
  }

    // Paystack Secret Key
    const SECRET = 'sk_test_8ba58280eeba04afae96c483e99ca2ef9e3475b1';

    function ProcessPayment($amount, $email) {
      $result = array();

      $reference = $this->genReference(10);

      $url = "https://api.paystack.co/transaction/initialize";
      $callback_url = 'http://wimart247.com/wallet';
      
      $curl = curl_init();
      
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          'amount'=>$amount*100,
          'email'=>$email
        ]),
        CURLOPT_HTTPHEADER => [
          "authorization: Bearer ".self::SECRET,
          "content-type: application/json",
          "cache-control: no-cache"
        ],
      ));
      
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close ($curl);

      if($err){
        die('Curl returned error: ' . $err);
      }

      $result = json_decode($response, true);

      $transaction_referance = $result['data']['reference'];

      $verification = $this->VerifyPayment($transaction_referance);
      // if($verification['status'])
      return $result['data']['authorization_url']; 
    }

    function VerifyPayment($transaction_referance) {
        $result = array();
        $url = 'https://api.paystack.co/transaction/verify/'.rawurlencode($transaction_referance);
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HTTPHEADER => [
            "authorization: Bearer ".self::SECRET,
            "cache-control: no-cache"
          ],
        ));

        $request = curl_exec($ch);
        if(curl_error($ch)){
         echo 'error:' . curl_error($ch);
         }
        curl_close($ch);
        
        if ($request) {
          $result = json_decode($request, true);
        }

        if (array_key_exists('data', $result) && array_key_exists('status', $result['data']) && ($result['data']['status'] === 'success')) {
            return true;
        }else{
            return false;
        }   
    }

    //-----------------------  start_refund_for_redeem_requirements  -----------------------------//

    // resolve account number
    function resolveAccountNumber($account_number, $back_code) {
      $result = array();

      $account_number = '01060794207';
      $bank_code = "058";

      $url = "https://api.paystack.co/bank/resolve?account_number=".$account_number."&bank_code=".$bank_code;
      
      $curl = curl_init();
      
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
          "authorization: Bearer ".self::SECRET,
          "Cache-Control: no-cache",
        ],
      ));
      
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close ($curl);

      $result = json_decode($response, true);

      return $result;
    }

    function createTransferRecipient() {
      $result = array();

      $account_number = '01060794207';
      $bank_code = "044";

      $url = "https://api.paystack.co/transferrecipient";
      
      $curl = curl_init();
      
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          "type" => "nuban",
          "name" => "Account 1029",
          "description" => "Customer1029 bank account",
          "account_number" => $account_number,
          "bank_code" => $bank_code,
          "currency" => "NGN",
        ]),
        CURLOPT_HTTPHEADER => [
          "authorization: Bearer ".self::SECRET,
          "Cache-Control: no-cache",
        ],
      ));
      
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close ($curl);

      $result = json_decode($response, true);

      return $result['data']['recipient_code'];
    }

    function initiateTransfer($amount) {
      $result = array();

      $url = "https://api.paystack.co/transfer";
      
      $curl = curl_init();
      
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          "source" => "balance", 
          "reason" => "Calm down", 
          "amount"=> $amount, 
          "recipient" => createTransferRecipient()
        ]),
        CURLOPT_HTTPHEADER => [
          "authorization: Bearer ".self::SECRET,
          "Cache-Control: no-cache",
        ],
      ));
      
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close ($curl);

      $result = json_decode($response, true);

      return $result['data']['transfer_code'];
    }

    function fetchTransfer($amount) {
      $result = array();

      $transfer_code = initiateTransfer($amount);

      $url = "https://api.paystack.co/transfer/".$transfer_code;
      
      $curl = curl_init();
      
      curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
          "authorization: Bearer ".self::SECRET,
          "Cache-Control: no-cache",
        ],
      ));
      
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close ($curl);

      $result = json_decode($response, true);

      return $result['data']['status'];
    }
}

?>