{*
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
*}

<p class="payment_module">
	<a href="javascript:$('#rbkm').submit();" title="{l s='RBK Money' mod='rbkmoney'}">
		<img src="{$module_template_dir}rbkm_logo.png" alt="{l s='RBK Money' mod='rbkmoney'}"/>
		{l s='RBK Money' mod='rbkmoney'}
	</a>
</p>

<form class="hidden" id="rbkm" method="post" name="rbkm" action="https://rbkmoney.ru/acceptpurchase.aspx">
	<input type="hidden" name="eshopId" value="{$eshopId}">
	<input type="hidden" name="orderId" value="{$id_cart}">
	<input type="hidden" name="user_email" value="{$customer->email}">
	<input type="hidden" name="serviceName" value="{$serviceName}">
	<input type="hidden" name="recipientAmount" value="{$recipientAmount}">
	<input type="hidden" name="recipientCurrency" value="{$currency->iso_code}">
	<input type="hidden" name="successUrl" value="{$successUrl}">
	<input type="hidden" name="failUrl" value="{$failUrl}">
</form>
