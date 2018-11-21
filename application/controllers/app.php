<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class App extends Controller
{
    public function getHeadersInfo()
    {
        $data['headers'] = getallheaders();

        $this->render->json($data);
    }
}
