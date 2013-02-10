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

class Rbkmoney extends PaymentModule {
  function __construct() {
    $this->name = 'rbkmoney';
    $this->tab = 'payments_gateways';
    $this->version = '0.1';
    $this->currencies = TRUE;
    $this->currencies_mode = 'radio';

    parent::__construct(); /* The parent construct is required for translations */

    $this->page = basename(__FILE__, '.php');
    $this->displayName = $this->l('RBK Money payment system');
    $this->description = $this->l('Accept payments by RBK Money payment system');
  }

  function install() {
    parent::install();
    Configuration::updateValue('RBKM_ESHOPID', '');
    Configuration::updateValue('RBKM_PASS', '');
    Configuration::updateValue('RBKM_DEBUG', '0');
    $this->registerHook('payment');
    $this->registerHook('paymentReturn');
    return TRUE;
  }

  function uninstall() {
    if (!Configuration::deleteByName('RBKM_ESHOPID')
      OR !Configuration::deleteByName('RBKM_PASS')
      OR !Configuration::deleteByName('RBKM_DEBUG')
      OR !parent::uninstall()
    ) {
      return FALSE;
    }
    return TRUE;
  }

  public function getContent() {
    $this->output = "<h2>RBK Money</h2>";

    if ($_POST['submit']) {
      if (!empty($_POST['rbkm_eshopid'])) {
        Configuration::updateValue('RBKM_ESHOPID', $_POST['rbkm_eshopid']);
      }
      if (!empty($_POST['rbkm_pass'])) {
        Configuration::updateValue('RBKM_PASS', $_POST['rbkm_pass']);
      }
      if (!empty($_POST['rbkm_debug'])) {
        Configuration::updateValue('RBKM_DEBUG', $_POST['rbkm_debug']);
      }
      $this->output .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="' . $this->l('Confirmation') . '" />' . $this->l('Settings updated') . '</div>';
    }

    $this->displayFormSettings();

    return $this->output;
  }

  public function displayFormSettings() {
    $rbkm_eshopid = htmlentities(Configuration::get('RBKM_ESHOPID'), ENT_COMPAT, 'UTF-8');
    $rbkm_pass = htmlentities(Configuration::get('RBKM_PASS'), ENT_COMPAT, 'UTF-8');
    $rbkm_debug = htmlentities(Configuration::get('RBKM_DEBUG'), ENT_COMPAT, 'UTF-8') == '0' ? '' : 'checked="checked"';

    $this->output .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />' . $this->l('Settings') . '</legend>
			<label for="rbkm_eshopid">' . $this->l('RBK Money eshop ID') . '</label>
			<div class="margin-form"><input type="text" name="rbkm_eshopid" id="rbkm_eshopid" value="' . $rbkm_eshopid . '" /></div>
			<label for="rbkm_pass">' . $this->l('RBK Money keyword') . '</label>
			<div class="margin-form"><input type="text" name="rbkm_pass" id="rbkm_pass" value="' . $rbkm_pass . '" /></div>
			<label>' . $this->l('Log notifications from RBK Money (Advanced Parameters > Logs)') . '</label>
			<div class="margin-form">
			  <input type="radio" name="rbkm_debug" value="0"' . $rbkm_debug . '/>Off
			  <input type="radio" name="rbkm_debug" value="1"' . $rbkm_debug . '/>On
			</div>

    <div class="margin-form"><input type="submit" name="submit" value="' . $this->l('Update settings') . '" class="button" /></div>
		</fieldset>
		</form>';
  }


  function hookPayment($params) {
    global $smarty;

    $address = new Address(intval($params['cart']->id_address_invoice));
    $customer = new Customer(intval($params['cart']->id_customer));
    $rbkm_eshopid = Configuration::get('RBKM_ESHOPID');
    $currency = $this->getCurrency();
//    $today = date("Y-m-d H:i:s");

    if (!Validate::isLoadedObject($address) OR !Validate::isLoadedObject($customer) OR !Validate::isLoadedObject($currency)) {
      return $this->l('Error: (invalid address or customer)');
    }

    $this::validateOrder($params['cart']->id, Configuration::get('PS_OS_BANKWIRE'), $params['cart']->getOrderTotal(), 'rbkmoney', NULL, NULL, (int) $currency->id, FALSE, $customer->secure_key);

//    $order = new Order($this->currentOrder);


    $products = $params['cart']->getProducts();
    $serviceName = '';

    foreach ($products as $key => $product) {
      $products[$key]['name'] = str_replace('"', '\'', $product['name']);
      if (isset($product['attributes'])) {
        $products[$key]['attributes'] = str_replace('"', '\'', $product['attributes']);
      }
      $products[$key]['name'] = htmlentities(utf8_decode($product['name']));
      $serviceName .= $products[$key]['name'] . ' ';
    }
    if ($currency->iso_code == 'RUB') {
      $currency->iso_code = 'RUR';
    }
    $smarty->assign(array(
      'serviceName' => $serviceName,
      'customer' => $customer,
      'eshopId' => $rbkm_eshopid,
      'currency' => $currency,
      'recipientAmount' => number_format(Tools::convertPrice($params['cart']->getOrderTotal(TRUE, 3), $currency), 2, '.', ''),
      'id_cart' => intval($params['cart']->id),
      'successUrl' => 'http://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'order-confirmation.php?key=' . $customer->secure_key . '&id_cart=' . intval($params['cart']->id) . '&id_module=' . intval($this->id),
      'failUrl' => 'http://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php',
    ));

    return $this->display(__FILE__, 'rbkmoney.tpl');
  }

  function hookPaymentReturn($params) {
    return $this->display(__FILE__, 'confirmation.tpl');
  }

}