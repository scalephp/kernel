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
     *
     * @var array
     */
    protected $builders = [];

    /**
     *
     * @var array
     */
    protected $instances = [];

    /**
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
     * @param type $name
     * @param type $instance
     */
    public function setInstance($name, $instance)
    {
        $this->instances[$name] = $instance;
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

                $param_name = ($param_class->name == 'Closure') ? $param->name : strtolower($param_class->name);

                // Get from trait's parent object
                $local = $this->$param_name;

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
        $this->builders = require \App\PATH.'/config/builders.php';
    }
}
