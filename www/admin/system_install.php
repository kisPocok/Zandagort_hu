<?php

include('../csatlak.php');
if (!isset($argv[1]) or $argv[1]!=$zanda_private_key) exit;
set_time_limit(0);

define('PUBLIC_DIR', dirname(__FILE__) . '/../../');

function makeDir($path, $mode)
{
	mkdir($path, $mode, true);
	chmod($path, $mode);
}

# Log mappa
makeDir(PUBLIC_DIR . '/../log', 0777);

# Képek
$directories = array(
    'www/img/cimerek',
    'www/img/minicimerek',
    'www/img/okoszim',
    'www/img/user_avatarok',
    'www/img/user_nagy_avatarok'
);
foreach ($directories as $dir) {
	$dir = PUBLIC_DIR . $dir;
	makeDir($dir, 0733);
}

exit("kesz\n");