<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class sleek extends Controller
{
    public function getData()
    {
        $post_data = $this->render->json_post();
        $name = $post_data['name'];
        $json_data = null;
        if (file_exists('project/base/config/sleek-query/' . id_role . '/' . $name . '.json')) {
            $json_data = json_decode(file_get_contents('project/base/config/sleek-query/' . id_role . '/' . $name . '.json'), true);
        } else if (file_exists('project/base/config/sleek-query/' . $name . '.json')) {
            $json_data = json_decode(file_get_contents('project/base/config/sleek-query/' . $name . '.json'), true);
        }
        $data = array('data' => array());

        if (!empty($json_data)) {
            if (isset($json_data['query'])) {
                $query = $json_data['query'];
                foreach ($query as $key => $q) {
                    $store = $q['store'];
                    $keys = $q['keys'];
                    $where = array();

                    $tmpData = $this->sleekdb->select($store, $keys, $where);
                    $tmpTotalRows = $this->sleekdb->totalRows();

                    if (isset($post_data['options'])) {
                        if (isset($post_data['options'][$key])) {
                            $options = $post_data['options'][$key];
                            if (isset($options['where'])) {
                                $where = $options['where'];
                            }
                            $tmpData = $this->sleekdb->select($store, $keys, $where);
                            $tmpTotalRows = $this->sleekdb->totalRows();
                            if (isset($options['limit'])) {
                                $limit = $options['limit'];
                                $where[] = ["limit" => $limit];
                            }
                            if (isset($options['sortBy'])) {
                                $sortBy = $options['sortBy'];
                                $where[] = ["sortBy" => $sortBy];
                            }
                            $tmpData = $this->sleekdb->select($store, $keys, $where);
                        };
                    }
                    $data['data'][] = array("rows" => $tmpData, "total" => $tmpTotalRows);
                }
            }
        }
        $this->render->json($data);
    }

    public function executeMutation()
    {
        $post_data = $this->render->json_post();
        $name = $post_data['name'];
        $type = $post_data['type'];

        $json_data = null;
        if (file_exists('project/base/config/sleek-mutation/' . $name . '.json')) {
            $json_data = json_decode(file_get_contents('project/base/config/sleek-mutation/' . $name . '.json'), true);
        } else {
            $this->set->error_message('Mutation options not found');
        }

        $store = $json_data['store'];

        function getInputData($my_data, $my_fields, $type)
        {
            $input_data = array();
            foreach ($my_fields as $field) {
                if (isset($my_data[$field['id']])) {
                    $input_data[$field['id']] = $my_data[$field['id']];
                    if (isset($field['type'])) {
                        if ($field['type'] == 'password') {
                            $input_data[$field['id']] = password_hash($input_data[$field['id']], 1);
                        }
                    }
                }
                if (($field['id'] == 'createdAt' && $type == 'insert') || ($field['id'] == 'updatedAt' && $type == 'update')) {
                    $input_data[$field['id']] = date('Y-m-d H:i:s');
                }
            }
            return $input_data;
        }

        $where = array();
        if ($type == 'insert') {
            if (in_array(id_role, $json_data['roles']['insert'])) {
                if (is_array($post_data['data'])) {
                    $array_keys = array_keys($post_data['data']);
                    if ($array_keys[0] === 0) {
                        foreach ($array_keys as $key) {
                            $input_data = getInputData($post_data['data'][$key], $json_data['fields'], $type);
                            $this->sleekdb->insert($store, $input_data);
                        }
                        $this->set->success_message(true);
                    } else {
                        $input_data = getInputData($post_data['data'], $json_data['fields'], $type);
                        if ($this->sleekdb->insert($store, $input_data)) {
                            $this->set->success_message(true);
                        }
                    }
                }
            }
        } else if ($type == 'update') {
            if (in_array(id_role, $json_data['roles']['update'])) {
                if (isset($post_data['data']) && isset($post_data['where'])) {
                    $input_data = getInputData($post_data['data'], $json_data['fields'], $type);
                    if ($this->sleekdb->update($store, $input_data, $post_data['where'])) {
                        $this->set->success_message(true);
                    }
                }
            }
        } else if ($type == 'delete') {
            if (in_array(id_role, $json_data['roles']['delete'])) {
                if (isset($post_data['where'])) {
                    if ($this->sleekdb->delete($store, $post_data['where'])) {
                        $this->set->success_message(true);
                    }
                }
            }
        }
        $this->set->error_message(true);
    }
}
