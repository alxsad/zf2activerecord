<?php
/**
 * Zf2ActiveRecord
 *
 * @link http://github.com/alxsad/Zf2ActiveRecord for the canonical source repository
 * @copyright Copyright (c) 2012 Alex Davidovich <alxsad@gmail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @category Zf2ActiveRecord
 */

namespace Zf2ActiveRecord;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

/**
 * Zf2ActiveRecord module class
 *
 * @category Zf2ActiveRecord
 */
class Module implements AutoloaderProviderInterface, ConfigProviderInterface
{
    /**
     * Returns configuration to merge with application configuration
     *
     * @return array|\Traversable
     */
    public function getConfig ()
    {
        return include __DIR__ . '/config/module.php';
    }
    
    /**
     * Return an array for passing to Zend\Loader\AutoloaderFactory
     *
     * @return array
     */
    public function getAutoloaderConfig ()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}