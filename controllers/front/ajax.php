<?php
/**
 */
require_once(__DIR__ ."/../../classes/straalApi.php");

class straalAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
            $this->setTemplate('agentPS16.tpl');
        }else{
            $this->setTemplate('module:straal/views/templates/front/agent.tpl');
        }


        //Execute displayAjaxCreateUrl()
        if (isset($_POST['action']) && $_POST['action']=='createUrl' && version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
            $this->displayAjaxCreateUrl();
            die();
        }


    }


    public function displayAjaxCreateUrl()
    {

        $straal = new straalApi();
        $id_order = $_POST['id_order'];
        $objOrder = new Order($id_order);
        $currency = new Currency($objOrder->id_currency);
        $lang = new Language($objOrder->id_lang);

        $old_url = $straal->getOrderPaymentUrl($id_order);

        if(count($old_url)>0){
            $url = $old_url[0]['payment_url'];
            echo json_encode($url);
        }else{
            $response = $straal->createNewPaymentWithCC($currency->iso_code, $objOrder->total_paid, $lang->iso_code, "Prestashop - ".Configuration::get('PS_SHOP_NAME'), (string)$objOrder->id_cart, 'oneshot', (string)$objOrder->id_cart);
            $straal->mapOrderWithPaymentUrl($id_order, $response['checkout_url']);
            echo json_encode($response['checkout_url']);
        }

        return true;
    }
}

?>