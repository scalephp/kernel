<?php namespace Scale\Kernel\Core;

/**
 * Runtime Exception
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */

class RuntimeException extends \Exception
{
    public static function handler($e)
    {
        print '<pre>'.$e->getMessage().':'.$e->getLine().PHP_EOL;
        print_r($e->getTrace());
        throw new \Exception;
    }
}
