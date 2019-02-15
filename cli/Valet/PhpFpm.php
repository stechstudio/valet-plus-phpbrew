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
            info("Switching to $version");

            $this->stop();
            $this->link($installedVersion);

            $this->restart();
            info("Valet is now using PHP $version");

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
            ->map(function($version) {
                return trim(str_replace(['php-', '* '], '', $version));
            })
            ->filter(function($version) {
                return strpos($version, '5.') === 0 || strpos($version, '7.') === 0;
            });
    }

    /**
     * Pull the PHP version number out of the symlinked binary
     */
    function linkedPhp()
    {
        if (!$this->files->isLink('/usr/local/bin/php')) {
            return false;
        }

        $resolvedPath = $this->files->readLink('/usr/local/bin/php');
        preg_match("|/php-([0-9\.]*)|", $resolvedPath, $matches);

        return array_key_exists(1, $matches) ? $matches[1] : false;
    }

    /**
     * Switches to and symlinks a specific PhpBrew managed version. Note this requires the full
     * patch version you have installed (like 7.3.2).
     *
     * @param $version
     */
    function link($version)
    {
        $this->phpbrew("switch $version");

        $this->files->symlink($_SERVER['HOME'] . "/.phpbrew/php/php-$version/bin/php", '/usr/local/bin/php');
    }

    /**
     * Setup PHP/PHP-FPM
     */
    function install()
    {
        if (!$this->hasInstalledPhp()) {
            throw new \DomainException('Please install PHP with PhpBrew before continuing');
        }

        if(!$this->linkedPhp()) {
            $version = $this->installedVersions()->sort()->reverse()->first();
            info("[php] Linking $version");

            $this->stop();
            $this->link($version);
        }

        $this->files->ensureDirExists('/usr/local/var/log', user());
        $this->updateConfiguration();

        info('[php] Installing apcu');
        $this->phpbrew('ext install apcu stable');

        $this->restart();
    }

    function updateConfiguration()
    {
        //parent::updateConfiguration();
        $this->updateFpmConfig($this->fpmConfigPath());
        $this->updateFpmConfig($this->fpmPoolConfigPath());
        $this->updateTimezoneConfig();
    }

    function updateFpmConfig($path)
    {
        if(!$this->files->exists($path)) {
            return;
        }

        $contents = $this->files->get($path);

        $contents = preg_replace('/^listen = .+$/m', 'listen = ' . VALET_HOME_PATH . '/valet.sock', $contents);
        $contents = preg_replace('/^;?listen\.owner = .+$/m', 'listen.owner = ' . user(), $contents);
        $contents = preg_replace('/^;?listen\.group = .+$/m', 'listen.group = staff', $contents);
        $contents = preg_replace('/^;?listen\.mode = .+$/m', 'listen.mode = 0777', $contents);
        $contents = preg_replace('/^;?php_admin_value\[error_log\] = .+$/m',
            'php_admin_value[error_log] = ' . VALET_HOME_PATH . '/Log/php.log', $contents);
        $contents = preg_replace('/^;?error_log = .+$/m', 'error_log = ' . VALET_HOME_PATH . '/Log/php.log', $contents);

        $this->files->put($path, $contents);
    }

    function updateTimezoneConfig()
    {
        $systemZoneName = readlink('/etc/localtime');
        // All versions below High Sierra
        $systemZoneName = str_replace('/usr/share/zoneinfo/', '', $systemZoneName);
        // macOS High Sierra has a new location for the timezone info
        $systemZoneName = str_replace('/var/db/timezone/zoneinfo/', '', $systemZoneName);
        $contents = $this->files->get($this->stubsPath() . '/z-performance.ini');
        $contents = str_replace('TIMEZONE', $systemZoneName, $contents);

        $iniPath = $this->iniPath();
        $this->files->ensureDirExists($iniPath, user());
        $this->files->putAsUser($this->iniPath() . '/z-performance.ini', $contents);
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
        info('[phpfpm] Stopping');
        $this->phpbrew('fpm stop');
    }

    function restart()
    {
        info('[phpfpm] Restarting');
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
        return trim(str_replace('bin/php', 'var/db', $this->cli->runAsUser('which php')));
    }

    function stubsPath()
    {
        return trim(str_replace('bin/valet', 'weprovide/valet-plus/cli/stubs', $this->cli->runAsUser('which valet')));
    }

    function phpbrew($command)
    {
        return $this->cli->runAsUser(__DIR__ . "/../../bin/phpbrew $command");
    }
}
