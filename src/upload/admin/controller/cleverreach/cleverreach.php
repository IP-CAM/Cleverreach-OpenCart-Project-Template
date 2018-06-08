<?php

class ControllerCleverreachCleverreach extends Controller
{
    protected $error = [];

    public function index()
    {
        $this->load->language('cleverreach/cleverreach');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('cleverreach/cleverreach');

        $data = $this->language->all();

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['cleverreach_lines']) && $this->validate()) {
            $this->model_setting_setting->editSetting('cleverreach', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('cleverreach/cleverreach', 'user_token=' . $this->session->data['user_token'], true));

        } elseif ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting('cleverreach', $this->request->post);
            $this->response->redirect($this->model_cleverreach_cleverreach->getAuthenticationUrl($this->request->post['cleverreach_client_id']));

        } elseif ($this->request->server['REQUEST_METHOD'] == 'GET' && isset($this->request->get['code'])) {
            $token = $this->model_cleverreach_cleverreach->getAuthenticationToken([
                'client_id'     => $this->config->get('cleverreach_client_id') ,
                'client_secret' => $this->config->get('cleverreach_client_secret') ,
                'redirect_uri'  => $this->model_cleverreach_cleverreach->createRedirectUri(true) ,
                'code'          => $this->request->get['code']
            ]);

            if (!$token) {
                $this->error['warning'] = $this->language->get('error_token');
            } else {
                $this->model_setting_setting->editSetting('cleverreach', [
                    'cleverreach_client_id'     => $this->config->get('cleverreach_client_id') ,
                    'cleverreach_client_secret' => $this->config->get('cleverreach_client_secret') ,
                    'cleverreach_token'         => $token
                ]);

                $this->session->data['success'] = $this->language->get('text_success_token');
                $this->response->redirect($this->url->link('cleverreach/cleverreach', 'user_token=' . $this->session->data['user_token'], true));
            }
        }

        if ($this->config->has('cleverreach_token')) {
            $this->model_cleverreach_cleverreach->addEvents();
            $data['cleverreach_token'] = $this->config->get('cleverreach_token');
            $data['groups'] = $this->model_cleverreach_cleverreach->getGroups();
            $data['forms'] = $this->model_cleverreach_cleverreach->getForms();
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('cleverreach/cleverreach', 'user_token=' . $this->session->data['user_token'], true)
        );

        if (isset($this->request->post['cleverreach_client_id'])) {
            $data['cleverreach_client_id'] = $this->request->post['cleverreach_client_id'];
        } else {
            $data['cleverreach_client_id'] = $this->config->get('cleverreach_client_id');
        }

        if (isset($this->request->post['cleverreach_client_secret'])) {
            $data['cleverreach_client_secret'] = $this->request->post['cleverreach_client_secret'];
        } else {
            $data['cleverreach_client_secret'] = $this->config->get('cleverreach_client_secret');
        }

        $data['cleverreach_lines'] = $this->model_cleverreach_cleverreach->getLines();

        $data['action'] = $this->url->link('cleverreach/cleverreach', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('cleverreach/cleverreach', $data));
    }

    public function upsert()
    {
        $this->load->language('cleverreach/upsert_form');
        $this->load->model('cleverreach/cleverreach');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' &&
            isset($this->request->post['group']) &&
            isset($this->request->post['customer']) &&
            is_array($this->request->post['customer'])) {

            $this->model_cleverreach_cleverreach->upsertReceivers($this->request->post['group'], $this->request->post['customer']);
            $this->session->data['success'] = $this->language->get('text_success');
        }

        $this->response->redirect($this->url->link('customer/customer', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function upsertForm()
    {
        $this->load->language('cleverreach/upsert_form');
        $this->load->model('cleverreach/cleverreach');
        $data = $this->language->all();

        if ($this->config->has('cleverreach_token')) {
            $data['groups'] = $this->model_cleverreach_cleverreach->getGroups();
            $data['action'] = $this->url->link('cleverreach/cleverreach/upsert', 'user_token=' . $this->session->data['user_token'], true);

            $this->response->setOutput($this->load->view('cleverreach/upsert_form', $data));
        } else {
            http_response_code(400);
            $this->response->setOutput('missing cleverreach token');
        }
    }

    public function renderCustomerList(&$route, &$data, &$template)
    {
        return $this->load->view('cleverreach/customer_list', $data);
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'cleverreach/cleverreach')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}