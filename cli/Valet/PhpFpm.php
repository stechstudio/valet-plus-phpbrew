<?php
namespace ValetPhpBrew;

class PhpFpm extends \Valet\PhpFpm
{
    /**
     * Switch between versions of installed PHP. Switch to the provided version.
     * You can provide a partial version number (list 7.3) and we'll use whatever
     * patch version (like 7.3.2) is installed.
     *
     * @param $version
     */
    function switchTo($version)
    {
        if(!strstr($version, '.')) {
            warning('Please provide a minor version number');
            return;
        }

//        if(strpos($this->linkedPhp(), $version) === 0) {
//            info('Already on this version');
//            return;
//        }

        if($installedVersion = $this->hasInstalledVersion($version)) {
            $this->link($installedVersion);
            return;
        }

        warning("PHP $version is not installed. You need to install with PhpBrew first. We suggest running:");
        echo "phpbrew install $version +default+fpm+dbs\n";

        warning("Remember to run `valet install` after installing a new version of PHP");
    }



    /**
     * @return bool
     */
    function hasInstalledPhp()
    {
        return $this->installedVersions()->count() > 0;
    }

    /**
     * This can accept a partial version number 5.6 and match against a more specific version 5.6.40
     *
     * @param $version
     *
     * @return string|null
     */
    function hasInstalledVersion($version)
    {
        return $this->installedVersions()
            ->first(function($installed) use($version) {
                return strpos($installed, $version) === 0;
            });
    }

    /**
     * Returns a collection of all PHP versions currently installed by PhpBrew
     *
     * @return \Tightenco\Collect\Support\Collection
     */
    function installedVersions()
    {
        $list = explode(PHP_EOL, trim($this->phpbrew('list')));

        return collect($list)
            ->filter(function($version) {
                return !strstr($version, "system");
            })
            ->map(function($version) {
                return trim(str_replace(['php-', '* '], '', $version));
            });
    }

    /**
     * Pull the PHP version number out of the symlinked binary
     */
    function linkedPhp()
    {
        if (!$this->files->isLink('/usr/local/bin/php')) {
            throw new \DomainException("Unable to determine linked PHP.");
        }

        $resolvedPath = $this->files->readLink('/usr/local/bin/php');
        preg_match("|/php-([0-9\.]*)|", $resolvedPath, $matches);

        if(array_key_exists(1, $matches)) {
            return $matches[1];
        }

        throw new \DomainException("Unable to determine linked PHP.");
    }

    /**
     * Switches to and symlinks a specific PhpBrew managed version. Note this requires the full
     * patch version you have installed (like 7.3.2).
     *
     * @param $version
     */
    function link($version)
    {
        info("Switching to $version");

        $this->stop();
        $this->phpbrew("switch $version");

        $this->files->symlink($_SERVER['HOME'] . "/.phpbrew/php/php-$version/bin/php", '/usr/local/bin/php');

        $this->restart();
        info("Valet is now using PHP $version");
    }

    function updateConfiguration()
    {
        parent::updateConfiguration();

        if(!$this->files->exists($this->fpmPoolConfigPath())) {
            return;
        }

        $contents = $this->files->get($this->fpmPoolConfigPath());

        $contents = preg_replace('/^user = .+$/m', 'user = ' . user(), $contents);
        $contents = preg_replace('/^group = .+$/m', 'group = staff', $contents);
        $contents = preg_replace('/^listen = .+$/m', 'listen = ' . VALET_HOME_PATH . '/valet.sock', $contents);
        $contents = preg_replace('/^;?listen\.owner = .+$/m', 'listen.owner = ' . user(), $contents);
        $contents = preg_replace('/^;?listen\.group = .+$/m', 'listen.group = staff', $contents);
        $contents = preg_replace('/^;?listen\.mode = .+$/m', 'listen.mode = 0777', $contents);
        $contents = preg_replace('/^;?php_admin_value\[error_log\] = .+$/m',
            'php_admin_value[error_log] = ' . VALET_HOME_PATH . '/Log/php.log', $contents);
        $this->files->put($this->fpmPoolConfigPath(), $contents);
    }

    /**
     * This checks things that we don't care about
     */
    function checkInstallation()
    {
        return;
    }

    function fix($reinstall)
    {
        warning("Use PhpBrew to install or fix your PHP installation");
    }

    function stop()
    {
        $this->phpbrew('fpm stop');
    }

    function restart()
    {
        $this->phpbrew('fpm restart');
    }

    function fpmConfigPath()
    {
        return trim(str_replace('bin/php', 'etc/php-fpm.conf', $this->cli->runAsUser('which php')));
    }

    function fpmPoolConfigPath()
    {
        return trim(str_replace('bin/php', 'etc/php-fpm.d/www.conf', $this->cli->runAsUser('which php')));
    }

    function iniPath()
    {
        return trim(str_replace('var/db', 'etc/php-fpm.conf', $this->cli->runAsUser('which php')));
    }

    function phpbrew($command)
    {
        return $this->cli->runAsUser(__DIR__ . "../../bin/phpbrew $command");
    }
}
