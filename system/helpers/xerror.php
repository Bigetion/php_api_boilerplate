<?php
function show_error($header = 'Page Not Found', $message = 'The page your requested was not found')
{
    $data['error_message'] = $header . ' : ' . $message;
    if (strtolower($header) == 'authentication') {
        $data['require_login'] = true;
    }

    $header_with_payload = get_header('Access-Control-Request-Method');
    header('Content-Type: application/json');
    if (!$header_with_payload) {
        echo json_encode($data);
    }
    exit();
}
