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
        if ($value instanceof \Closure) {

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
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (substr($name, 0, 5) == 'build') {
            return $this->newInstance(strtolower(substr($name, 5)), $arguments);
        }

        return $this->callBuilder($name, $arguments);
    }

    /**
     *
     * @param string $name
     * @return \Closure
     */
    public function getBuilder($name)
    {
        if (isset($this->builders[$name])) {
            return $this->builders[$name];
        }
    }

    /**
     *
     * @param string   $name
     * @param \Closure $builder
     * @return mixed
     */
    public function setBuilder($name, \Closure $builder)
    {
        $this->builders[$name] = $builder;

        return $this;
    }

    /**
     *
     * @param string $name
     * @param array  $params
     * @return mixed
     */
    public function callBuilder($name, array $params = [])
    {
        if (isset($this->builders[$name])) {
            return call_user_func_array($this->builders[$name], $params);
        }
        throw new RuntimeException("Call to undefind method $name");
    }

    /**
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
     * @param array  $params
     */
    public function newInstance($name, array $params = [])
    {
        return $this->setInstance($name, $this->callBuilder($name, $params));
    }

    /**
     * Sets a resource instance to the object
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
     *
     * @param string $class
     * @return object
     */
    public function constructInject($class)
    {
        $reflection = new \ReflectionClass($class);

        $dependencies = [];

        $constructor = $reflection->getConstructor();

        if (!$constructor) {

            return $reflection->newInstanceWithoutConstructor();
        }

        foreach ($constructor->getParameters() as $param) {

            $param_class = $param->getClass();

            if ($param_class) {

                if ($param_class->name == 'Closure') {

                    $param_name = $param->name;
                    // Get from trait's parent object
                    $local = $this->$param_name;

                } else {

                    $param_name = strtolower($param_class->name);
                    $local = $this->$param_name;
                    if ($local instanceof \Closure) {
                        $local = $local();
                    }
                }

                // If we have a local value, set it, else create a new one
                $dependencies[] = is_object($local) ? $local : $param_class->newInstance();

            } elseif ($param->isDefaultValueAvailable()) {

                $dependencies[] = $param->getDefaultValue();

            } elseif ($param->isOptional()) {

                $dependencies[] = null;

            } else {

                throw new RuntimeException('Unable to resolve parameter');
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     *  Container of Builder Closures
     *
     *  [
     *    'object' => function ($type) { return Concrete::factory($type, ..);},
     *    'foobar' => function () { return new Foobar_Type();},
     *    '' => ''...
     *  ]
     */
    protected function loadBuilders()
    {
        $this->builders = require \App\PATH.'/etc/builders.php';
    }
}
