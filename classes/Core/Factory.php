<?php namespace Scale\Kernel\Core;

use Scale\Kernel\Core\Container;

class Factory extends Container
{
    /**
     * 
     * @param string $name
     * @return mixed
     */
    public function factory($name)
    {    
        if (class_exists($name)) {
            
            return $this->constructInject($name);
        }
    }
}
