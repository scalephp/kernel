<?php namespace Scale\Kernel\Core;
/**
 * File finder
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */

trait Finder
{

    /**
     * 
     * @param  path   $path  Relative locations to search for files
     * @param  string $scope 'application', 'kli', or none for full scope
     * @return array
     */
    public function scan($path, $scope = null, $lowercase = false)
    {
        $found = [];

        $scopes_config = require \App\PATH.'/config/scopes.php';

        if ($scope) {

            $dirs = $scopes_config[$scope];

        } else {

            $dirs = [];

            foreach ($scopes_config as $name => $paths) {

                $dirs = array_merge($dirs, $paths);
            }
        }

        foreach ($dirs as $dir) {

            $search = $dir.'/'.$path;

            $recursive = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($search),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($recursive as $file) {

                if ($file->isFile()) {

                    $name = $file->getBaseName('.php');

                    $found[] = ($lowercase) ? strtolower($name) : $name;
                }
            }

        }

        return $found;
    }
}
