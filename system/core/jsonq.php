<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class JsonQ
{
    private $jsonq;

    private function get_data_by_where($jsonq, $where = array())
    {
        $currentWhere = '';
        $limit = -1;
        $nextCondition = '';

        $jsonq->macro('search', function ($data, $condition) {
            $jsonq = new \Nahid\JsonQ\Jsonq();
            $jsonq->collect($data);
            $result = $this->get_data_by_where($jsonq, $condition);
            return count($result) > 0;
        });

        $jsonq->macro('~', function ($text, $val) {
            return stripos($text, $val) !== false;
        });

        $jsonq->macro('inarray', function ($data, $val) {
            return in_array($val, $data, true);
        });

        $jsonq->macro('inarraycontains', function ($data, $val) {
            foreach ($data as $item) {
                if (stripos($val, $item) !== false) {
                    return true;
                }

            }
            return false;
        });

        if (is_array($where)) {
            foreach ($where as $val) {
                $next = 'and';
                if (isset($val['next'])) {
                    $next = $val['next'];
                }

                if (isset($val['limit'])) {
                    $limit = $val['limit'];
                }
                if (isset($val['sortBy'])) {
                    $currentWhere = $jsonq->sortBy($val['sortBy'][0], $val['sortBy'][1]);
                }
                if (isset($val['condition'])) {
                    if ($currentWhere == '') {
                        $currentWhere = $jsonq->where($val['condition'][0], $val['condition'][1], $val['condition'][2]);
                        $nextCondition = $next;
                    } else {
                        switch ($nextCondition) {
                            case 'and':
                                $currentWhere = $currentWhere->where($val['condition'][0], $val['condition'][1], $val['condition'][2]);
                                $nextCondition = $next;
                                break;
                            case 'or':
                                $currentWhere = $currentWhere->orWhere($val['condition'][0], $val['condition'][1], $val['condition'][2]);
                                $nextCondition = $next;
                                break;
                            default:
                                $currentWhere = $currentWhere->where($val['condition'][0], $val['condition'][1], $val['condition'][2]);
                                $nextCondition = $next;
                        }
                    }
                }
            }
        }
        if ($currentWhere == '') {
            $currentWhere = $this->jsonq;
        }
        $data = $currentWhere->get();
        if ($limit != -1) {
            if ($limit[0] > 0) {
                $data = array_slice($data, $limit[0]);
            }

            if ($limit[1] > 0) {
                $data = array_slice($data, 0, $limit[1]);
            }

        }
        $returnData = array();
        foreach ($data as $val) {
            $returnData[] = $val;
        }
        return $returnData;
    }

    public function search($data = array(), $columns = array(), $where = array())
    {
        $this->jsonq = new \Nahid\JsonQ\Jsonq();
        $this->jsonq->collect($data);
        $data = $this->get_data_by_where($this->jsonq, $where);

        $result = $data;
        if (count($columns) > 0) {
            $result = array();
            foreach ($data as $item) {
                $item_tmp = array();
                foreach ($columns as $column) {
                    $explode_column = explode(' as ', $column);
                    if (count($explode_column) > 1) {
                        $item_tmp[$explode_column[1]] = $item[$explode_column[0]];
                    } else {
                        $item_tmp[$column] = $item[$column];
                    }
                }
                $result[] = $item_tmp;
            }
        }

        return $result;
    }

}
