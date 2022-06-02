

{if ($type=='unit_price' || $hook_origin=='product_sheet') && isset($product.id_category_default) && $product.id_category_default == Configuration::get('EASYPAY_CATEGORY_SUSCP') }({l s='Subscrição' mod='easypay'}  


{foreach from=$product.features item=feature}
    {if $feature.id_feature==Configuration::get('EASYPAY_EXP_TIME')} - {$feature.value}{/if}
{/foreach}


){/if}

