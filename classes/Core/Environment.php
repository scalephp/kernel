<?php namespace Scale\Kernel\Core;

/**
 * Environment
 *
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */

class Environment
{
    /**
     *
     * @var type 
     */
    protected $api;
    
    /**
     *
     * @var type 
     */
    protected $server_http = [
        'HTTP_HOST',
        'REQUEST_METHOD',
        'SERVER_PROTOCOL',
        'SERVER_ADDR',
        'QUERY_STRING',
        'DOCUMENT_ROOT',
        'HTTP_REFERER',
        'HTTP_USER_AGENT',
        'HTTPS',
        'REMOTE_ADDR',
        'REQUEST_URI',
    ];
    
    /**
     *
     * @var type 
     */
    protected $server_cli = [
        'argv',
        'argc',
        
    ];

    /**
     *
     * @var type 
     */
    protected $server = [];

    /**
     * 
     */
    public function __construct()
    {
        $this->api = (PHP_SAPI == 'cli' || PHP_SAPI == 'cli-server') ? 'cli' : 'http';
        
        $this->loadServer($this->api);
    }
    
    /**
     * 
     * @param type $api
     */
    public function loadServer($api)
    {
        foreach ($this->{"server_$api"} as $param) {
            
            $this->server[$param] = filter_input(INPUT_SERVER, $param, FILTER_SANITIZE_STRING);
        }
    }
    
    /**
     * 
     * @param string $name
     * @return mixed
     */
    public function getServer($name)
    {
        return isset($this->server[$name]) ? $this->server[$name] : null;
    }
    
    /**
     * 
     * @return string
     */
    public function getApi()
    {
        return $this->api;
    }
}
