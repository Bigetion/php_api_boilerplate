<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class Main
{
    public $c = array();

    public function __get($key)
    {
        if (!array_key_exists($key, $this->c)) {
            $this->c[$key] = new $key();
            return $this->c[$key];
        } else {
            return $this->c[$key];
        }
    }

}

function &get_instance()
{
    $a = new Main;
    return $a;
}
