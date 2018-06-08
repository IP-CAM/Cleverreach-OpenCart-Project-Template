<?php

require_once DIR_SYSTEM . 'library/cleverreach/cleverreach.php';

define('CLEVERREACH_AUTH_URL',  "https://rest.cleverreach.com/oauth/authorize.php");
define('CLEVERREACH_TOKEN_URL', "https://rest.cleverreach.com/oauth/token.php");

class ModelCleverreachCleverreach extends Model
{
    /**
     * @var CleverreachRestClient
     */
    protected $restClient;

    /**
     * @var Log
     */
    protected $logger;

    public function __construct($registry) {
        parent::__construct($registry);
        $this->logger = new Log('cleverreach.log');
    }

    public function addEvents()
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` LIKE 'cleverreach%'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code`='cleverreach_customer_add', `trigger`='catalog/model/account/customer/addCustomer/after', `action`='cleverreach/cleverreach/addCustomer', `status`='1', `sort_order`='0'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code`='cleverreach_customer_edit', `trigger`='catalog/model/account/customer/editNewsletter/after', `action`='cleverreach/cleverreach/editCustomer', `status`='1', `sort_order`='0'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code`='cleverreach_customer_list', `trigger`='admin/view/customer/customer_list/before', `action`='cleverreach/cleverreach/renderCustomerList', `status`='1', `sort_order`='0'");
    }

    public function init()
    {
        if (!$this->restClient) {
            $this->restClient = CleverreachRestClient::getInstance($this->config->get('cleverreach_token'));
        }
    }

    public function createRedirectUri($sessionToken = true)
    {
        if (!$sessionToken) {
            $userToken = $this->session->data['user_token'];
        } else {
            $userToken = $this->request->get['user_token'];
        }

        // return $this->url->link('cleverreach/cleverreach', 'user_token=' . $this->session->data['user_token'], true);
        return $this->url->link('cleverreach/cleverreach');
    }

    public function getAuthenticationUrl($clientId)
    {
        return CLEVERREACH_AUTH_URL . "?client_id=" . $clientId . "&grant=basic&response_type=code&redirect_uri=" . $this->createRedirectUri();
    }

    public function getAuthenticationToken(array $inputs)
    {
        try {
            $inputs["grant_type"] = "authorization_code";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, CLEVERREACH_TOKEN_URL);
            curl_setopt($curl, CURLOPT_POST, sizeof($inputs));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $inputs);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $result = json_decode(curl_exec($curl), true);
            curl_close($curl);

            if (isset($result['access_token'])) {
                return $result['access_token'];
            } else {
                $this->logger->write("Cleverreach Error (ADMIN): " . json_encode($result));
            }
        } catch(\Exception $e) {
            $this->logger->write("Cleverreach Error (ADMIN): " . $e->getMessage());
        }

        return false;
    }

    public function getGroups()
    {
        $this->init();
        return $this->restClient->getGroups();
    }

    public function getForms()
    {
        $this->init();
        return $this->restClient->getForms();
    }

    public function upsertReceivers($groupId, array $customerIds)
    {
        $this->init();
        $receivers = [];

        $this->load->model('customer/customer');
        foreach ($customerIds as $customerId) {
            $customer = $this->model_customer_customer->getCustomer($customerId);

            $receivers[] = $this->restClient->prepareReceiver($customer['email'], [
                'firstname' => $customer['firstname'] ,
                'lastname'  => $customer['lastname']
            ], time());
        }

        return $this->restClient->upsertReceivers($groupId, $receivers);
    }

    public function getLines()
    {
        $this->load->model('setting/store');
        $this->load->model('localisation/language');

        $stores    = $this->model_setting_store->getStores();
        $languages = $this->model_localisation_language->getLanguages();
        $lines    = $this->config->get('cleverreach_lines');

        array_unshift($stores, ['store_id' => 0, 'name' => 'Default']);

        $data = [];
        foreach ($stores as $store) {
            foreach ($languages as $language) {
                $lineId = $store['store_id'] . '_' . $language['language_id'];

                $data[$lineId] = [
                    'id'       => $lineId ,
                    'store'    => $store['name'] ,
                    'language' => $language['name'] ,
                    'group'    => ( (is_array($lines) && isset($lines[$lineId]) && isset($lines[$lineId]['group'])) ? $lines[$lineId]['group'] : '' ) ,
                    'form'     => ( (is_array($lines) && isset($lines[$lineId]) && isset($lines[$lineId]['form'])) ? $lines[$lineId]['form'] : '' )
                ];
            }
        }

        return $data;
    }

}
