<?php
/**
 */
require_once(__DIR__ ."/../../classes/straalApi.php");

class straalLogsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

            $straal = new straalApi();
            $logs = $straal->getLogs();
//            $remap = $straal->reMapAllUsers();
//            die(json_encode($remap));
//        $respuesta = file_get_contents('php://input');
//        $respuesta = json_decode($respuesta, true);

        //Classe straal


        $this->context->smarty->assign(array(
            'logs' => $logs,
        ));
        $this->setTemplate('module:straal/views/templates/front/logs.tpl');
    }
}

?>