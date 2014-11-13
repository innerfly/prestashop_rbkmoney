<style type="text/css">
	p.payment_module a.rbkmoney {
		background-image:url({$logo});
		background-position:15px 50%;
		background-repeat:no-repeat;
	}
</style>

<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module">
			<a href="javascript:void(0)" onclick="javascript:document.getElementById('rbkm').submit();" title="{l s='RBK Money' mod='rbkmoney'}" class="bankwire rbkmoney">
				{l s='RBK Money' mod='rbkmoney'}
			</a>
		</p>
	</div>
</div>

<form class="hidden" id="rbkm" method="post" name="rbkm" action="{$action}">
	<input type="hidden" name="eshopId" value="{$eshopId}">
	<input type="hidden" name="orderId" value="{$id_cart}">
	<input type="hidden" name="user_email" value="{$customer->email}">
	<input type="hidden" name="serviceName" value="{$serviceName}">
	<input type="hidden" name="recipientAmount" value="{$recipientAmount}">
	<input type="hidden" name="recipientCurrency" value="{$currency}">
	<input type="hidden" name="hash" value="{$hash}">
	<input type="hidden" name="successUrl" value="{$successUrl}">
	<input type="hidden" name="failUrl" value="{$failUrl}">
</form>