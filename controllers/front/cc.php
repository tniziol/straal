<?php
/**
 */
ini_set('precision', 10);
ini_set('serialize_precision', 10);

require_once(__DIR__ ."/../../classes/straalApi.php");


class straalccModuleFrontController extends ModuleFrontController
{





    /**
     * Processa os dados enviados pelo formulário de pagamento
     */
    public function postProcess()
    {

        //Get current cart object from session
        $cart = $this->context->cart;

        //Classe straal
        $straal = new straalApi();

        //Verify if this payment module is authorized
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'straal') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            die($this->l('This payment method is not available.'));
        }

        /**
         * Verify if this module is enabled and if the cart has
         * a valid customer, delivery address and invoice address
         */
        if (!$this->module->active || $cart->id_customer == 0 || $cart->id_address_delivery == 0
            || $cart->id_address_invoice == 0) {
            Tools::redirect(__PS_BASE_URI__.'index.php?controller=order&step=1');
        }

        /** @var CustomerCore $customer */
        $customer = new Customer($cart->id_customer);

        /**
         * Check if this is a valid customer account
         */
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect(__PS_BASE_URI__.'index.php?controller=order&step=1');
        }


        //Get currency ISO
        $currency = Currency::getCurrency($this->context->cart->id_currency);
        $iso_currency = $currency['iso_code'];

        //Get total Cart
        $total = (float) $this->context->cart->getOrderTotal(true, Cart::BOTH);

        //Get lang ISO
        $lang = Language::getLanguage($this->context->cart->id_lang);
        $iso_lang = $lang['iso_code'];

        //Get id_cart
        $id_cart = $this->context->cart->id;


        $response = $straal->createNewPaymentWithCC($iso_currency, $total, $iso_lang, "Prestashop - ".Configuration::get('PS_SHOP_NAME'), (string)$id_cart, 'oneshot', (string)$id_cart);


        //----------------------------------------------------------------//
        //----------------------------------------------------------------//
        //----------------------------------------------------------------//
        //----------------------------------------------------------------//
        //----------------------------------------------------------------//

            //TENGO QUE CREAR UNA TABLA EN LA BD PARA GUARDAR ENCOMIENDAS Y ASÍ PODER CAPTURARLAS DESPUES
//        $sql = "INSERT INTO "._DB_PREFIX_."ep_orders (method, id_cart, link, title) VALUES ('".$multibanco['method']['type']."', ".(int)$cart->id.", '".urlencode($multibanco['method']['url'])."', 'Pagar Agora: ')";
//        Db::getInstance()->execute($sql);

        /**
         * Place the order
         */


        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {

            $this->module->validateOrder(
                (int) $id_cart,
                Configuration::get('STRAAL_WAITING_PAYMENT_CC'),
                (float) $total,
                Configuration::get('STRAAL_PAYMENT_NAME'),
                null,
                Array('{URL}' => $response['checkout_url'], '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME')),
                (int) $this->context->currency->id,
                false,
                $customer->secure_key
            );

            Mail::Send(
                (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
                'visa', // email template file to be use
                Configuration::get('STRAAL_PAYMENT_NAME'), // email subject
                array(
                    '{URL}' => $response['checkout_url'],
                    '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME'),
                ),
                $this->context->customer->email, // receiver email address
                NULL, //receiver name
                NULL, //from email address
                NULL,  //from name
                NULL,
                NULL,
                dirname(__FILE__).'/../../mails/'
            );

            $objOrder = new Order(Order::getOrderByCartId((int)$cart->id));

        }else{
            $this->module->validateOrder(
                (int) $id_cart,
                Configuration::get('STRAAL_WAITING_PAYMENT_CC'),
                (float) $total,
                Configuration::get('STRAAL_PAYMENT_NAME'),
                null,
                Array('{URL}' => $response['checkout_url'], '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME')),
                (int) $this->context->currency->id,
                false,
                $customer->secure_key
            );

            Mail::Send(
                (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
                'visa', // email template file to be use
                Configuration::get('STRAAL_PAYMENT_NAME'), // email subject
                array(
                    '{URL}' => $response['checkout_url'],
                    '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME'),
                ),
                $this->context->customer->email, // receiver email address
                NULL, //receiver name
                NULL, //from email address
                NULL,  //from name
                NULL,
                NULL,
                _PS_BASE_URL_.__PS_BASE_URI__.'modules/straal/mails/'
            );

            $objOrder = new Order(Order::getIdByCartId((int)$cart->id));
        }


        /**
         * Redirect the customer to the order confirmation page
         */



        $straal->mapOrderWithPaymentUrl($objOrder->id, $response['checkout_url']);

        Tools::redirect(__PS_BASE_URI__.'index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key.'&method=cc&monto='.' '.(float) $this->context->cart->getOrderTotal(true, Cart::BOTH).'&url='.urlencode($response['checkout_url']));
    }


}