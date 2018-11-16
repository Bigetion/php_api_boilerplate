<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class Home extends Controller
{

    public function index()
    {
        $this->render->json(array("success" => true));
    }
}
