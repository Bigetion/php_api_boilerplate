<?php
error_reporting(-1);
ini_set('memory_limit', '128M');
define('INDEX', '');
function get_time()
{
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    return $time;
}
$start_time = get_time();
define('start_time', $start_time);
function load_recursive($nama_folder, $level = 0, $jenis_file = array('php'))
{
    $data = array();
    foreach (ListIn($nama_folder, $nama_folder . '/') as $value) {
        $exts = explode('.', $value);
        $ext = $exts[count($exts) - 1];
        if (in_array($ext, $jenis_file)) {
            $data[] = $value;
        }
    }
    if ($level != 0) {
        $val = array();
        foreach ($data as $value) {
            $a = explode('/', $value);
            $val[] = $a[$level];
        }
        $data = $val;
    }
    return $data;
}
function ListIn($dir, $prefix = '')
{
    if (!file_exists($dir)) {
        show_error('Dir not exist', $dir . ' not exist');
        exit;
    } else {
        $dir = rtrim($dir, '\\/');
        $result = array();
        $h = opendir($dir);
        while (($f = readdir($h)) !== false) {
            if ($f !== '.' and $f !== '..') {
                if (is_dir("$dir/$f")) {
                    $result = array_merge($result, ListIn("$dir/$f", "$prefix$f/"));
                } else {
                    $result[] = $prefix . $f;
                }
            }
        }
        closedir($h);
        return $result;
    }
}
function load_file($nama_folder)
{
    $namafile = array();
    $i = 0;
    if ($nama_folder != "index.html") {
        if ($handle = opendir($nama_folder)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    $namafile[$i] = $entry;
                }

                $i++;
            }
            closedir($handle);
        }
    }
    rsort($namafile);
    return $namafile;
}
function is_loaded($class = '')
{
    static $_is_loaded = array();
    if ($class != '') {
        $_is_loaded[strtolower($class)] = $class;
    }
    return $_is_loaded;
}
function &load_class($class)
{
    static $_classes = array();
    if (isset($_classes[$class])) {
        return $_classes[$class];
    }
    is_loaded($class);
    if (class_exists($class)) {
        $_classes[$class] = new $class();
    } else {
        show_error();
        exit;
    }
    return $_classes[$class];
}
foreach (load_recursive('system') as $value) {
    require_once $value;
}

require_once 'vendor/autoload.php';

$project = strtolower(str_replace('-', '_', segment(1)));
$controller = strtolower(str_replace('-', '_', segment(2)));
$method = strtolower(str_replace('-', '_', segment(3)));

$CONFIG = &load_class('Project');
session_start();
ob_start();

$CONFIG->set_project($project)->set_controller($controller)->set_method($method)->render();
