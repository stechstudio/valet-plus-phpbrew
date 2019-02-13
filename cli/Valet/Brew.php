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

    /**
     * Determine if the given formula is installed.
     *
     * @param  string $formula
     *
     * @return bool
     */
//    function installed($formula)
//    {
//        if (strstr($formula, "php")) {
//            return $this->hasInstalledPhp();
//        }
//
//        return parent::installed($formula);
//    }
//
//    function installOrFail($formula, $options = [], $taps = [])
//    {
//        if (strstr($formula, "php")) {
//            throw new \Valet\DomainException('Please install PHP with phpbrew');
//        }
//
//        return parent::installOrFail($formula, $options, $taps);
//    }
//
//    function hasLinkedPhp()
//    {
//        return (bool)strstr($this->cli->runAsUser('which php'), '.phpbrew');
//    }
//
//    function getLinkedPhpFormula()
//    {
//        return basename(dirname(dirname(trim($this->cli->runAsUser('which php')))));
//    }
//
//    function linkedPhp()
//    {
//        return $this->getLinkedPhpFormula();
//    }
//
//    function link($formula, $force = false)
//    {
//        if (strstr($formula, "php")) {
//            die("trying to link $formula");
//        }
//
//        return parent::link($formula, $force);
//    }
}
