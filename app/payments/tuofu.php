<?php
use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$gatewayUrl = 'https://merchant.paytos.com/CubePaymentGateway/gateway/action.NewSubmitAction.do';

$data['OrderID'] = get_paytos_order_id($order_id);
$data['CartId'] = get_paytos_order_id($order_id);
$data['CurrCode'] = $processor_data['processor_params']['paytos_currency'];;
$data['Amount'] = $order_info['total'] * 100;
$data['CardPAN'] = $payment_info["card_number"];
$data['ExpirationMonth'] = $payment_info["expiry_month"];
$data['ExpirationYear'] = $payment_info["expiry_year"];
$data['CVV2'] = $payment_info["cvv2"];
$data['IPAddress'] = get_client_ip();
$data['BCity'] = $order_info['b_city'];
$data['Bstate'] = $order_info["b_state_descr"];
$data['Bcountry'] = $order_info['b_country_descr'];
$data['BCountryCode'] = $order_info['b_country'];
$data['PostCode'] =  $order_info['b_zipcode'];
$data['Email'] =  $order_info['email'];
$data['Telephone'] = $order_info['phone'];
$data['Pname'] = string_replace(get_product_names($order_info));
$data['IFrame'] = '1';
$data['URL'] = $_SERVER["HTTP_HOST"];
$data['OrderUrl'] = $_SERVER["HTTP_HOST"];
$data['callbackUrl'] = ''; // blank
$data['Framework'] = PRODUCT_NAME;
$data['IVersion'] = 'V8.0';
$data['Language'] = 'en';

/* HashValue input */
$paytos_key = $processor_data['processor_params']['md5key'];
$paytos_account = $processor_data['processor_params']['accno'];
$inputValue = $paytos_key . 
    $paytos_account . 
    $data['OrderID'] .
    $data['Amount'] . 
    $data['CurrCode'];
$data['HashValue'] = szComputeMD5Hash($inputValue);
fn_print_r($data);

$result = curl_post($gatewayUrl, http_build_query($data, '', '&')); 		

$object_result = json_decode($result,true);
fn_print_r($object_result);
exit;

/*
if($object_result["status"]=="0000"){
    $message = $paytos_cfg['messages']['paySuccess'];
    $pp_response['order_status'] = 'P';
    $pp_response['reason_text'] ="$message" ;
    $pp_response['transaction_id'] = $paytos_web_orderid;

    fn_finish_payment($paytos_web_orderid, $pp_response);
    fn_order_placement_routines('route', $paytos_web_orderid, false);
       
}else{
    $pp_response['order_status'] = 'O';
    $pp_response['reason_text'] ="$message" ;
    $pp_response['transaction_id'] = $paytos_orderid;

    if($json_result["isPendingPayment"]==false){
        $message = $paytos_cfg['messages']['payFailure'];
        $message =  str_ireplace("@@@",$object_result["msg"],$message);
        //fn_set_notification('E', $message);
        $pp_response['order_status'] = 'F';
		$pp_response['reason_text'] ="$message" ;
		$pp_response['transaction_id'] = $paytos_orderid;

    } else {
        $message = $paytos_cfg['messages']['payPending'];
        $message =  str_ireplace("@@@",$object_result["msg"],$message);
        //fn_set_notification('E', $message);
        $pp_response['order_status'] = 'O';
	    $pp_response['reason_text'] ="$message" ;
	    $pp_response['transaction_id'] = $paytos_orderid;
    }

    // var_dump($payments_post_data);
    fn_finish_payment($paytos_web_orderid, $pp_response);
    fn_order_placement_routines('route', $paytos_web_orderid, false);
}
 */

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
    $pre_order = substr($_SERVER["HTTP_HOST"],0,2);
    $paytos_orderid = $pre_order.$order_id;
    return $paytos_orderid;
}

exit;
/*
// 订单信息
$BillNo = date('Ymd').$order_id;
$MerNo = $processor_data['processor_params']['accno'];
$MD5key = $processor_data['processor_params']['md5key'];
$Amount = $order_info['total'] * 100; // * 100
$CurrencyCode = getCurrency($order_info['secondary_currency']);

// 账单信息
$BFirstName = $order_info['b_firstname'];
$BLastName = $order_info['b_lastname'];
$Email = $order_info['email'];
$Phone = $order_info['phone'];
$BillZip = $order_info['b_zipcode'];
$BillAddress = $order_info['b_address'];
$BillCity = $order_info['b_city'];
$BillState = $order_info['b_state'];
$BillCountry = $order_info['b_country'];

// 发货信息
$SFirstName = $order_info['firstname'];
$SLastName = $order_info['lastname'];
$SEmail = $order_info['email'];
$SPhone = $order_info['phone'];
$ShipZip = $order_info['s_zipcode'];
$ShipAddress = $order_info['s_address'];
$ShipCity = $order_info['s_city'];
$ShipState = $order_info['s_state'];
$ShipCountry = $order_info['s_country'];

// 信用卡信息
$CardNo = $order_info['payment_info']['card_number'];
//fn_print_r($CardNo);
//fn_print_r($MD5key);
$SecurityCode = $order_info['payment_info']['cvv2'];
$CardType = fn_get_payment_card($CardNo, array(
    'visa' => 'VISA',
    'visa_debit' => 'DELTA',
    'mastercard' => 'MC',
    'mastercard_debit' => 'MCDEBIT',
    'amex' => 'AMEX',
    'jcb' => 'JCB',
    'maestro' => 'MAESTRO',
    'visa_electron' => 'UKE',
    'laser' => 'LASER',
    'diners_club_carte_blanche' => 'DINERS',
    'diners_club_international' => 'DINERS'
));
$CardExpireYear = $order_info['payment_info']['expiry_year'];
$CardExpireMonth = $order_info['payment_info']['expiry_month'];

// 数据的组合和加密校验
$md5src = $MD5key . $MerNo . $BillNo . $Amount . $CurrencyCode;
$MD5info = szComputeMD5Hash($md5src);

$cookies = '';
foreach ($_COOKIE as $key => $val) {
    $cookies = $cookies . $key . '=' . $val . ';';
}
$PName = '';
if (!empty($order_info['products'])) {
    foreach ($order_info['products'] as $k => $v) {
        $v['product'] = htmlspecialchars(strip_tags($v['product']));
        $PName = $v['product'];
    }
}
$post_data = array(
    'AcctNo' => $MerNo,
    'OrderID' =>$BillNo,
    'CartID' => $order_info['order_id'],
    'CurrCode' =>getCurrency($order_info['secondary_currency']),
    'Amount' => $order_info['total']*100,
    'IPAddress'=>$_SERVER["SERVER_ADDR"],
    'Telephone' => $order_info['phone'],
    'CardPAN' => $CardNo,
    'CardType'=> $CardType,
    'CName' => $order_info['b_firstname']." ".$order_info['b_lastname'],
    'ExpDate' =>$CardExpireYear.$CardExpireMonth,
    'ExpirationMonth' => $CardExpireMonth,
    'ExpirationYear' => $CardExpireYear,
    'CVV2' => $SecurityCode,
    'BAddress' => $order_info['b_address'],
    'BCity' => $order_info['b_city'],
    'PostCode' => $order_info['b_zipcode'],
    'Email' => $order_info['email'],
    'Bstate' => $order_info['b_state'],
    'Bcountry' => $order_info['b_country'],
    'IFrame'=>"1",
    'URL'=>$_SERVER["HTTP_HOST"],
    'OrderUrl' => $_SERVER["HTTP_HOST"],
    'PName'=>$PName,
    'HashValue'=>$MD5info,
    'TxnType' =>"01",
    'Framework'=> PRODUCT_NAME,
    'CMSVersion'=> PRODUCT_VERSION,
    'PHPVersion'=>phpversion(),
    'cookies'=>$cookies,
    'IVersion'=>"V7.0-A-001",
    'Language'=>substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2),
);
$post_data = http_build_query($post_data, '', '&');

//fn_print_r($post_data);
$response = vpost($url, $post_data);
//fn_print_r($response);

$pp_response['reason_text'] = 'Payment status: '. $response['msg'];

if ($response['status'] === '0000') {
    // payment successful
    $pp_response['order_status'] = 'P';
} else {
    // payment failed
    $pp_response['order_status'] = 'F';
}

//fn_print_r($pp_response);
//fn_finish_payment($order_id, $pp_response, false);

function szComputeMD5Hash($input) {
    $md5hex = md5($input);
    $len = strlen($md5hex) / 2;
    $md5raw = "";
    for ($i = 0; $i < $len; $i++) {
        $md5raw = $md5raw . chr(hexdec(substr($md5hex, $i * 2, 2)));
    }
    $keyMd5 = base64_encode($md5raw);
    return $keyMd5;
}


function vpost($url, $data) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    $response = curl_exec($curl);
    $result = json_decode($response, TRUE);
    curl_close($curl);
    return $result;
}

function getCurrency($Currency){
    $_95currency = '';
    if ($Currency == 'ADP') {
        $_95currency = '020';
    } else if ($Currency == 'AED') {
        $_95currency = '784';
    } else if ($Currency == 'AFA') {
        $_95currency = '004';
    } else if ($Currency == 'ALL') {
        $_95currency = '008';
    } else if ($Currency == 'AMD') {
        $_95currency = '051';
    } else if ($Currency == 'ANG') {
        $_95currency = '532';
    } else if ($Currency == 'AOA') {
        $_95currency = '973';
    } else if ($Currency == 'AON') {
        $_95currency = '024';
    } else if ($Currency == 'ARS') {
        $_95currency = '032';
    } else if ($Currency == 'ASF') {
        $_95currency = '999';
    } else if ($Currency == 'ATS') {
        $_95currency = '040';
    } else if ($Currency == 'AUD') {
        $_95currency = '036';
    } else if ($Currency == 'AWG') {
        $_95currency = '533';
    } else if ($Currency == 'AZM') {
        $_95currency = '031';
    } else if ($Currency == 'BAM') {
        $_95currency = '977';
    } else if ($Currency == 'BBD') {
        $_95currency = '052';
    } else if ($Currency == 'BDT') {
        $_95currency = '050';
    } else if ($Currency == 'BEF') {
        $_95currency = '056';
    } else if ($Currency == 'BGL') {
        $_95currency = '100';
    } else if ($Currency == 'BGN') {
        $_95currency = '975';
    } else if ($Currency == 'BHD') {
        $_95currency = '048';
    } else if ($Currency == 'BIF') {
        $_95currency = '108';
    } else if ($Currency == 'BMD') {
        $_95currency = '060';
    } else if ($Currency == 'BND') {
        $_95currency = '096';
    } else if ($Currency == 'BOB') {
        $_95currency = '068';
    } else if ($Currency == 'BOV') {
        $_95currency = '984';
    } else if ($Currency == 'BRL') {
        $_95currency = '986';
    } else if ($Currency == 'BSD') {
        $_95currency = '044';
    } else if ($Currency == 'BTN') {
        $_95currency = '064';
    } else if ($Currency == 'BWP') {
        $_95currency = '072';
    } else if ($Currency == 'BYB') {
        $_95currency = '112';
    } else if ($Currency == 'BYR') {
        $_95currency = '974';
    } else if ($Currency == 'BZD') {
        $_95currency = '084';
    } else if ($Currency == 'CAD') {
        $_95currency = '124';
    } else if ($Currency == 'CDF') {
        $_95currency = '976';
    } else if ($Currency == 'CHF') {
        $_95currency = '756';
    } else if ($Currency == 'CLF') {
        $_95currency = '990';
    } else if ($Currency == 'CLP') {
        $_95currency = '152';
    } else if ($Currency == 'CNY') {
        $_95currency = '156';
    } else if ($Currency == 'COP') {
        $_95currency = '170';
    } else if ($Currency == 'CRC') {
        $_95currency = '188';
    } else if ($Currency == 'CUP') {
        $_95currency = '192';
    } else if ($Currency == 'CVE') {
        $_95currency = '132';
    } else if ($Currency == 'CYP') {
        $_95currency = '196';
    } else if ($Currency == 'CZK') {
        $_95currency = '203';
    } else if ($Currency == 'DEM') {
        $_95currency = '280';
    } else if ($Currency == 'DJF') {
        $_95currency = '262';
    } else if ($Currency == 'DKK') {
        $_95currency = '208';
    } else if ($Currency == 'DOP') {
        $_95currency = '214';
    } else if ($Currency == 'DZD') {
        $_95currency = '012';
    } else if ($Currency == 'ECS') {
        $_95currency = '218';
    } else if ($Currency == 'ECV') {
        $_95currency = '983';
    } else if ($Currency == 'EEK') {
        $_95currency = '233';
    } else if ($Currency == 'EGP') {
        $_95currency = '818';
    } else if ($Currency == 'ERN') {
        $_95currency = '232';
    } else if ($Currency == 'ESP') {
        $_95currency = '724';
    } else if ($Currency == 'ETB') {
        $_95currency = '230';
    } else if ($Currency == 'EUR') {
        $_95currency = '978';
    } else if ($Currency == 'FIM') {
        $_95currency = '246';
    } else if ($Currency == 'FJD') {
        $_95currency = '242';
    } else if ($Currency == 'FKP') {
        $_95currency = '238';
    } else if ($Currency == 'FRF') {
        $_95currency = '250';
    } else if ($Currency == 'GBP') {
        $_95currency = '826';
    } else if ($Currency == 'GEL') {
        $_95currency = '981';
    } else if ($Currency == 'GHC') {
        $_95currency = '288';
    } else if ($Currency == 'GIP') {
        $_95currency = '292';
    } else if ($Currency == 'GMD') {
        $_95currency = '270';
    } else if ($Currency == 'GNF') {
        $_95currency = '324';
    } else if ($Currency == 'GRD') {
        $_95currency = '300';
    } else if ($Currency == 'GTQ') {
        $_95currency = '320';
    } else if ($Currency == 'GWP') {
        $_95currency = '624';
    } else if ($Currency == 'GYD') {
        $_95currency = '328';
    } else if ($Currency == 'HKD') {
        $_95currency = '344';
    } else if ($Currency == 'HNL') {
        $_95currency = '340';
    } else if ($Currency == 'HRK') {
        $_95currency = '191';
    } else if ($Currency == 'HTG') {
        $_95currency = '332';
    } else if ($Currency == 'HUF') {
        $_95currency = '348';
    } else if ($Currency == 'IDR') {
        $_95currency = '360';
    } else if ($Currency == 'IEP') {
        $_95currency = '372';
    } else if ($Currency == 'ILS') {
        $_95currency = '376';
    } else if ($Currency == 'INR') {
        $_95currency = '368';
    } else if ($Currency == 'IRR') {
        $_95currency = '364';
    } else if ($Currency == 'ISK') {
        $_95currency = '352';
    } else if ($Currency == 'ITL') {
        $_95currency = '380';
    } else if ($Currency == 'JMD') {
        $_95currency = '388';
    } else if ($Currency == 'JOD') {
        $_95currency = '400';
    } else if ($Currency == 'JPY') {
        $_95currency = '392';
    } else if ($Currency == 'KES') {
        $_95currency = '404';
    } else if ($Currency == 'KGS') {
        $_95currency = '417';
    } else if ($Currency == 'KHR') {
        $_95currency = '116';
    } else if ($Currency == 'KMF') {
        $_95currency = '174';
    } else if ($Currency == 'KPW') {
        $_95currency = '408';
    } else if ($Currency == 'KRW') {
        $_95currency = '410';
    } else if ($Currency == 'KWD') {
        $_95currency = '414';
    } else if ($Currency == 'KYD') {
        $_95currency = '136';
    } else if ($Currency == 'KZT') {
        $_95currency = '398';
    } else if ($Currency == 'LAK') {
        $_95currency = '418';
    } else if ($Currency == 'LBP') {
        $_95currency = '422';
    } else if ($Currency == 'LKR') {
        $_95currency = '144';
    } else if ($Currency == 'LRD') {
        $_95currency = '430';
    } else if ($Currency == 'LSL') {
        $_95currency = '426';
    } else if ($Currency == 'LTL') {
        $_95currency = '440';
    } else if ($Currency == 'LUF') {
        $_95currency = '442';
    } else if ($Currency == 'LVL') {
        $_95currency = '428';
    } else if ($Currency == 'LYD') {
        $_95currency = '434';
    } else if ($Currency == 'MAD') {
        $_95currency = '504';
    } else if ($Currency == 'MDL') {
        $_95currency = '498';
    } else if ($Currency == 'MGF') {
        $_95currency = '450';
    } else if ($Currency == 'MKD') {
        $_95currency = '807';
    } else if ($Currency == 'MMK') {
        $_95currency = '104';
    } else if ($Currency == 'MNT') {
        $_95currency = '496';
    } else if ($Currency == 'MOP') {
        $_95currency = '446';
    } else if ($Currency == 'MRO') {
        $_95currency = '478';
    } else if ($Currency == 'MTL') {
        $_95currency = '470';
    } else if ($Currency == 'MUR') {
        $_95currency = '480';
    } else if ($Currency == 'MVR') {
        $_95currency = '462';
    } else if ($Currency == 'MWK') {
        $_95currency = '454';
    } else if ($Currency == 'MXN') {
        $_95currency = '484';
    } else if ($Currency == 'MXV') {
        $_95currency = '979';
    } else if ($Currency == 'MYR') {
        $_95currency = '458';
    } else if ($Currency == 'MZM') {
        $_95currency = '508';
    } else if ($Currency == 'NAD') {
        $_95currency = '516';
    } else if ($Currency == 'NGN') {
        $_95currency = '566';
    } else if ($Currency == 'NIO') {
        $_95currency = '558';
    } else if ($Currency == 'NLG') {
        $_95currency = '528';
    } else if ($Currency == 'NOK') {
        $_95currency = '578';
    } else if ($Currency == 'NPR') {
        $_95currency = '524';
    } else if ($Currency == 'NZD') {
        $_95currency = '554';
    } else if ($Currency == 'OMR') {
        $_95currency = '512';
    } else if ($Currency == 'PAB') {
        $_95currency = '590';
    } else if ($Currency == 'PEN') {
        $_95currency = '604';
    } else if ($Currency == 'PGK') {
        $_95currency = '598';
    } else if ($Currency == 'PHP') {
        $_95currency = '608';
    } else if ($Currency == 'PKR') {
        $_95currency = '586';
    } else if ($Currency == 'PLN') {
        $_95currency = '985';
    } else if ($Currency == 'PLZ') {
        $_95currency = '616';
    } else if ($Currency == 'PTE') {
        $_95currency = '620';
    } else if ($Currency == 'PYG') {
        $_95currency = '600';
    } else if ($Currency == 'QAR') {
        $_95currency = '634';
    } else if ($Currency == 'ROL') {
        $_95currency = '642';
    } else if ($Currency == 'RSD') {
        $_95currency = '941';
    } else if ($Currency == 'RUB') {
        $_95currency = '810';
    } else if ($Currency == 'RWF') {
        $_95currency = '646';
    } else if ($Currency == 'SAR') {
        $_95currency = '682';
    } else if ($Currency == 'SBD') {
        $_95currency = '090';
    } else if ($Currency == 'SCR') {
        $_95currency = '690';
    } else if ($Currency == 'SDD') {
        $_95currency = '736';
    } else if ($Currency == 'SDP') {
        $_95currency = '736';
    } else if ($Currency == 'SDR') {
        $_95currency = '000';
    } else if ($Currency == 'SEK') {
        $_95currency = '752';
    } else if ($Currency == 'SGD') {
        $_95currency = '702';
    } else if ($Currency == 'SHP') {
        $_95currency = '654';
    } else if ($Currency == 'SIT') {
        $_95currency = '705';
    } else if ($Currency == 'SKK') {
        $_95currency = '703';
    } else if ($Currency == 'SLL') {
        $_95currency = '694';
    } else if ($Currency == 'SOS') {
        $_95currency = '706';
    } else if ($Currency == 'SRG') {
        $_95currency = '740';
    } else if ($Currency == 'STD') {
        $_95currency = '678';
    } else if ($Currency == 'SVC') {
        $_95currency = '222';
    } else if ($Currency == 'SYP') {
        $_95currency = '760';
    } else if ($Currency == 'SZL') {
        $_95currency = '748';
    } else if ($Currency == 'THB') {
        $_95currency = '764';
    } else if ($Currency == 'TJR') {
        $_95currency = '762';
    } else if ($Currency == 'TJS') {
        $_95currency = '972';
    } else if ($Currency == 'TMM') {
        $_95currency = '795';
    } else if ($Currency == 'TND') {
        $_95currency = '788';
    } else if ($Currency == 'TOP') {
        $_95currency = '776';
    } else if ($Currency == 'TRL') {
        $_95currency = '792';
    } else if ($Currency == 'TTD') {
        $_95currency = '780';
    } else if ($Currency == 'TWD') {
        $_95currency = '901';
    } else if ($Currency == 'TZS') {
        $_95currency = '834';
    } else if ($Currency == 'UAH') {
        $_95currency = '980';
    } else if ($Currency == 'UAK') {
        $_95currency = '804';
    } else if ($Currency == 'UGX') {
        $_95currency = '800';
    } else if ($Currency == 'USD') {
        $_95currency = '840';
    } else if ($Currency == 'USN') {
        $_95currency = '997';
    } else if ($Currency == 'USS') {
        $_95currency = '998';
    } else if ($Currency == 'UYU') {
        $_95currency = '858';
    } else if ($Currency == 'UZS') {
        $_95currency = '860';
    } else if ($Currency == 'VEB') {
        $_95currency = '862';
    } else if ($Currency == 'VND') {
        $_95currency = '704';
    } else if ($Currency == 'VUV') {
        $_95currency = '548';
    } else if ($Currency == 'WST') {
        $_95currency = '882';
    } else if ($Currency == 'XAF') {
        $_95currency = '950';
    } else if ($Currency == 'XAG') {
        $_95currency = '961';
    } else if ($Currency == 'XAU') {
        $_95currency = '959';
    } else if ($Currency == 'XBA') {
        $_95currency = '955';
    } else if ($Currency == 'XBB') {
        $_95currency = '956';
    } else if ($Currency == 'XBC') {
        $_95currency = '957';
    } else if ($Currency == 'XBD') {
        $_95currency = '958';
    } else if ($Currency == 'XCD') {
        $_95currency = '951';
    } else if ($Currency == 'XDR') {
        $_95currency = '960';
    } else if ($Currency == 'XEU') {
        $_95currency = '954';
    } else if ($Currency == 'XOF') {
        $_95currency = '952';
    } else if ($Currency == 'XPD') {
        $_95currency = '964';
    } else if ($Currency == 'XPF') {
        $_95currency = '953';
    } else if ($Currency == 'XPT') {
        $_95currency = '962';
    } else if ($Currency == 'XTS') {
        $_95currency = '963';
    } else if ($Currency == 'XXX') {
        $_95currency = '999';
    } else if ($Currency == 'YER') {
        $_95currency = '886';
    } else if ($Currency == 'YUM') {
        $_95currency = '891';
    } else if ($Currency == 'YUN') {
        $_95currency = '890';
    } else if ($Currency == 'ZAL') {
        $_95currency = '991';
    } else if ($Currency == 'ZAR') {
        $_95currency = '710';
    } else if ($Currency == 'ZMK') {
        $_95currency = '894';
    } else if ($Currency == 'ZRN') {
        $_95currency = '180';
    } else if ($Currency == 'ZWD') {
        $_95currency = '716';
    }
    return $_95currency;

}
 */
