<?php

// INSTANTIATE
$settings = require 'settings.php';
$app = new Slim\App($settings);

// DEPENDENCIES
require 'dependencies.php';

// ROUTES
require 'routes.php';

$app->run();