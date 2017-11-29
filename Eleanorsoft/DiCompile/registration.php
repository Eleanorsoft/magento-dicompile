<?php
/**
 * registration.php
 * Register module in Magento2 system
 *
 * @package Eleanorsoft_DiCompile
 * @author Konstantin Esin <hello@eleanorsoft.com>
 * @copyright Copyright (c) 2017 Eleanorsoft (https://www.eleanorsoft.com/)
 */

use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Eleanorsoft_DiCompile',
    __DIR__
);