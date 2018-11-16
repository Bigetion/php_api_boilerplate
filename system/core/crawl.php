<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class Crawl
{
    private $html;
    private $tags;
    private $html_object;
    private $jsonq;

    private function element_to_object($element, $level)
    {
        $obj = array("level" => $level);
        if (property_exists($element, 'tagName')) {
            $obj["tag"] = $element->tagName;
            foreach ($element->attributes as $attribute) {
                $obj[$attribute->name] = $attribute->value;
            }
            $i = 0;
            foreach ($element->childNodes as $subElement) {
                if ($subElement->nodeType == XML_TEXT_NODE) {
                    $obj["html"] = $subElement->wholeText;
                } else {
                    $obj["children"][] = $this->element_to_object($subElement, $level . '_' . $i);
                }
                $i += 1;
            }
        }
        $this->tags[] = $obj;
        return $obj;
    }

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

    public function set_url($url, $rendered = false)
    {
        if ($rendered == true) {
            $html = file_get_contents('https://render-tron.appspot.com/render/' . rawurlencode($url));
        } else {
            $html = file_get_contents($url);
        }
        $this->html = $html;
        $this->tags = [];
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);
        $this->html_object = $this->element_to_object($dom->documentElement, "0");
    }

    public function set_tags($tags)
    {
        $this->tags = $tags;
    }

    public function get_html()
    {
        return $this->html;
    }

    public function get_tags()
    {
        return $this->tags;
    }

    public function get_html_object()
    {
        return $this->html_object;
    }

    public function get_data($columns = array(), $where = array())
    {
        $this->jsonq = new \Nahid\JsonQ\Jsonq();
        $this->jsonq->collect($this->tags);
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

    public function get_string_between($start, $end)
    {
        $r = explode($start, $this->html);
        if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        }
        return '';
    }
}
