<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class Project
{
    public $project = '';
    public $controller = '';
    public $method = '';

    public $is_project = true;

    public $jwt_payload = false;

    public function origin_authenticate($allowed_origin = array())
    {
        $headers = getallheaders();

        if (array_key_exists("Origin", $headers)) {
            if (is_string($allowed_origin)) {
                if ($allowed_origin != "*") {
                    if ($headers["Origin"] != $allowed_origin) {
                        show_error('Permission', 'Origin unauthorized');
                    }
                }
            } else if (is_array($allowed_origin)) {
                if (!in_array($headers["Origin"], $allowed_origin)) {
                    show_error('Permission', 'Origin unauthorized');
                }
            }
        }
    }

    public function params_validation($path)
    {
        if (file_exists($path)) {
            $json = json_decode(file_get_contents($path), true);
            $gump = &load_class('GUMP');
            if (isset($json[$this->method])) {
                if (isset($json[$this->method]['validation']) || isset($json[$this->method]['filter'])) {
                    $post_data = json_decode(file_get_contents('php://input'), true);
                    if (isset($json[$this->method]['validation'])) {
                        $gump->validation_rules($json[$this->method]['validation']);
                    }
                    if (isset($json[$this->method]['filter'])) {
                        $gump->filter_rules($json[$this->method]['filter']);
                        $post_data = $gump->filter($post_data, $json[$this->method]['filter']);
                    }
                    $_POST = $post_data;
                    $gump->run_validation($post_data);
                }
            }
        }
    }

    public function is_exist_project($project)
    {
        if (!in_array($project, load_file('project'))) {
            return false;
        } else {
            return true;
        }

    }

    public function is_exist_app_controller($controller)
    {
        if (!file_exists('application/controllers/' . $controller . '.php')) {
            return false;
        } else {
            return true;
        }

    }

    public function is_exist_project_controller($project, $controller)
    {
        if (!file_exists('project/' . $project . '/controllers/' . $controller . '.php')) {
            return false;
        } else {
            return true;
        }

    }

    public function set_project($project)
    {
        $this->project = $project;
        return $this;
    }

    public function set_controller($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    public function set_method($method)
    {
        $this->method = $method;
        return $this;
    }

    public function render()
    {
        if (empty($this->project) && empty($this->controller)) {
            if (!$this->is_exist_project(default_project)) {
                show_error('Page not found', 'Project ' . default_project . ' was not found');
            }

            if ($this->is_exist_project_controller(default_project, default_project_controller)) {
                require_once 'project/' . default_project . '/controllers/' . default_project_controller . '.php';
            } else {
                show_error('Page not found', 'Main project controller ' . default_project_controller . ' was not found');
            }

            $this->project = default_project;
            $this->controller = default_project_controller;
            $this->method = default_project_method;
        } else {
            if ($this->is_exist_app_controller($this->project)) {
                $this->is_project = false;
                require_once 'application/controllers/' . $this->project . '.php';

                if (empty($this->controller) && empty($this->method)) {
                    $this->controller = $this->project;
                    $this->method = default_app_method;
                } else {
                    $this->method = $this->controller;
                    $this->controller = $this->project;
                }
            } elseif ($this->is_exist_project($this->project)) {

                if ($this->is_exist_project_controller(default_project, default_project_controller)) {
                    require_once 'project/' . default_project . '/controllers/' . default_project_controller . '.php';
                } else {
                    show_error('Page not found', 'Main project controller ' . default_project_controller . ' was not found');
                }

                foreach (load_recursive('project/' . $this->project . '/config') as $value) {
                    $project_config = include $value;
                    foreach ($project_config as $key => $value) {
                        define($key, $value);
                    }
                }

                if (empty($this->controller) && empty($this->method)) {
                    if (defined('main_controller')) {
                        $this->controller = main_controller;
                        if (defined('default_method')) {
                            $this->method = default_method;
                        }

                    }
                } else if (empty($this->method)) {
                    $this->method = "index";
                }

                if ($this->is_exist_project_controller($this->project, $this->controller)) {
                    require_once 'project/' . $this->project . '/controllers/' . $this->controller . '.php';
                } else {
                    show_error('Page not found', 'Controller ' . $this->controller . ' was not found');
                }

            } else {
                show_error('Page not found', 'Project ' . $this->project . ' was not found');
            }

        }

        define('base_url_project', base_url . $this->project . '/');
        $this->_render();
    }

    public function _render()
    {
        if (defined('allowed_origin')) {
            $this->origin_authenticate(allowed_origin);
        } else {
            $this->origin_authenticate('-');
        }
        $controller = $this->controller;
        $method = $this->method;

        $base_directory = array('application', 'project', 'system');

        if (in_array($controller, $base_directory)) {
            show_error('Permission', 'You dont have permission to access this page');
        }

        $Render = &load_class($controller);
        if (method_exists($controller, $method)) {
            if ($this->is_project) {
                if (method_exists(default_project_controller, "__global")) {
                    $Render->load->url(default_project . "/" . default_project_controller . "/__global");
                }
                $this->params_validation('project/' . $this->project . '/params/' . $controller . '.json');
            } else {
                $this->params_validation('application/params/' . $controller . '.json');
            }
            $header_with_payload = get_header('Access-Control-Request-Method');
            if (!$header_with_payload) {
                $Render->$method();
            }
        } else {
            show_error('Page not found', 'Controller ' . $controller . ' with function ' . $method . ' was not found');
        }

    }
}
