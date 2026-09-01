<?php

if (!empty($_GET['ADISDB']) && !empty($_GET['ADISOI'])) {

    $db = (string) preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $_GET['ADISDB']));
    $oi = (string) preg_replace('/[^0-9]/', '', (string) $_GET['ADISOI']);

    if ($db === '' || $oi === '') {
        return;
    }

    $db = rawurlencode($db);
    $oi = rawurlencode(str_pad($oi, 8, '0', STR_PAD_LEFT));

    $url = '/find/opac/id/' . $db . $oi;

    header('Location: ' . $url);

}
