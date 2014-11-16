<?php namespace Scale\Kernel\Interfaces;
/**
 * Executor Interface
 *
 * An executor is able to handle an application run.
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */
use Scale\Kernel\Core\Application;

interface ExecutorInterface
{
    public function execute();
    
    public function prepare(Application $app);
}
