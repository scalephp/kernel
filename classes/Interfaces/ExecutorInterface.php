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

interface ExecutorInterface
{
    public function prepare();
    
    public function execute();
}
