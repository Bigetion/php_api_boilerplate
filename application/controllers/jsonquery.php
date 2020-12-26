<?php

class JsonQuery extends Main
{
    public function index()
    {
        $post_data = $this->render->json_post();
        $data = array('data' => []);

        if (isset($post_data['data']) && isset($post_data['where'])) {
            $json = $post_data['data'];
            $where = $post_data['where'];
            $colums = array();
            if (isset($post_data['columns'])) {
                $colums = $post_data['columns'];
            }
            $data['data'] = $this->jsonq->search($json, $colums, $where);
        }

        $this->render->json($data);
    }
}
