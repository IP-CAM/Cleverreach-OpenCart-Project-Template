<?php

require_once DIR_SYSTEM . 'library/cleverreach/rest.php';

class CleverreachRestClient
{
    /**
     * @var CleverreachRestClient
     */
    protected static $instance;

    /**
     * @var \CR\tools\rest
     */
    protected static $rest;

    public static function getInstance($cleverreachToken)
    {
        if (!self::$instance) {
            self::$instance = new CleverreachRestClient();
            self::$rest = new \CR\tools\rest("https://rest.cleverreach.com/v3");
            self::$rest->setAuthMode("bearer", $cleverreachToken);
        }

        return self::$instance;
    }

    /**
     * @var Log
     */
    protected $logger;

    protected function __construct()
    {
        $this->logger = new Log('cleverreach.log');
    }

    public function callRestClient($method, $route, $data = [])
    {
        $result = [];

        try {
            if ($method == 'get') {
                $result = self::$rest->get($route);
            } elseif ($method == 'post') {
                $result = self::$rest->post($route, $data);
            } elseif ($method == 'put') {
                $result = self::$rest->put($route, $data);
            }
        }  catch(\Exception $e) {
            $this->logger->write("Cleverreach Error (Rest Client): " . $e->getMessage());
        }

        return $result;
    }

    public function getGroups()
    {
        return $this->callRestClient('get', '/groups');
    }

    public function getForms()
    {
        return $this->callRestClient('get', '/forms');
    }

    public function addReceiver($groupId, $formId, $email, array $attributes = [], array $doiData = null)
    {
        $result = $this->callRestClient('post', '/groups/'. $groupId . '/receivers', $this->prepareReceiver($email, $attributes));

        if ($result) {
            $result = $this->activateReceiver($formId, $email, $doiData);
        }

        return $result;
    }

    public function activateReceiver($formId, $email, array $doiData = null)
    {
        return $this->callRestClient('post', '/forms/'. $formId . '/send/activate', [
            'email' => $email ,
            'doidata' => ($doiData ? $doiData : [
                "user_ip"    => $_SERVER["REMOTE_ADDR"],
                "referer"    => $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"],
                "user_agent" => $_SERVER["HTTP_USER_AGENT"]
            ])
        ]);
    }

    public function deactivateReceiver($groupId, $email)
    {
        return $this->callRestClient('put', '/groups/'. $groupId . '/receivers/' . $email . '/deactivate', []);
    }

    public function upsertReceivers($groupId, array $receivers)
    {
        return $this->callRestClient('post', '/groups/'. $groupId . '/receivers/upsert', $receivers);
    }

    public function prepareReceiver($email, $globalAttributes, $activated = 0)
    {
        return [
            'email'      => $email ,
            'registered' => time() ,
            'activated'  => $activated ,
            'source'     => 'OpenCart' ,
            'global_attributes' => $globalAttributes
        ];
    }

}
