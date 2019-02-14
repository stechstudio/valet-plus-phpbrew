<?php
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
} else {
    require __DIR__.'/../../autoload.php';
}

use Illuminate\Container\Container;

Container::getInstance()->singleton('Valet\PhpFpm', ValetPhpBrew\PhpFpm::class);

$app->setName($app->getName() . " with PhpBrew");