<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/rbkmoney.php');

define('RBKMONEY_ALLOWED_IP', '89.111.188.128, 46.38.182.208, 46.38.182.209, 46.38.182.210, 89.111.188.129, 94.236.107.4, 94.236.107.1, 95.215.102.166');

// check valid IP
$allowed_ip = explode(',', RBKMONEY_ALLOWED_IP);
$valid_ip = in_array($_SERVER['REMOTE_ADDR'], $allowed_ip);
if (!$valid_ip) {
  Logger::AddLog('[RBK Money error] Post request from not allowed IP:' . $_SERVER['REMOTE_ADDR'], 3, NULL, NULL, NULL, TRUE);
  exit();
}
else {
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

  $currency = ($rbkmoney->getCurrency((int) $cart->id_currency)->iso_code == 'RUB') ? 'RUR' : $rbkmoney->getCurrency((int) $cart->id_currency)->iso_code;
  $total = number_format(Tools::convertPrice($cart->getOrderTotal(TRUE, 4), $rbkmoney->getCurrency()), 2, '.', '');

  $string = $eshopId . '::' . $response['orderId'] . '::' . $response['serviceName'] . '::' . $response['eshopAccount'] . '::' . $total . '::' . $currency . '::' . $response['paymentStatus'] . '::' . $response['userName'] . '::' . $response['userEmail'] . '::' . $response['paymentData'] . '::' . $secretKey;

  $control_hash = md5($string);

  if ($debug == '1') {
    $rbkm_string = $response['eshopId'] . '::' . $response['orderId'] . '::' . $response['serviceName'] . '::' . $response['eshopAccount'] . '::' . $response['recipientAmount'] . '::' . $response['recipientCurrency'] . '::' . $response['paymentStatus'] . '::' . $response['userName'] . '::' . $response['userEmail'] . '::' . $response['paymentData'] . '::' . $secretKey;

    Logger::AddLog('[RBK Money post] ' . $rbkm_string . ' hash:' . $response['hash'], 2, NULL, NULL, NULL, TRUE);
    Logger::AddLog('[RBK Money control] ' . $string . ' hash:' . $control_hash, 2, NULL, NULL, NULL, TRUE);
  }

  if (($response['hash'] != $control_hash)) {
    $err = "[RBK Money error] Hash mismatch. Control hash: {$control_hash} RBK Money hash:" . $response['hash'];
    Logger::AddLog($err, 3, NULL, NULL, NULL, TRUE);
    exit();
  }
  else {
//    $msg = "cart id " . $cart->id . "PS_OS_BANKWIRE " . Configuration::get('PS_OS_BANKWIRE') . "total " . $cart->getOrderTotal() . "curr " . $rbkmoney->getCurrency()->id . "secret " . $customer->secure_key;
//    Logger::AddLog('[tst] ' . $msg, 2, NULL, NULL, NULL, TRUE);

    if ($response['paymentStatus'] == '3') {
      $rbkmoney->validateOrder((int)$cart->id, Configuration::get('PS_OS_BANKWIRE'), $cart->getOrderTotal(), 'rbkmoney', NULL, NULL, (int) $rbkmoney->getCurrency()->id, FALSE, $customer->secure_key);
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