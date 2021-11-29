<?php

if (!empty($_GET['ADISDB']) && !empty($_GET['ADISOI'])) {

    $db = strtoupper($_GET['ADISDB']);
    $oi = str_pad($_GET['ADISOI'], 8, '0', STR_PAD_LEFT);

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $protocol = 'https://';
    } else {
        $protocol = 'http://';
    }
    $url = $protocol . $_SERVER['HTTP_HOST'] . '/find/opac/id/' . $db . $oi;

    header('Location: ' . $url);

}
