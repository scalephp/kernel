<?php namespace Scale\Kernel\Interfaces;

/**
 * Trait that allows classes to find files in a given scope.
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 */

interface FinderInterface
{
    public function scan($path, $scope = null, $lowercase = false);
}
