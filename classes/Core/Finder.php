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
    public function scan($path, $scope = null, $lowercase = false, $recursive = true)
    {
        $found = [];

        foreach ($this->getDirs($scope) as $dir) {

            $search_dir = \App\PATH.$dir.$path;
            
            if (!file_exists($search_dir)) {
                continue;
            }
            
            $iterator = $this->getDirectoryIterator($search_dir, true);

            foreach ($iterator as $file) {

                if ($file->isFile()) {

                    $name = $file->getBaseName('.php');

                    $found[] = ($lowercase) ? strtolower($name) : $name;
                }
            }
        }
        return $found;
    }

    /**
     * 
     * @param  string $scope
     * @return array
     */
    public function getDirs($scope)
    {
        $scopes_config = require \App\PATH.'/config/scopes.php';

        if ($scope) {

            $dirs = $scopes_config[$scope];
        } else {

            $dirs = [];
            foreach ($scopes_config as $name => $paths) {
                $dirs = array_merge($dirs, $paths);
            }
        }
        return $dirs;
    }
    
    /**
     * 
     * @param string $path
     * @param bool   $recursive
     * @return \IteratorIterator
     */
    public function getDirectoryIterator($path, $recursive = true)
    {
        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            $iterator = new \IteratorIterator(
                new \DirectoryIterator($path)
            );                
        }   
        return $iterator;
    }
}
