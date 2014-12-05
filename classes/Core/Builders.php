<?php namespace Scale\Kernel\Core;

/**
 * DI Builders
 *
 * Used to provide classes a generic container and transfer methods for
 * dependencies and instances.
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Scale\Kernel\Interfaces\BuilderInterface;
use Scale\Kernel\Core\RuntimeException;

trait Builders
{
    /**
     * Closures used to build classes
     *
     * @var array
     */
    protected $builders = [];

    /**
     * Instance variables
     *
     * @var array
     */
    protected $instances = [];

    protected $modes = [
        'file-load' => true,
        'apc-get'  => false,
        'apc-load'  => false,
    ];

    /**
     * Returns a named value from $instances, if not set then returns the
     * builder for that key
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return ($i = $this->getInstance($name)) ? $i : $this->getBuilder($name);
    }

    /**
     * Sets a builder Closure or instance value for a given key
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        if ($value instanceof Closure) {

            $this->setBuilder($name, $value);
        } else {

            $this->setInstance($name, $value);
        }
    }

    /**
     * If its present in instances, return it, else call its builder
     * to create a new instance
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // Do we have this object in store as an instance already?
        if (isset($this->instances[$name]) && !$arguments) {
            return $this->instances[$name];
        }

        /**
         * Intercept the "build*()" prefix and automatically generate the requested
         * resource.
         *
         * $class->build*()        --> builds instance of *
         *
         * $foobar->buildClient()  --> creates a "client"
         * $example->buildDriver() --> creates an "adapter"
         *
         * Uses factories defined in builders.php config file
         */
        if (substr($name, 0, 5) == 'build') {
            return $this->newInstance(strtolower(substr($name, 5)), $arguments);
        }

        // Call a builder with the same name as the __call() method
        return $this->callBuilder($name, $arguments);
    }

    /**
     *
     * @return array
     */
    public function getBuilders()
    {
        return $this->builders;
    }

    /**
     * Gets a builder closure for a given key
     *
     * @param string $name
     * @return Closure
     */
    public function getBuilder($name)
    {
        if (isset($this->builders[$name])) {
            return $this->builders[$name];
        } elseif($this->modes['apc-get']) {
            $s = false;
            $key = "builder::$name";
            $b = apc_fetch($key, $s);
            if (!$s || !$b) {
                $filename = strtolower(str_replace('\\', '/', $name));
                $path = "{$this->path}/config/factories/$filename.php";
                $b = file_get_contents($path);
                $b = str_replace('<?php', '', $b);
                apc_store($key, $b);
            }
            eval($b);
            $f = explode('\\', $name);
            return $this->builders[$name] = ${array_pop($f)};
        }
    }

    /**
     * Sets the builder closure for a given key
     *
     * @param string   $name
     * @param Closure $builder
     * @return mixed
     */
    public function setBuilder($name, Closure $builder)
    {
        $this->builders[$name] = $builder;

        return $this;
    }

    /**
     *
     * @param type $array
     * @return type
     */
    public function setBuilders($array)
    {
        $this->builders = $array;

        return $this;
    }

    /**
     * Invokes a builder with the given key and parameters
     *
     * @param string $name
     * @param array  $params
     * @return mixed
     */
    public function callBuilder($name, array $params = [])
    {
        $count = count($params);
        if ($count === 0) {
            return $this->builders[$name]();
        } elseif ($count === 1) {
            return $this->builders[$name]($params[0]);
        } elseif ($count === 2) {
            return $this->builders[$name]($params[0], $params[1]);
        } elseif ($count === 3) {
            return $this->builders[$name]($params[0], $params[1], $params[2]);
        } elseif ($count === 4) {
            return $this->builders[$name]($params[0], $params[1], $params[2], $params[3]);
        } else {
            return call_user_func_array($this->builders[$name], $params);
        }
    }

    /**
     * Sets a variable to the instance storage
     *
     * @param string $name
     * @param mixed  $instance
     * @return BuilderInterface
     */
    public function setInstance($name, $instance)
    {
        $this->instances[$name] = $instance;
        return $this;
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function getInstance($name)
    {
        return isset($this->instances[$name]) ? $this->instances[$name] : null;
    }

    /**
     * Creates and sets a new instace of a builder
     *
     * @param string $name
     * @param array  $params
     */
    public function newInstance($name, array $params = [])
    {
        return $this->setInstance($name, $this->callBuilder($name, $params));
    }

    /**
     * Sets a resource instance to the object
     *
     * @param BuilderInterface  $consumer
     * @param string $builder
     * @param mixed  $instance
     */
    public function provide(BuilderInterface $consumer, $builder, $instance = null)
    {
        if ($instance !== null) {
            $consumer->setInstance($builder, $instance);
        } else {
            $consumer->setBuilder($builder, $this->builders[$builder]);
        }
    }

    /**
     *
     * @param BuilderInterface $consumer
     */
    public function inform(BuilderInterface $consumer)
    {
        $consumer->path = $this->path;
        $consumer->setBuilders($this->getBuilders());
    }

    /**
     * Return classes required in the given class' constructor
     *
     * @param  bool $lowercase
     * @return array
     */
    public function reflectConstruct($lowercase = true)
    {
        $classes = [];
        $params = (new ReflectionClass($this))->getConstructor()->getParameters();
        foreach ($params as $param) {
            $name = $param->getClass()->name;
            $classes[] = ($lowercase) ? strtolower($name) : $name;
        }
        return $classes;
    }

    /**
     * Automatically injects dependencies into a new object's constructor
     * and returns the new instance.
     *
     * @param string $class
     * @return object
     */
    public function constructInject($class)
    {
        // Get the class' reflection
        $reflection = new ReflectionClass($class);

        // Array to hold dependencies to inject
        $dependencies = [];

        // Get the __construct() method of the given class
        $constructor = $reflection->getConstructor();

        // If no constructor, no dependencies, easy, just instantiate
        if (!$constructor) {
            return $reflection->newInstanceWithoutConstructor();
        }

        // If we have parameters, let's cycle through them
        foreach ($constructor->getParameters() as $param) {

            // Check if we can build this locally
            $local = $this->getLocalValue($param);

            // If found locally
            if (is_object($local)) {
                $dependencies[] = $local;

            // Else, let's instantiate via autoloader
            } elseif ($pc = $param->getClass()) {
                $dependencies[] = $this->constructInject($pc->name);

            // If it isn't an object, let's check for a default scalar
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();

            // If no default value availble, check if it's optional
            } elseif ($param->isOptional()) {
                $dependencies[] = null;

            // We can't build this class correctly, fail
            } else {
                throw new RuntimeException("Unable to resolve parameter");
            }
        }

        // Create and return new instance with given dependencies
        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * When constructInject()'ing a class, this method is called to determine if
     * the dependency can be created with builder definitions
     *
     * @param ReflectionParameter $param
     * @return mixed
     */
    protected function getLocalValue(ReflectionParameter $param)
    {
        $class = $param->getClass();

        // Do we need a Closure returned?
        if ($class->name == 'Closure') {

            // Get from trait's parent object
            $local = $this->getBuilder($param->name);

        // We need an instance, not a builder
        } else {

            $name = strtolower($class->name);

            $local = $this->getInstance($name);

            if (!$local) {
                $local= $this->getBuilder($name);
            }

            // If we have a local builder, execute it to get a new instace
            if ($local instanceof Closure) {

                $reflection = new ReflectionFunction($local);
                $arguments  = $reflection->getParameters();

                if ($arguments) {
                    $d = [];
                    foreach ($arguments as $arg) {
                        $d[] = $this->getLocalValue($arg);
                    }
                    $local = call_user_func_array($local, $d);
                } else {
                    $local = $local();
                }
            }
        }
        return $local;
    }

    /**
     *  Container of Builder Closures
     */
    protected function loadBuilders()
    {
        if ($this->modes['apc-load']) {
            $r = apc_fetch('runspace::', $s);
            if (!$s || !$r) {
                $r = file_get_contents($this->path.'/config/runspace.php');
                apc_store('runspace::', $r);
            }
            eval($r);
            $this->builders = $runspace;
        } elseif ($this->modes['file-load']) {
            $this->builders = $this->appConfig('builders');
        }
    }

    /**
     *
     * @param string $name
     * @return array
     */
    protected function appConfig($name)
    {
        return require $this->path."/config/{$name}.php";
    }
}
