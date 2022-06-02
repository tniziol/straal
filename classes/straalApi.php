<?php
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

// require _PS_MODULE_DIR_.'psgdpr/psgdpr.php';




class straalApi
{

    /**
     * @var string
     */
    public $test;

    /**
     * @var string
     */
    private $api_id;


    /**
     * @var string
     */
    private $api_key;


    /**
     * @var bool
     */
    private $auto_auth;

    /**
     * @var string
     */
    public $api_url;

    /**
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->context = Context::getContext();

        //Variables de teste
        $this->api_key = Configuration::get('STRAAL_API_KEY');
        $this->auto_auth = Configuration::get('STRAAL_AUTO_AUTH');
        $this->api_url = "https://api.straal.com/";

    }

    //editar
    public function checkConnection(){
        return true;
    }

    //Crear logs de Straal en la DB
    public function createLog($title, $description, $id_user = 0, $date = false){

        //Set date to NOW() if not is filled.
        if($date==false){
            $date = date('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO "._DB_PREFIX_."straal_logs (title, description, id_user, date) VALUES ('$title', '$description', '$id_user', '$date')";
        return Db::getInstance()->execute($sql);
    }

    private function postApi($url, $body=[], $headers=[]){

        $curlOpts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => 1,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_PASSWORD => $this->api_key
        ];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_USERPWD, ":".$this->api_key);

        curl_setopt_array($curl, $curlOpts);

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response, true);

        return $response;

    }

    public function getApi($url, $body=[], $headers=[]){
        $curlOpts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_PASSWORD => $this->api_key
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, ":".$this->api_key);
        curl_setopt_array($curl, $curlOpts);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);

        return $response;

    }

    private function createUser($email){
        //API Doc https://api-reference.straal.com/#resources-customers-create-a-customer

        //create Stral Customer Reference
        $reference = $this->generateStraalUserReference();

        //API comunication
        $body = ['email' => $email, 'reference' => $reference];
        $url = $this->api_url."v1/customers";
        $response = $this->postApi($url, $body);


        //Validate if have errors
        if(!isset($response['errors'])){
            //Mapping customers Straal/Prestashop
            $id_user = $this->context->customer->id;
            $id_guest = $this->context->customer->id;
            $this->mapUser($response['id'], $id_user, $id_guest, $response['email'], $response['reference'], $response['created_at']);
        }else if($response['errors'][0]['code']==12005){
            //Insert code for debug mode
            $this->createLog('Error at create user in straal',$response['errors'][0]['message'], $this->context->customer->id);
            $this->reMapAllUsers();
            return true;
        }else{
            $this->createLog('Error at create user in straal',$response['errors'][0]['message'], $this->context->customer->id);
            $this->reMapAllUsers();
            return false;
        }
        return true;
    }

    //Public Map order URL with Payment Url
    public function mapOrderWithPaymentUrl($id_order, $payment_url){
        $sql = "INSERT INTO "._DB_PREFIX_."straal_payment_url (id_order, payment_url, date) VALUES (".$id_order.", '".$payment_url."', NOW())";
        return Db::getInstance()->execute($sql);
    }

    //Public Get url mapped
    public function getOrderPaymentUrl($id_order){
        $sql = "SELECT * FROM "._DB_PREFIX_."straal_payment_url WHERE id_order = ".$id_order." AND (date + INTERVAL 19 MINUTE) > NOW() ORDER BY date DESC LIMIT 1";
        return Db::getInstance()->executeS($sql);
    }

    public function reMapAllUsers(){

        $sql = "DELETE FROM "._DB_PREFIX_."straal_users_map";
        Db::getInstance()->execute($sql);

        $pagina = 1;
        while(true){

            $url = $this->api_url."v1/customers?per_page=100&page=".$pagina;
            $response = $this->getApi($url);



            if(count($response['data'])>0){
                foreach($response['data'] as $customer){
                    (int)$id_customer = $this->decodeIdCustomerFromStraalReference($customer['reference']);
                    (int)$id_guest = $this->decodeIdGuestFromStraalReference($customer['reference']);

                    $this->mapUser($customer['id'], $id_customer, $id_guest, $customer['email'], $customer['reference']);
                }
            }else{
                break;
            }

            $pagina += 1;
        }

        return true;
    }

    private function decodeIdCustomerFromStraalReference($straal_reference){
        $id_customer = 0;
        if(strpos($straal_reference, 'customer') !== false){
            $id_customer = str_replace('customer', '', $straal_reference);
        }
        return $id_customer;
    }

    private function decodeIdGuestFromStraalReference($straal_reference){
        $id_customer = 0;
        if(strpos($straal_reference, 'guest') !== false){
            $id_customer = str_replace('guest', '', $straal_reference);
        }
        return $id_customer;
    }

    private function mapUser($straal_id_user, $prestashop_id_user, $prestashop_id_guest, $straal_email, $straal_reference, $straal_created_at = false){

        if($straal_created_at==false){
            $straal_created_at = date('Y-m-d H:is:s');
        }

        $sql = "INSERT INTO "._DB_PREFIX_."straal_users_map 
                    (straal_id_user, prestashop_id_user, prestashop_id_guest, straal_email, straal_reference, straal_created_at)
                VALUES 
                    ('$straal_id_user', '$prestashop_id_user', '$prestashop_id_guest', '$straal_email', '$straal_reference', '$straal_created_at')";

        return Db::getInstance()->execute($sql);
    }

    private function getMappedUser($straal_reference){

        $sql = "Select * from "._DB_PREFIX_."straal_users_map WHERE straal_reference = '$straal_reference'";

        $resp = Db::getInstance()->executeS($sql);

        if(count($resp)>0){
            return $resp;
        }else{
            return false;
        }
    }

    private function generateStraalUserReference($email = ''){

        //Validate if is a registered customer or a guest
        if($this->context->customer->id==null){
            $id_user = 0;
            $id_guest = $this->context->customer->id_guest;
        }else{
            $id_user = $this->context->customer->id;
            $id_guest = 0;
        }

        $email = $this->context->customer->email;

        //Create Reference for guest or registered user
        if($id_guest==0){
//            $reference = "customer".$id_user."email";
            $reference = $email;
        }else{
//            $reference = "guest".$id_guest;
            $reference = $email;
        }

        return $reference;
    }

    public function createCardForCurrentUser($cardHolder, $cardNumber, $cvv, $expiry_month, $expiry_year){

        //Define active user id if is guest or customer
        if($this->context->customer->id==null){
            $id_user = 0;
            $id_guest = $this->context->customer->id_guest;
        }else{
            $id_user = $this->context->customer->id;
            $id_guest = 0;
        }

        //create reference, if is guest or customer
        if($id_guest>0){
            $user_reference = "guest".$id_guest;
        }else{
            $user_reference = "customer".$id_user;
        }

        //Obtain mapped user Straal API / Prestashop
        $straal_user = $this->getMappedUser($user_reference);


        //API comunication
        if($straal_user!=false){
            $url = $this->api_url."v1/customers/".$straal_user[0]['straal_id_user']."/cards";
        }else{
            $this->createLog('No se pudo crear la tarjeta', 'El usuario '.$user_reference.' no existe o no está mapeado');
            return false;
        }


        //Get Current IP Remote address
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $body = [
                    'name' => $cardHolder,
                    'number' => $cardNumber,
                    'cvv' => $cvv,
                    'expiry_month' => $expiry_month,
                    'expiry_year' => $expiry_year,
                    'origin_ipaddr' => $ip
                ];


        $response = $this->postApi($url, $body);


        //Check if Card is created
        if(isset($response['id'])){
            $this->mapCard($response['id'], $straal_user[0]['straal_id_user'], $response['num_last_4'], $ip);
            return $response;
        }else{
            $this->createLog('No se pudo crear la card', json_encode($response));
            return false;
        }
    }

    private function mapCard($straal_id_card, $straal_id_customer, $straal_num_last_4, $straal_origin_ipaddr){
        $sql = "
                    INSERT INTO "._DB_PREFIX_."straal_card_map
                    (
                        straal_id_user,
                        straal_id_card,
                        straal_num_last_4,
                        straal_origin_ipaddr,
                        created_date
                    ) 
                    VALUES 
                    (
                        '$straal_id_customer',
                        '$straal_id_card',
                        '$straal_num_last_4',
                        '$straal_origin_ipaddr',
                        NOW()
                    );
               ";

        $response = Db::getInstance()->execute($sql);
    }

    private function getMappedCard($straal_id_customer){
        $sql = "SELECT * FROM "._DB_PREFIX_."straal_card_map WHERE straal_id_user = '$straal_id_customer'";
        $response = Db::getInstance()->executeS($sql);

        if(count($response)>0){
            return $response[0];
        }else{
            return false;
        }
    }

    public function createNewPaymentWithCC($currency_iso, $amount, $lang_iso, $order_description, $order_reference, $type_transaction, $id_cart)
    {
        //Function DOC https://api-reference.straal.com/#resources-checkout-page

        //Create and map user
        $email = $this->context->customer->email;
        $this->createUser($email);


        //Reference straal, can be user or guest
        $straal_user_reference = $this->generateStraalUserReference();
        //Get customer straal ID from PS DB
        $straal_id_user = $this->getMappedUser($straal_user_reference);
        if($straal_id_user!=false AND count($straal_id_user)>0){
            $straal_id_user = $straal_id_user[0]['straal_id_user'];
        }else{
            $this->createLog("No se pudo crear el pago", "El usuario ".$straal_id_user." No está registado en la BD de prestashop.");
            return false;
        }

        //Transform amount to Straal Format (7.65 to 765)
        $whole = floor($amount);
        $fraction = $amount - $whole;
        $amount = (string)$whole.(string)($fraction*100);


        //Data required
        $body = [
            "currency" => $currency_iso,
            "amount" => (int)$amount,
            "ttl" => 800,
            "return_url" => _PS_BASE_URL_.__PS_BASE_URI__."?fc=module&module=straal&controller=psuccess",
            "failure_url" => _PS_BASE_URL_.__PS_BASE_URI__."?fc=module&module=straal&controller=perror",
            "lang" => $lang_iso,
            "order_description" =>  $order_description,
            "order_reference" => $order_reference,
            "card_transaction" => [
                "type" => "oneclick"
            ],
            "extra_data" => [
                "some" => $id_cart
            ]
        ];

        $response = $this->postApi("https://api.straal.com/v1/customers/".$straal_id_user."/checkouts", $body);



        if(isset($response['errors']) && $response['errors'][0]['code']==40403){
            $this->createLog("Error al crear el checkout", "Usuario: ".$straal_id_user." -->".$response['errors'][0]['code']);
            return false;
        }else{
            return $response;
        }


    }

    public function getLogs(){
        $sql = "SELECT * FROM "._DB_PREFIX_."straal_logs order by date desc";
        $result = Db::getInstance()->executeS($sql);
        if(count($result)>0){
            return $result;
        }else{
            return false;
        }

    }



}