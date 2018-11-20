<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

require_once 'jsonq.php';

class Crawl
{
    private $html;
    private $tags;
    private $html_object;
    private $jsonq;

    private function element_to_object($element, $level)
    {
        $explode_level = explode("_", $level);
        $obj = array("level" => $level, "depth" => count($explode_level));
        if (property_exists($element, 'tagName')) {
            $obj["tag"] = $element->tagName;
            foreach ($element->attributes as $attribute) {
                $obj[$attribute->name] = $attribute->value;
            }
            $i = 0;
            $innerHTML = "";
            foreach ($element->childNodes as $subElement) {
                $innerHTML .= $element->ownerDocument->saveHTML($subElement);
                if ($subElement->nodeType == XML_TEXT_NODE) {
                    $obj["html"] = $subElement->wholeText;
                } else {
                    $obj["children"][] = $this->element_to_object($subElement, $level . '_' . $i);
                }
                $i += 1;
            }
            if($level != "0") $obj["innerHTML"] = $innerHTML;
        }
        $this->tags[] = $obj;
        return $obj;
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
        $jsonq = &load_class('JsonQ');
        return $jsonq->search($this->tags, $columns, $where);
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
