<div class="control-group">
  <label class="control-label" for="api_key">{__("allpaypayz_api_key")}</label>
  <div class="controls">
    <input type="password" name="payment_data[processor_params][api_key]"
           id="api_key" value="{$processor_params.api_key}" />
  </div>
</div>
<div class="control-group">
  <label class="control-label" for="sign_key">{__("allpaypayz_sign_key")}</label>
  <div class="controls">
    <input type="password" name="payment_data[processor_params][sign_key]"
           id="sign_key" value="{$processor_params.sign_key}" />
  </div>
</div>
<div class="control-group">
  <label class="control-label" for="base_url">{__("allpaypayz_base_url")}</label>
  <div class="controls">
    <select name="payment_data[processor_params][base_url]" id="base_url">
      <option value="https://api4.allpaypayz.com" {if $processor_params.base_url == 'https://api4.allpaypayz.com'}selected{/if}>Production</option>
      <option value="https://staging-api4.allpaypayz.com" {if $processor_params.base_url == 'https://staging-api4.allpaypayz.com'}selected{/if}>Staging</option>
    </select>
  </div>
</div>
<div class="control-group">
  <label class="control-label" for="payment_method">{__("allpaypayz_payment_method")}</label>
  <div class="controls">
    <input type="text" name="payment_data[processor_params][payment_method]"
           id="payment_method" value="{$processor_params.payment_method|default:"card"}" />
  </div>
</div>
