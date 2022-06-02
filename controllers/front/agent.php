<?php
/**
 */
require_once(__DIR__ ."/../../classes/straalApi.php");
require_once(__DIR__ ."/../../straal.php");

class straalAgentModuleFrontController extends ModuleFrontController
{
    public function initContent()
	{
		parent::initContent();



            $modulo = new straal();


            $respuesta = file_get_contents('php://input');
            $respuesta = json_decode($respuesta, true);
            $errores = Array();

            //Classe straal
            $straal = new straalApi();
            $headers = apache_request_headers();



            if(isset($respuesta['event']) && ($respuesta['event']=="checkout_attempt_finished") ){

                    $straal->createLog('Headers Captured', "Headers: ".json_encode($headers));
                    $straal->createLog('checkout_attempt_finished', json_encode($respuesta));

                    if(isset($headers['Authorization'])){

                        $authorization = $headers['Authorization'];
                        $authorization = str_replace("Basic ", '', $authorization);

                        $user = Configuration::get('STRAAL_USERNAME_NOTIF');
                        $password =  Configuration::get('STRAAL_PASSWORD_NOTIF');

                        if($authorization != base64_encode($user.':'.$password)){
                            $straal->createLog('403 Problem', "User or password incorrect");
                            header('HTTP/1.0 403 Forbidden');
                            die(json_encode(Array('error'=>'403', 'message' => 'User or password incorrect.')));
                        }

                    }else{
                        $straal->createLog('403 Problem', "User or password incorrect");
                        header('HTTP/1.0 403 Forbidden');
                        die(json_encode(Array('error'=>'403', 'message' => 'User or password incorrect.')));

                    }





                    if((isset($respuesta['data']['transaction']['captured']) && $respuesta['data']['transaction']['captured']==true) || (isset($respuesta['data']['transaction']['pay_by_link_payment']) && $respuesta['data']['checkout_attempt']['status'] == 'succeeded') ){
                        $total_capture = true;
                    }else{
                        $total_capture = false;
                    }

                    $straal->createLog('total_captured', "Total captured: ".$total_capture.'');

                    if(isset($respuesta['data']['transaction']) && $total_capture == true){
                        //Get order by cart ID
                        $id_cart = (int)$respuesta['data']['transaction']['order_reference'];

                        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
                            $objOrder = new Order(Order::getOrderByCartId((int)$id_cart));
                        }else{
                            $objOrder = new Order(Order::getIdByCartId((int)$id_cart));
                        }

                        $objCustomer = new Customer($objOrder->id_customer);

                        $straal->createLog('Enviando email de pagamento', 'processing');



                        //Translate title PS16 and PS17
                        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
                            $email_title = $modulo->l('Straal payment success');
                        }else{
                            $email_title = Context::getContext()->getTranslator()->trans('Straal payment success');
                        }


                        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
                            Mail::Send(
                                (int)$objOrder->id_lang, // defaut language id
                                'payment_success', // email template file to be use
                                $email_title, // email subject
                                array(
                                    '{id_order}' => (int)$objOrder->id,
                                    '{order_reference}' => $objOrder->reference,
                                    '{order_details}' => _PS_BASE_URL_.__PS_BASE_URI__.'index.php?controller=order-detail&id_order='.(int)$objOrder->id,
                                    '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME'),
                                ),
                                $objCustomer->email, // receiver email address
                                NULL, //receiver name
                                NULL, //from email address
                                NULL,  //from name
                                NULL,
                                NULL,
                                dirname(__FILE__).'/../../mails/'
                            );
                        }else{
                            $email_title = Context::getContext()->getTranslator()->trans('Straal payment success');

                            Mail::Send(
                                (int)$objOrder->id_lang, // defaut language id
                                'payment_success', // email template file to be use
                                $email_title, // email subject
                                array(
                                    '{id_order}' => (int)$objOrder->id,
                                    '{order_reference}' => $objOrder->reference,
                                    '{order_details}' => _PS_BASE_URL_.__PS_BASE_URI__.'index.php?controller=order-detail&id_order='.(int)$objOrder->id,
                                    '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME'),
                                ),
                                $objCustomer->email, // receiver email address
                                NULL, //receiver name
                                NULL, //from email address
                                NULL,  //from name
                                NULL,
                                NULL,
                                _PS_BASE_URL_.__PS_BASE_URI__.'modules/straal/mails/'
                            );
                        }

                        //Get the order for change state.
                        $history = new OrderHistory();
                        $history->id_order = (int)$objOrder->id;
                        $history->changeIdOrderState(Configuration::get('STRAAL_APROVED'), (int)$objOrder->id);
                        $history->add();

                        $straal->createLog('Encomienda pagada', 'Fué capturado '.$total_capture.' de '.$respuesta['data']['transaction']['amount']);

                    }else{
                        $straal->createLog('parcialmente pago', "Total captured: ".$total_capture.'');


                        if(isset($respuesta['data']['transaction']['errors']) && count($respuesta['data']['transaction']['errors'])>0){
                            $errores = $respuesta['data']['transaction']['errors'];
                        }

                        $id_cart = (int)$respuesta['data']['transaction']['order_reference'];


                        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
                            $objOrder = new Order(Order::getOrderByCartId((int)$id_cart));
                        }else{
                            $objOrder = new Order(Order::getIdByCartId((int)$id_cart));
                        }


                        $objCustomer = new Customer($objOrder->id_customer);



                        //Translate email title
                        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
                            $email_title = $modulo->l('Straal payment error.');
                        }else{
                            $email_title = Context::getContext()->getTranslator()->trans('Straal payment error');
                        }




                        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
                            Mail::Send(
                                (int)$objOrder->id_lang, // defaut language id
                                'payment_error', // email template file to be use
                                $email_title, // email subject
                                array(
                                    '{id_order}' => (int)$objOrder->id,
                                    '{order_reference}' => $objOrder->reference,
                                    '{order_details}' => _PS_BASE_URL_.__PS_BASE_URI__.'index.php?controller=order-detail&id_order='.(int)$objOrder->id,
                                    '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME'),
                                ),
                                $objCustomer->email, // receiver email address
                                NULL, //receiver name
                                NULL, //from email address
                                NULL,  //from name
                                NULL,
                                NULL,
                                dirname(__FILE__).'/../../mails/'
                            );
                        }else{
                            $email_title = Context::getContext()->getTranslator()->trans('Straal payment success');

                            Mail::Send(
                                (int)$objOrder->id_lang, // defaut language id
                                'payment_success', // email template file to be use
                                $email_title, // email subject
                                array(
                                    '{id_order}' => (int)$objOrder->id,
                                    '{order_reference}' => $objOrder->reference,
                                    '{order_details}' => _PS_BASE_URL_.__PS_BASE_URI__.'index.php?controller=order-detail&id_order='.(int)$objOrder->id,
                                    '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME'),
                                ),
                                $objCustomer->email, // receiver email address
                                NULL, //receiver name
                                NULL, //from email address
                                NULL,  //from name
                                NULL,
                                NULL,
                                _PS_BASE_URL_.__PS_BASE_URI__.'modules/straal/mails/'
                            );
                        }







                        $history = new OrderHistory();
                        $history->id_order = (int)$objOrder->id;
                        $history->changeIdOrderState(Configuration::get('STRAAL_ERROR'), (int)$objOrder->id);
                        $history->add();

                        $straal->createLog('Encomienda parcialmente pagada', 'Fué capturado '.$total_capture.' de '.$respuesta['data']['transaction']['amount']);
                    }
                }



            $this->context->smarty->assign(array(
                'ativar_nome' => Configuration::get('activar_nome'),
            ));


            if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
                $this->setTemplate('agentPS16.tpl');
            }else{
                $this->setTemplate('module:straal/views/templates/front/agent.tpl');
            }

	}
}

?>