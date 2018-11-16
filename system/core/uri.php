<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class URI
{
    public function __construct()
    {
        $main_config = include 'application/config/config.php';
        foreach ($main_config as $key => $value) {
            define($key, $value);
        }
    }

    public function segment($nomor)
    {

        $base_url = str_replace("http://", "", str_replace("https://", "", base_url));
        $current_url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $uri_base = explode('?', $current_url);
        $uri_base = str_replace($base_url, '', $uri_base[0]);
        $uri_link = explode('/', $uri_base);
        $uri_new = $uri_base;

        $ext = array(".html", ".aspx", ".asp");

        $data = explode('/', $uri_new);
        if ($nomor > count($data)) {
            return '';
        } else {
            return str_replace($ext, '', $data[$nomor - 1]);
        }

    }

    public function subsegment($from = -1, $to = 0)
    {
        $uri_base = explode('?', $_SERVER['REQUEST_URI']);
        $uri_link = explode('/', $uri_base[0]);
        $count = count($uri_link);
        $segment = '';
        if ($from < 0 || $from > $count) {
            $segment = $uri_link[$count - 1];
        } else if ($from == $to) {
            $segment = $this->segment($from);
        } else if ($count >= $to) {
            if ($to == 0) {
                $to = $count;
            }

            if ($to < 0) {
                $to = $count + $to;
            }

            for ($i = $from; $i < $to; $i++) {
                $segment .= $uri_link[$i] . '/';
            }
            $segment = substr($segment, 0, -1);
        }
        return $segment;
    }
}
