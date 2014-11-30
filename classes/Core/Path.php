<?php namespace Scale\Kernel\Core;

class Path
{
    /**
     *
     * @var string
     */
    protected $path;

    /**
     *
     * @param string $path
     */
    public function __construct($path = null)
    {
        $this->path = ($path) ?: dirname($_SERVER['DOCUMENT_ROOT']."/../../");
    }

    public function __toString()
    {
        return $this->path;
    }

    /**
     *
     * @return string
     */
    public function get()
    {
        return $this->path;
    }
}
