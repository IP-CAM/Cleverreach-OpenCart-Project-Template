<?php

class ControllerCleverreachCleverreach extends Controller
{

    public function addCustomer()
    {
        $this->log->write(func_get_args());
    }

}
