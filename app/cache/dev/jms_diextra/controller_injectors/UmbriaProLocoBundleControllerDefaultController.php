<?php

namespace Umbria\ProLocoBundle\Controller;

/**
 * This code has been auto-generated by the JMSDiExtraBundle.
 *
 * Manual changes to it will be lost.
 */
class DefaultController__JMSInjector
{
    public static function inject($container) {
        $instance = new \Umbria\ProLocoBundle\Controller\DefaultController($container->get('doctrine.orm.entity_manager', 1));
        return $instance;
    }
}
