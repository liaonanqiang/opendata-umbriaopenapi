<?php

namespace Umbria\OpenApiBundle\Controller\Tourism;

/**
 * This code has been auto-generated by the JMSDiExtraBundle.
 *
 * Manual changes to it will be lost.
 */
class TravelAgencyController__JMSInjector
{
    public static function inject($container) {
        $instance = new \Umbria\OpenApiBundle\Controller\Tourism\TravelAgencyController($container->get('doctrine.orm.entity_manager', 1), $container->get('umbria_open_api.filter_bag', 1), $container->get('knp_paginator', 1));
        return $instance;
    }
}
