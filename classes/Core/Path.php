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
        if ($path !== null) {
            $this->path = $path;
        } else{
            if ($_SERVER['DOCUMENT_ROOT']) {
               $this->path = dirname($_SERVER['DOCUMENT_ROOT']."/../../");
            } else {
               $this->path = realpath(__DIR__."/../../../../..");
            }
        }
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
