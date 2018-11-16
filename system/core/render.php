<?php if (!defined('INDEX')) {
    exit('No direct script access allowed');
}

class Render
{
    public function json($data)
    {
        $header_with_payload = get_header('Access-Control-Request-Method');
        header('Content-Type: application/json');
        if (!$header_with_payload) {
            echo json_encode($data);
        }
    }

    public function image($data)
    {
        $header_with_payload = get_header('Access-Control-Request-Method');
        if (!$header_with_payload) {
            if (file_exists($data)) {
                $imageInfo = getimagesize($data);
                switch ($imageInfo[2]) {
                    case IMAGETYPE_JPEG:
                        header("Content-Type: image/jpeg");
                        break;
                    case IMAGETYPE_GIF:
                        header("Content-Type: image/gif");
                        break;
                    case IMAGETYPE_PNG:
                        header("Content-Type: image/png");
                        break;
                    default:
                        break;
                }
                header('Content-Length: ' . filesize($data));
                readfile($data);
                exit();
            }
        }
    }

    public function json_post()
    {
        return json_decode(file_get_contents('php://input'), true);
    }

}
