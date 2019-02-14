<?php

namespace ValetPhpBrew;

class Brew extends \Valet\Brew
{
    function ensureInstalled($formula, $options = [], $taps = [])
    {
        if(strstr($formula, "php")) {
            throw new \DomainException('Please install PHP with PhpBrew before continuing');
        }
    }
}
