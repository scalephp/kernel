<?php namespace Scale\Kernel\Core;

use Scale\Kernel\Interfaces\BuilderInterface;
use Scale\Kernel\Core\Path;

class Container implements BuilderInterface
{
    /**
     * DI Trait
     */
    use Builders;

    protected $path;

    public function __construct($path = null)
    {
        if (is_string($path)) {
            $this->path = $path;
        } elseif (is_object($path)) {
            $this->path = $path->get();
        } else {
            $this->path = (new Path)->get();
        }

        $this->loadBuilders();
    }
}
