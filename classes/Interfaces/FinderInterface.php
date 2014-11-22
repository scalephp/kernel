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

interface FinderInterface
{
    public function scan($path, $scope = null, $lowercase = false);
}
