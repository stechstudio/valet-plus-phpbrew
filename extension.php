<?php
require __DIR__ . '/../../autoload.php';

use Illuminate\Container\Container;

Container::getInstance()->singleton('Valet\Brew', ValetPhpBrew\Brew::class);
Container::getInstance()->singleton('Valet\PhpFpm', ValetPhpBrew\PhpFpm::class);