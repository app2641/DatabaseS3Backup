#! /usr/bin/env php
<?php

require_once dirname(__FILE__).'/library/composer/vendor/autoload.php';
require_once dirname(__FILE__).'/library/Backup.php';

set_time_limit(0);
defined('ROOT') || define('ROOT', realpath(dirname(__FILE__)));

$backup = new Backup();
$backup->execute();

