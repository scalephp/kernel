<?php namespace Scale\Kernel\Core;

/**
 * Scale Application
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */

use Scale\Kernel\Interfaces\ExecutorInterface;

class Application
{
    /**
     *
     * @var ExecutorInterface
     */
    protected $executor;


    /**
     *
     * @param string $executor
     */
    public function __construct(ExecutorInterface $executor)
    {
        $this->executor = $executor;
    }

    /**
     * Use Builder to find executor for the given client
     */
    public function setExecutor(ExecutorInterface $executor)
    {
        $this->executor = $executor;
    }

    /**
     *
     * @return ExecutorInterface
     */
    public function getExecutor()
    {
        return $this->executor;
    }

    /**
     * Execute the application handler
     */
    public function execute()
    {
        return $this->executor->prepare()->execute();
    }
}
