<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$path = str_replace('\\', '/', getcwd());
$base_path = str_replace($root, '', $path);
return array(
    'base_url' => 'http://' . $_SERVER['HTTP_HOST'] . $base_path . '/',
    'default_app_method' => 'index',
    'default_project' => 'page',
    'default_project_controller' => 'home',
    'default_project_method' => 'index',
    'secret_key' => '123456789',
    'allowed_origin' => '*',
)
?>
