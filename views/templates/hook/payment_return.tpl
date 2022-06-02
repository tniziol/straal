{*
 * Straal, a module for Prestashop 1.7
 * Form to be displayed in the payment step
 *}

{if isset($smarty.get.method) && $smarty.get.method=='cc'}
<div class="straal-mid"><a href="{$smarty.get.url|unescape:"htmlall"}"><button class="btn success straal-btn">{l s='Pay now with Straal' mod='straal'}</button></a>
<div class="straal-text"><p>{l s='You will be redirected to the payment gateway.' mod='straal'}</p></div></div>


{*<script>*}
{*    function redirect_url(){*}
{*        window.location.replace("{$smarty.get.url|unescape:'htmlall'}");*}
{*    }*}
{*    setTimeout(redirect_url,25000)*}
{*</script>*}
{/if}

