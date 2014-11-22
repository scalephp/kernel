<?php namespace Scale\Kernel\Core;

/**
 * Explorer
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */

trait Explorer
{

    /**
     *
     * @param type $pattern
     * @param type $flags
     * @return type
     */
    public function rGlob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {

            $files = array_merge($files, $this->rGlob($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }

    /**
     *
     * @param type $path
     * @return type
     */
    public function recurse($path)
    {
        return $this->rGlob(App\PATH.$path);
    }

    /**
     *
     * @param type $path
     * @param type $file
     * @return type
     */
    public function resolveFile($path, $file)
    {
        return str_replace(
            '/',
            '\\',
            str_replace(
                '.php',
                '',
                str_replace(
                    App\PATH.$path,
                    '',
                    $file
                )
            )
        );
    }

    /**
     * Get application controller names
     *
     * @return array
     */
    public function getControllers($name = '*')
    {
        $path = $this->recurse("/classes/Controller/$name.php");

        $names = array();
        foreach ($path as $controller) {
            $names[] = $this->resolveFile('/classes/Controller/', $controller);
        }

        return $names;
    }
}
