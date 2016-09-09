<?php
use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$gatewayUrl = 'https://merchant.paytos.com/CubePaymentGateway/gateway/action.NewSubmitAction.do';

$data['AcctNo'] = $processor_data['processor_params']['accno'];
$data['OrderID'] = get_paytos_order_id($order_id);
$data['CartId'] = get_paytos_order_id($order_id);
$data['CurrCode'] = $processor_data['processor_params']['paytos_currency'];;
$data['Amount'] = $order_info['total'] * 100;
$data['CardPAN'] = $payment_info["card_number"];
$data['ExpirationMonth'] = $payment_info["expiry_month"];
$data['ExpirationYear'] = $payment_info["expiry_year"];
$data['CVV2'] = $payment_info["cvv2"];
$data['CName'] = $payment_info['cardholder_name'];
$data['IPAddress'] = get_client_ip();
$data['BAddress'] = $order_info['b_address'];
$data['BCity'] = $order_info['b_city'];
$data['Bstate'] = $order_info["b_state_descr"];
$data['Bcountry'] = $order_info['b_country_descr'];
$data['BCountryCode'] = $order_info['b_country'];
$data['PostCode'] =  $order_info['b_zipcode'];
$data['Email'] =  $order_info['email'];
$data['Telephone'] = $order_info['phone'];
$data['PName'] = string_replace(get_product_names($order_info));
$data['IFrame'] = '1';
$data['URL'] = $_SERVER["HTTP_HOST"];
$data['OrderUrl'] = $_SERVER["HTTP_HOST"];
$data['callbackUrl'] = ''; // blank
$data['Framework'] = PRODUCT_NAME;
$data['IVersion'] = 'V8.0';
$data['Language'] = 'en';

/* HashValue input */
$paytos_key = $processor_data['processor_params']['md5key'];
$paytos_user = $processor_data['processor_params']['accno'];
$inputValue = $paytos_key . 
    $paytos_user . 
    $data['OrderID'] .
    $data['Amount'] . 
    $data['CurrCode'];
//fn_print_r($inputValue);
$data['HashValue'] = szComputeMD5Hash($inputValue);
//fn_print_r($data);

$result = curl_post($gatewayUrl, http_build_query($data, '', '&')); 		

$paytos_cfg = json_decode($result,true);
//fn_print_r($paytos_cfg);

$message = $paytos_cfg['msg'];
if($paytos_cfg["status"]=="0000"){
    $pp_response['order_status'] = 'P';
    $pp_response['reason_text'] = $message;
    $pp_response['transaction_id'] = $paytos_cfg['data']['par3'];

    fn_finish_payment($order_id, $pp_response);
    fn_order_placement_routines('route', $order_id, false);
       
}else{
    $pp_response['order_status'] = 'F';
    $pp_response['reason_text'] = 'Very Sorry. Your issuing bank or credit card company said \'' . $message . '\'. Please try to contact with your issuing bank or use a different card and try again.';

/*
    if($paytos_cfg["isPendingPayment"]==false){
        $message = $paytos_cfg['msg'];
        //fn_set_notification('E', $message);
        $pp_response['order_status'] = 'F';
        $pp_response['reason_text'] = 'Very Sorry. Your issuing bank or credit card company said \'' . $message . '\'. Please try to contact with your issuing bank or use a different card and try again.';
        $pp_response['transaction_id'] = $paytos_cfg['data']['orderNO'];

    } else {
        $message = $paytos_cfg['msg'];
        //fn_set_notification('E', $message);
        $pp_response['order_status'] = 'O';
        $pp_response['reason_text'] = 'Very Sorry. Your issuing bank or credit card company said \'' . $message . '\'. Please try to contact with your issuing bank or use a different card and try again.';
        $pp_response['transaction_id'] = $paytos_cfg['data']['orderNO'];
    }
*/

    // var_dump($payments_post_data);
    fn_finish_payment($order_id, $pp_response);
    fn_order_placement_routines('route', $order_id, false);
}

exit;

function curl_post($payUrl, $data) {
	$reffer_url = "http://".$_SERVER["HTTP_HOST"]."/checkout.html";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $payUrl);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($curl, CURLOPT_REFERER, $reffer_url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_TIMEOUT, 300);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
	 
    $tmpInfo = curl_exec($curl);

    if (curl_errno($curl)) {
        return false;
    }

    curl_close($curl);

    return $tmpInfo;
}

function szComputeMD5Hash($input){
    $md5hex=md5($input);
    $len=strlen($md5hex)/2;
    $md5raw="";
    for($i=0;$i<$len;$i++) { 
        $md5raw=$md5raw . chr(hexdec(substr($md5hex,$i*2,2)));
    }
    $keyMd5=base64_encode($md5raw);
    return $keyMd5;
}

function string_replace($string_before) {
    $string_after = str_replace("\n", " ", $string_before);
    $string_after = str_replace("\r", " ", $string_after);
    $string_after = str_replace("\r\n", " ", $string_after);
    $string_after = str_replace("'", "&#39 ", $string_after);
    $string_after = str_replace('"', "&#34 ", $string_after);
    $string_after = str_replace("(", "&#40 ", $string_after);
    $string_after = str_replace(")", "&#41 ", $string_after);
    return $string_after;
}

function get_product_names($order_info) {
    $products_info = "";
    if (!empty($order_info['products'])) {
        foreach ($order_info['products'] as $k => $v) {
            $v['product'] = htmlspecialchars(strip_tags($v['product']));
            if ($products_info == "") {
	            $products_info = $v['product']; 
            } else {
              	$products_info = $products_info.htmlspecialchars(' , ').$v['product'];
            }
        }
    }
    return $products_info;
}

function get_client_ip() {
    $ip = fn_get_ip(true);
    $client_ip = long2ip($ip['host']);
    return $client_ip;
}

function get_paytos_order_id($order_id) {
/*
    $pre_order = substr($_SERVER["HTTP_HOST"],0,2) . date('YmdHis');
    $paytos_orderid = $pre_order.$order_id;
    return $paytos_orderid;
*/
    return $order_id;
}
