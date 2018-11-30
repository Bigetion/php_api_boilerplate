<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class Scrap extends Controller
{

    public function index()
    {
        $url = $_GET['url'];
        $is_rendered = $_GET['is_rendered'];

        $this->crawl->set_url($url, $is_rendered);
        $data = $this->crawl->get_html_object();
        $this->render->json($data);
    }

    public function getData()
    {
        $post_data = $this->render->json_post();
        $name = $post_data['name'];
        $json_data = null;
        if (file_exists('project/base/config/scrap-service/' . id_role . '/' . $name . '.json')) {
            $json_data = json_decode(file_get_contents('project/base/config/scrap-service/' . id_role . '/' . $name . '.json'), true);
        } else if (file_exists('project/base/config/scrap-service/' . $name . '.json')) {
            $json_data = json_decode(file_get_contents('project/base/config/scrap-service/' . $name . '.json'), true);
        }
        $data = array('data' => array());

        if (!empty($json_data)) {
            if (isset($json_data['url']) && isset($json_data['query'])) {
                $url = $json_data['url'];
                $query = $json_data['query'];
                $is_rendered = $json_data['is_rendered'];
                $array_keys = array_keys($query);
                if ($array_keys[0] === 0) {
                    foreach ($query as $row) {
                        if (isset($row['columns']) && isset($row['where'])) {
                            $columns = $row['columns'];
                            $where = $row['where'];

                            $this->crawl->set_url($url, $is_rendered);
                            $data['data'][] = $this->crawl->get_data($columns, $where);
                        }
                    }
                } else {
                    if (isset($query['columns']) && isset($query['where'])) {
                        $columns = $query['columns'];
                        $where = $query['where'];

                        $this->crawl->set_url($url, $is_rendered);
                        $data['data'] = $this->crawl->get_data($columns, $where);
                    }
                }
            }
        }
        $this->render->json($data);
    }
}
