<?php

namespace Dla\DlaOpacNg\Ajax;

$host = getenv('SOLR_HOST');
$core = getenv('SOLR_CORE');

$PLUGIN_PATH = '/find/opac/id/';

$DOMAIN = 'https://www.dla-marbach.de/';
$ABSOLUTE_DETAIL_URL = $DOMAIN . 'find/opac/id/';

$SITEMAP_DIR = '/server/data/www/apache/dla-www-prod-v10/www/fileadmin/';
$SITEMAP_URL = $DOMAIN . 'fileadmin/';