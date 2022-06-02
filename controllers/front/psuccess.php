<?php
/**
 */
require_once(__DIR__ ."/../../classes/straalApi.php");

class straalPsuccessModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();


        //Classe straal

        $this->context->smarty->assign(array(
            'logs' => '',
        ));

        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true && version_compare(_PS_VERSION_, '1.7.0', '<') === true) {
            $this->setTemplate('psuccessPS16.tpl');
        }else{
            $this->setTemplate('module:straal/views/templates/front/psuccess.tpl');
        }

    }
}

?>