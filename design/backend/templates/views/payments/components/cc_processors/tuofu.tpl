<div class="control-group">
    <label class="control-label" for="accno">Tuofu Account:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][accno]" id="accno" value="{$processor_params.accno}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="md5key">Tuofu MD5Key:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][md5key]" id="md5key" value="{$processor_params.md5key}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="paytos_currency">Paytos {__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][paytos_currency]" id="paytos_currency">
            <option value="124" {if $processor_params.paytos_currency == "124"}selected="selected"{/if}>{__("currency_code_cad")}</option>
            <option value="978" {if $processor_params.paytos_currency == "978"}selected="selected"{/if}>{__("currency_code_eur")}</option>
            <option value="826" {if $processor_params.paytos_currency == "826"}selected="selected"{/if}>{__("currency_code_gbp")}</option>
            <option value="840" {if $processor_params.paytos_currency == "840"}selected="selected"{/if}>{__("currency_code_usd")}</option>
            <option value="392" {if $processor_params.paytos_currency == "392"}selected="selected"{/if}>{__("currency_code_jpy")}</option>
            <option value="643" {if $processor_params.paytos_currency == "643"}selected="selected"{/if}>{__("currency_code_rur")}</option>
            <option value="036" {if $processor_params.paytos_currency == "036"}selected="selected"{/if}>{__("currency_code_aud")}</option>
        </select>
    </div>
</div>
