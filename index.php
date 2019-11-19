<?php

namespace alice;

include_once('./env.php');
$config = include_once('./config.php');

include_once('./main.php');

$run = new main($config);
$run->run();
