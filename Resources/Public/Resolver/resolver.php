<?php

if (!empty($_GET['ADISDB']) && !empty($_GET['ADISOI'])) {

    $db = rawurlencode(strtoupper((string) $_GET['ADISDB']));
    $oi = rawurlencode(str_pad((string) $_GET['ADISOI'], 8, '0', STR_PAD_LEFT));

    $url = '/find/opac/id/' . $db . $oi;

    header('Location: ' . $url);

}
