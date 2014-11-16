<?php namespace Scale\Kernel\Interfaces;

interface ViewInterface
{
    public function __construct($name, $params = array(), $ns = null);

    public function render($return = true);
}
