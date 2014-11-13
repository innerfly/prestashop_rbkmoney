<?php

class Rbkmoney extends PaymentModule
{
    function __construct()
    {
        $this->name = 'rbkmoney';
        $this->tab = 'payments_gateways';
        $this->version = '0.2';
        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct(); /* The parent construct is required for translations */

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('RBK Money payment system');
        $this->description = $this->l('Accept payments by RBK Money payment system');
    }

    function install()
    {
        parent::install();
        Configuration::updateValue('RBKM_ESHOPID', '');
        Configuration::updateValue('RBKM_PASS', '');
        Configuration::updateValue('RBKM_DEBUG', 'false');
        $this->registerHook('payment');
        $this->registerHook('paymentReturn');
        return true;
    }

    function uninstall()
    {
        if (!Configuration::deleteByName('RBKM_ESHOPID') OR !Configuration::deleteByName('RBKM_PASS') OR !Configuration::deleteByName('RBKM_DEBUG') OR !parent::uninstall()
        ) {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        $this->output = "<h2>RBK Money</h2>";

        if ($_POST['submit']) {
            if (!empty($_POST['rbkm_eshopid'])) {
                Configuration::updateValue('RBKM_ESHOPID',  $_POST['rbkm_eshopid']);
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

    public function displayFormSettings()
    {
        $rbkm_eshopid = htmlentities(Configuration::get('RBKM_ESHOPID'),
          ENT_COMPAT, 'UTF-8');
        $rbkm_pass = htmlentities(Configuration::get('RBKM_PASS'), ENT_COMPAT,
          'UTF-8');

        if (htmlentities(Configuration::get('RBKM_DEBUG'), ENT_COMPAT,
            'UTF-8') == 'true'
        ) {
            $on = 'checked="checked"';
            $off = '';
        } else {
            $on = '';
            $off = 'checked="checked"';
        }

        $this->output .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />' . $this->l('Settings') . '</legend>
			<label for="rbkm_eshopid">' . $this->l('RBK Money eshop ID') . '</label>
			<div class="margin-form"><input type="text" name="rbkm_eshopid" id="rbkm_eshopid" value="' . $rbkm_eshopid . '" /></div>
			<label for="rbkm_pass">' . $this->l('RBK Money keyword') . '</label>
			<div class="margin-form"><input type="text" name="rbkm_pass" id="rbkm_pass" value="' . $rbkm_pass . '" /></div>
			<label>' . $this->l('Log notifications from RBK Money (Advanced Parameters > Logs)') . '</label>
			<div class="margin-form">
			  <input type="radio" name="rbkm_debug" value="false" ' . $off . '/>Off
			  <input type="radio" name="rbkm_debug" value="true" ' . $on . '/>On
			</div>

    <div class="margin-form"><input type="submit" name="submit" value="' . $this->l('Update settings') . '" class="button" /></div>
		</fieldset>
		</form>';
    }


    function hookPayment($params)
    {
        $address = new Address(intval($params['cart']->id_address_invoice));
        $customer = new Customer(intval($params['cart']->id_customer));
        $eshopid = Configuration::get('RBKM_ESHOPID');
        $secret_key = Configuration::get('RBKM_PASS');
        $currency = $this->getCurrency();
        $recipientAmount = number_format(Tools::convertPrice($params['cart']->getOrderTotal(true,
              3), $currency), 2, '.', '');

        if (!Validate::isLoadedObject($address) OR !Validate::isLoadedObject($customer) OR !Validate::isLoadedObject($currency)) {
            return $this->l('Error: (invalid address or customer)');
        }

        $products = $params['cart']->getProducts();
        $serviceName = '';
        foreach ($products as $key => $product) {
            $products[$key]['name'] = str_replace('"', '\'', $product['name']);
            if (isset($product['attributes'])) {
                $products[$key]['attributes'] = str_replace('"', '\'',
                  $product['attributes']);
            }
            $products[$key]['name'] = htmlentities(utf8_decode($product['name']));
            $serviceName .= $products[$key]['name'] . ' ';
        }

        // change currency 'RUB' to 'RUR'
        $currency = ($this->getCurrency($params['cart']->id_currency)->iso_code == 'RUB') ? 'RUR' : $this->getCurrency($params['cart']->id_currency)->iso_code;

        $hash_string = $eshopid . '::' . $recipientAmount . '::' . $currency . '::' . $customer->email . '::' . $serviceName . '::' . $params['cart']->id . '::::' . $secret_key;

        $url = 'http://' . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT,
            'UTF-8') . __PS_BASE_URI__ . 'index.php?controller=history';

        global $smarty;

        $smarty->assign(array(
          'logo' => _MODULE_DIR_ . $this->name . '/rbkm_logo.png',
//          'action' => _MODULE_DIR_ . $this->name . '/submit.php',
          'action' => 'https://rbkmoney.ru/acceptpurchase.aspx',
          'serviceName' => $serviceName,
          'customer' => $customer,
          'eshopId' => $eshopid,
          'currency' => $currency,
          'recipientAmount' => $recipientAmount,
          'id_cart' => intval($params['cart']->id),
          'hash' => md5($hash_string),
          'successUrl' => $url . '&status=success',
          'failUrl' => $url . '&status=fail',
        ));

        return $this->display(__FILE__, 'payment.tpl');
    }

    function hookPaymentReturn($params)
    {
        return $this->display(__FILE__, 'confirmation.tpl');
    }

    public function validateOrder(
      $id_cart,
      $id_order_state,
      $amount_paid,
      $payment_method = 'Unknown',
      $message = null,
      $extra_vars = array(),
      $currency_special = null,
      $dont_touch_amount = false,
      $secure_key = false,
      Shop $shop = null
    ) {
        parent::validateOrder($id_cart, $id_order_state, $amount_paid,
          $payment_method, $message, $extra_vars, $currency_special,
          $dont_touch_amount, $secure_key, $shop);
    }

}