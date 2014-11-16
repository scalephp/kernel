<?php namespace Scale\Kernel\Interfaces;
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

interface BuilderInterface
{
    /**
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name);

    /**
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value);
    /**
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments);

    /**
     *
     * @param string $name
     * @return \Closure
     */
    public function getBuilder($name);

    /**
     *
     * @param string   $name
     * @param \Closure $builder
     * @return mixed
     */
    public function setBuilder($name, \Closure $builder);

    /**
     *
     * @param string $name
     * @param array  $params
     * @return mixed
     */
    public function callBuilder($name, array $params = []);

    /**
     *
     * @param type $name
     * @param type $instance
     */
    public function setInstance($name, $instance);

    /**
     *
     * @param BuilderInterface $consumer
     * @param string       $builder
     * @param object       $instance
     */
    public function provide(BuilderInterface $consumer, $builder, $instance = null);
}
