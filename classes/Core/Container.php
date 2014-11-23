<?php namespace Scale\Kernel\Core;

use Scale\Kernel\Interfaces\BuilderInterface;

class Container implements BuilderInterface
{
    /**
     * DI Trait
     */
    use Builders;
    
    public function __construct()
    {
        // Loads DI configuration
        $this->loadBuilders();
    }
}
