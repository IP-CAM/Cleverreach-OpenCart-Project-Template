<?php

require_once DIR_SYSTEM . 'library/cleverreach.php';

define('CLEVERREACH_AUTH_URL',  "https://rest.cleverreach.com/oauth/authorize.php");
define('CLEVERREACH_TOKEN_URL', "https://rest.cleverreach.com/oauth/token.php");

class ModelCleverreachCleverreach extends Model
{
    protected $restClient = null;

    public function __construct($registry) {
        parent::__construct($registry);
        $this->logger = new Log('cleverreach.log');
    }

    public function addEvents()
    {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` LIKE 'cleverreach%'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code`='cleverreach_customer_add', `trigger`='catalog/model/account/customer/addCustomer/after', `action`='cleverreach/cleverreach/addCustomer', `status`='1', `sort_order`='0'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code`='cleverreach_customer_edit', `trigger`='catalog/model/account/customer/editCustomer/after', `action`='cleverreach/cleverreach/editCustomer', `status`='1', `sort_order`='0'");
    }

    public function initRestClient()
    {
        if (!$this->restClient) {
            $this->restClient = new \CR\tools\rest("https://rest.cleverreach.com/v3");
            $this->restClient->setAuthMode("bearer", $this->config->get('cleverreach_token'));
        }
    }

    public function callRestClient($method, $route)
    {
        $this->initRestClient();
        $result = [];

        try {
            if ($method == 'get') {
                $result = $this->restClient->get($route);
            }
        }  catch(\Exception $e) {
            $this->logger->write("Cleverreach Error (ADMIN): " . $e->getMessage());
        }

        return $result;
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
        return $this->callRestClient('get', '/groups');
    }

    public function getLines()
    {
        $this->load->model('setting/store');
        $this->load->model('localisation/language');

        $stores    = $this->model_setting_store->getStores();
        $languages = $this->model_localisation_language->getLanguages();
        $groups    = $this->config->get('cleverreach_lines');

        array_unshift($stores, ['store_id' => 0, 'name' => 'Default']);

        $data = [];
        foreach ($stores as $store) {
            foreach ($languages as $language) {
                $lineId = $store['store_id'] . '_' . $language['language_id'];

                $data[$lineId] = [
                    'id'       => $lineId ,
                    'store'    => $store['name'] ,
                    'language' => $language['name'] ,
                    'group'    => ( (is_array($groups) && isset($groups[$lineId])) ? $groups[$lineId] : '' )
                ];
            }
        }

        return $data;
    }

}
