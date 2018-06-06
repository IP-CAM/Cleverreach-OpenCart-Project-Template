<?php

require_once DIR_SYSTEM . 'library/cleverreach/cleverreach.php';

class ModelCleverreachCleverreach extends Model
{

    /**
     * @var CleverreachRestClient
     */
    protected $restClient;

    public function __construct($registry) {
        parent::__construct($registry);
    }

    public function init()
    {
        if (!$this->restClient) {
            $this->restClient = CleverreachRestClient::getInstance($this->config->get('cleverreach_token'));
        }
    }

    public function addReceiver($email, $attributes)
    {
        $lines = $this->config->get('cleverreach_lines');
        $lineId = $this->config->get('config_store_id') . '_' . $this->config->get('config_language_id');

        if ($lines && isset($lines[$lineId])) {
            CleverreachRestClient::getInstance($this->config->get('cleverreach_token'))->addReceiver($lines[$lineId]['group'], $lines[$lineId]['form'], $email, $attributes);
        }
    }

    public function activateReceiver($email)
    {
        $lines = $this->config->get('cleverreach_lines');
        $lineId = $this->config->get('config_store_id') . '_' . $this->config->get('config_language_id');

        if ($lines && isset($lines[$lineId])) {
            CleverreachRestClient::getInstance($this->config->get('cleverreach_token'))->activateReceiver($lines[$lineId]['form'], $email);
        }
    }

    public function deactivateReceiver($email)
    {
        $lines = $this->config->get('cleverreach_lines');
        $lineId = $this->config->get('config_store_id') . '_' . $this->config->get('config_language_id');

        if ($lines && isset($lines[$lineId])) {
            CleverreachRestClient::getInstance($this->config->get('cleverreach_token'))->deactivateReceiver($lines[$lineId]['group'], $email);
        }
    }

}
