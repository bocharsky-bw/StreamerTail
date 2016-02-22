#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use StreamerTail\Console\Command\TailCommand;

$app = new Application();
$app->add(new TailCommand());
$app->run();
