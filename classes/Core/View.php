<?php namespace Scale\Kernel\Core;

class View
{
    /**
     *
     * @var string
     */
    protected $name;
    
    /**
     *
     * @var string 
     */
    protected $path;
    
    /**
     *
     * @var array
     */
    protected $params = array();
    
    /**
     * 
     * @param string $name
     * @param array $params
     */
    public function __construct($name, $params = array(), $ns = null)
    {
        $this->name = $name;
                
        $this->path =  (($ns === null) ? namespace\PATH : $ns)."/views/$name.php";
        $this->params = $params;
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->render(true);
    }

    /**
     * 
     * @param bool $return
     * @return mixed null|string
     */
    public function render($return = false)
    {
        // Set paramters into local scope variables
        extract($this->params);

        if ($return) {
            ob_start();
            include $this->path;
            return ob_get_clean();
        }
        
        include $this->path;
    }
}
