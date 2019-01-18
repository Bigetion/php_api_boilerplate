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
    public function getUserInfo()
    {
        $data = array(
            'idRole' => id_role,
            'idUser' => id_user,
        );
        $this->render->json($data);
    }
    
    public function getJSONFromAPI() 
    {
        $post_data = $this->render->json_post();
        $result = array();

        if (isset($post_data['url'])) {
            $url = $post_data['url'];
            $this->crawl->set_url($url);
            $p = $this->crawl->get_data([], [[
                "condition" => ["tag", "=", "p"],
            ]]);
            $result = json_decode($p[0]['innerHTML']);
        }
        $this->render->json($result);
    }
}
