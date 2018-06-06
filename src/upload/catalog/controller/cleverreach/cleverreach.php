<?php

class ControllerCleverreachCleverreach extends Controller
{
    public function addCustomer(&$route, &$args, &$output)
    {
        $this->load->model('account/customer');
        $this->load->model('cleverreach/cleverreach');

        if ($output) {
            $customer = $this->model_account_customer->getCustomer($output);

            if ($customer && $customer['newsletter']) {
                $this->model_cleverreach_cleverreach->addReceiver($customer['email'], [
                    'firstname' => $customer['firstname'] ,
                    'lastname'  => $customer['lastname']
                ]);
            }
        }
    }

    public function editCustomer(&$route, &$args, &$output)
    {
        $this->load->model('cleverreach/cleverreach');

        $newsletter = $args[0];
        $email = $this->customer->getEmail();

        if ($newsletter) {
            $this->model_cleverreach_cleverreach->activateReceiver($email);
        } else {
            $this->model_cleverreach_cleverreach->deactivateReceiver($email);
        }
    }

}
