<?php
function segment($nomor)
{
    $uri = &load_class('URI');
    return $uri->segment($nomor);
}

function subsegment($from = -1, $to = 0)
{
    $uri = &load_class('URI');
    return $uri->subsegment($from, $to);
}
