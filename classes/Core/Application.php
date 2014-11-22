<?php namespace Scale\Kernel\Core;

/**
 * Scale Application
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */

use Scale\Kernel\Interfaces\BuilderInterface;

class Application implements BuilderInterface
{
    /**
     * DI Trait
     */
    use Builders;

    /**
     *
     * @param string $api
     */
    public function __construct($api)
    {
        // Loads DI configuration
        $this->loadBuilders();
        
        // Use Builder to find executor for the given client
        $this->executor = $this->executor($api);
    }
    
    /**
     * Execute the application handler
     */
    public function execute()
    {
        $this->executor()->prepare($this)->execute();
    }
}
