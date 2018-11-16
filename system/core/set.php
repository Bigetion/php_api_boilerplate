<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class Set
{

    public $data = array();
    public $error_message = array();

    public function message_error($type, $message)
    {
        $this->error_message[$type] = $message;
    }

    public function post($nama_var, $nama_value = null)
    {
        if (empty($nama_value)) {
            $this->data[$nama_var] = $_POST[$nama_var];
        } else {
            $this->data[$nama_value] = $_POST[$nama_var];
        }

    }

    public function validation($nama_var, $type = '', $message = '')
    {
        $arr = explode('|', $type);
        $error = false;
        krsort($arr);
        foreach ($arr as $val) {
            switch ($val):
        case 'required':
            if (empty($_POST[$nama_var])) {
                    if (array_key_exists("required", $this->error_message)) {
                        $this->data['error'][$nama_var] = $this->error_message["required"];
                    } else {
                        $this->data['error'][$nama_var] = 'harus diisi.!';
                    }

                    $error = true;
            }
            break;
        case 'numeric':
            if (!empty($_POST[$nama_var]) && !is_numeric($_POST[$nama_var])) {
                if (array_key_exists("numeric", $this->error_message)) {
                    $this->data['error'][$nama_var] = $this->error_message["numeric"];
                } else {
                    $this->data['error'][$nama_var] = 'hanya boleh angka.!';
                }

                $error = true;
            }
            break;
        case 'alpha':
            if (!empty($_POST[$nama_var]) && !preg_match('/^[A-Za-z\s ]+$/', $_POST[$nama_var])) {
                if (array_key_exists("alpha", $this->error_message)) {
                    $this->data['error'][$nama_var] = $this->error_message["alpha"];
                } else {
                    $this->data['error'][$nama_var] = 'hanya boleh huruf.!';
                }

                $error = true;
            }
            break;
        case 'alphanumeric':
            if (!empty($_POST[$nama_var]) && !preg_replace('/[^a-zA-Z0-9]*$/', '', $_POST[$nama_var])) {
                if (array_key_exists("alphanumeric", $this->error_message)) {
                    $this->data['error'][$nama_var] = $this->error_message["alphanumeric"];
                } else {
                    $this->data['error'][$nama_var] = 'hanya boleh angka dan huruf.!';
                }

                $error = true;
            }
            break;
        case 'email':
            if (!empty($_POST[$nama_var]) && !preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i', $_POST[$nama_var])) {
                if (array_key_exists("email", $this->error_message)) {
                    $this->data['error'][$nama_var] = $this->error_message["email"];
                } else {
                    $this->data['error'][$nama_var] = 'alamat email tidak valid.!';
                }

                $error = true;
            }
            endswitch;

            if ($error == true && $message != '') {
                $this->data['error'][$nama_var] = $message;
            }
        }
    }

    public function image_validation($name, $config = array())
    {
        $msg = "";
        $allowed_type = array('image/jpg', 'image/jpeg', 'image/png', 'img/gif');
        $max_size = 2097152; // 2M
        $fix_width = 0;
        $fix_height = 0;
        $min_width = 220;
        $min_height = 220;

        if (array_key_exists("allowed_type", $config)) {
            $allowed_type = $config["allowed_type"];
        }

        if (array_key_exists("max_size", $config)) {
            $max_size = $config["max_size"];
        }

        if (array_key_exists("fix_width", $config)) {
            $fix_width = $config["fix_width"];
        }

        if (array_key_exists("fix_height", $config)) {
            $fix_height = $config["fix_height"];
        }

        if (array_key_exists("min_width", $config)) {
            $min_width = $config["min_width"];
        }

        if (array_key_exists("min_height", $config)) {
            $min_height = $config["min_height"];
        }

        $tmp_name = $_FILES[$name]['tmp_name'];

        if (!empty($tmp_name)) {
            $image = $_FILES[$name]["name"];
            $path_info = pathinfo($image);
            if (in_array($_FILES[$name]['type'], $allowed_type)) {
                if ($_FILES[$name]["size"] <= $max_size) {
                    $image_d = getimagesize($tmp_name);
                    if ($fix_width == 0 && $fix_height == 0) {
                        if (($image_d[0] >= $min_width) && ($image_d[1] >= $min_height)) {
                        } else {
                            $msg = "invalid dimension a";
                        }
                    } elseif ($fix_width > 0 && $fix_height > 0) {
                        if (($image_d[0] == $fix_width) && ($image_d[1] == $fix_height)) {
                        } else {
                            $msg = "invalid dimension b";
                        }
                    }
                } else {
                    $msg = "invalid file size";
                }
            } else {
                $msg = "format not supported";
            }

        } else {
            $msg = 'Please select at least one image.';
        }

        return $msg;
    }

    public function file_validation($name, $config = array())
    {
        $msg = "";
        $allowed_type = array('application/pdf');
        $max_size = 2097152; // 2M

        if (array_key_exists("allowed_type", $config)) {
            $allowed_type = $config["allowed_type"];
        }

        if (array_key_exists("max_size", $config)) {
            $max_size = $config["max_size"];
        }

        $tmp_name = $_FILES[$name]['tmp_name'];

        if (!empty($tmp_name)) {
            $image = $_FILES[$name]["name"];
            $path_info = pathinfo($image);
            if (in_array($_FILES[$name]['type'], $allowed_type)) {
                if ($_FILES[$name]["size"] <= $max_size) {
                } else {
                    $msg = "invalid file size";
                }
            } else {
                $msg = "format not supported";
            }

        } else {
            $msg = 'Please select at least one file.';
        }

        return $msg;
    }

    public function error($nama_var, $pesan_error)
    {
        if (!isset($this->data['error'][$nama_var])) {
            $this->data['error'][$nama_var] = $pesan_error;
        }

    }

    public function error_message($message, $data = array())
    {
        $data['error_message'] = $message;

        $header_with_payload = get_header('Access-Control-Request-Method');
        header('Content-Type: application/json');
        if (!$header_with_payload) {
            echo json_encode($data);
        }
        exit();
    }

    public function success_message($message, $data = array())
    {
        $data['success_message'] = $message;

        $header_with_payload = get_header('Access-Control-Request-Method');
        header('Content-Type: application/json');
        if (!$header_with_payload) {
            echo json_encode($data);
        }
        exit();
    }
}
