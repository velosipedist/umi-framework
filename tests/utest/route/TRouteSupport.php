<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */
namespace utest\route;

use umi\toolkit\IToolkit;

/**
 * Трейт для регистрации тулбокса маршрутизации.
 */
trait TRouteSupport
{
    /**
     * Получить тестовый тулкит
     * @throws \RuntimeException
     * @return IToolkit
     */
    abstract protected function getTestToolkit();

    protected function registerRouteTools()
    {
        $this->getTestToolkit()->registerToolbox(
            require(LIBRARY_PATH . '/route/toolbox/config.php')
        );
    }
}
 