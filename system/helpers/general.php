<?php
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

function get_header($header_key)
{
    $headers = getallheaders();
    $header_key = strtolower($header_key);
    $new_headers = array();
    foreach ($headers as $key => $val) {
        $new_headers[strtolower($key)] = $val;
    }
    $headers = $new_headers;
    if ($header_key) {
        if (array_key_exists($header_key, $headers)) {
            $headers = $headers[$header_key];
        } else {
            $headers = false;
        }

    } else {
        $headers = false;
    }

    return $headers;
}

function get_base_dir($dir)
{
    $base_path = str_replace(getcwd(), '', $dir);
    return str_replace('\\', '/', substr($base_path, 1));
}
