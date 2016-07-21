<?php

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/rbkmoney.php');

define('RBKMONEY_ALLOWED_IP', '89.111.188.128, 89.111.188.129, 94.236.107.4, 195.13.163.231, 46.38.182.209, 95.215.102.166, 46.38.182.208, 46.38.182.211');


// check valid IP
$allowed_ip = array_map('trim', explode(',', RBKMONEY_ALLOWED_IP));
$valid_ip = in_array($_SERVER['REMOTE_ADDR'], $allowed_ip);
if (!$valid_ip && isset($_POST['eshopId'])) {
    Logger::AddLog('[RBK Money] Post request from not allowed IP:' . $_SERVER['REMOTE_ADDR'] . '. Status of order ID ' . stripcslashes($_POST['eshopId']) . ' not changed',
      3, null, null, null, true);
    exit();
} else {
    header("HTTP/1.0 200 OK");
}

if ($_POST) {
    foreach ($_POST as $k => $v) {
        $response[$k] = stripslashes($v);
    }

    $rbkmoney = new Rbkmoney();
    $cart = new Cart(intval($response['orderId']));
    $customer = new Customer(intval($cart->id_customer));

    $eshopId = Configuration::get('RBKM_ESHOPID');
    $secretKey = Configuration::get('RBKM_PASS');
    $debug = Configuration::get('RBKM_DEBUG');

    //    $currency = ($rbkmoney->getCurrency((int) $cart->id_currency)->iso_code == 'RUB') ? 'RUR' : $rbkmoney->getCurrency((int) $cart->id_currency)->iso_code;
    $total = number_format(Tools::convertPrice($cart->getOrderTotal(true, 4),
        $rbkmoney->getCurrency()), 2, '.', '');

    $string = @implode('::', array(
      $eshopId,
      $response['orderId'],
      $response['serviceName'],
      $response['eshopAccount'],
      $total,
      $response['recipientCurrency'],
      $response['paymentStatus'],
      $response['userName'],
      $response['userEmail'],
      $response['paymentData'],
      $secretKey
    ));

    $control_hash = md5($string);

    if ($debug !== 'false') { // log response
        foreach ($response as $k => $v) {
            $query .= $k . "=" . $v . "&";
        }
        Logger::AddLog('[RBK Money] Received POST request: ' . $query, 2, null,
          null, null, true);
    }

    if (($response['hash'] != $control_hash)) {
        $msg = "[RBK Money] Hash mismatch error. Control hash: {$control_hash}; RBK Money hash:" . $response['hash'];
        Logger::AddLog($msg, 3, null, null, null, true);
        exit();
    } else {
        if ($response['paymentStatus'] == '3') {
            $rbkmoney->validateOrder((int) $cart->id,
              Configuration::get('PS_OS_BANKWIRE'), $cart->getOrderTotal(),
              'rbkmoney', null, null, (int) $rbkmoney->getCurrency()->id, false,
              $customer->secure_key);
            $id_order = Order::getOrderByCartId(intval($response['orderId']));
            $order = new Order($id_order);
            $order->setCurrentState(Configuration::get('PS_OS_WS_PAYMENT'));
        }
        if ($response['paymentStatus'] == '5') {
            $id_order = Order::getOrderByCartId(intval($response['orderId']));
            $order = new Order($id_order);
            $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
        }
    }
}

?>
