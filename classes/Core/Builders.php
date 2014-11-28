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

    /**
     * Returns a named value from $instances, if not set then returns the
     * builder for that key
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        return $this->getBuilder($name);
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
     * Gets a builder closure for a given key
     *
     * @param string $name
     * @return Closure
     */
    public function getBuilder($name)
    {
        if (isset($this->builders[$name])) {
            return $this->builders[$name];
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
     * Invokes a builder with the given key and parameters
     *
     * @param string $name
     * @param array  $params
     * @return mixed
     */
    public function callBuilder($name, array $params = [])
    {
        if (isset($this->builders[$name])) {
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
        throw new RuntimeException("Call to undefind method $name");
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
     * Return classes required in the given class' constructor
     * @param  bool $lowercase
     * @return array
     */
    public function reflectConstruct($lowercase = true)
    {
        $classes = [];
        $params = (new \ReflectionClass($this))->getConstructor()->getParameters();
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
        $reflection = new \ReflectionClass($class);

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
                $dependencies[] = $pc->newInstance();

            // If it isn't an object, let's check for a default scalar
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();

            // If no default value availble, check if it's optional
            } elseif ($param->isOptional()) {
                $dependencies[] = null;

            // We can't build this class correctly, fail
            } else {
                throw new RuntimeException('Unable to resolve parameter');
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
    protected function getLocalValue($param)
    {
        $class = $param->getClass();

        // Do we need a Closure returned?
        if ($class->name == 'Closure') {

            // Get from trait's parent object
            $local = $this->__get($param->name);

        // We need an instance, not a builder
        } else {

            $local = $this->__get(strtolower($class->name));

            // If we have a local builder, execute it to get a new instace
            if ($local instanceof Closure) {
                $local = call_user_func($local);
            }
        }
        return $local;
    }

    /**
     *  Container of Builder Closures
     */
    protected function loadBuilders()
    {
        $this->builders = $this->appConfig('builders');
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
