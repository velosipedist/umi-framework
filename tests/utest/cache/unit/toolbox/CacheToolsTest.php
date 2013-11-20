<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace utest\cache\unit\toolbox;

use umi\cache\toolbox\CacheTools;
use umi\dbal\cluster\server\IServer;
use umi\dbal\driver\IColumnScheme;
use utest\cache\CacheTestCase;
use utest\cache\mock\MockCacheAware;

class CacheToolsTest extends CacheTestCase
{
    /**
     * @var IServer
     */
    private $connection;

    private $tableName = 'test_cache';

    protected function setUpFixtures()
    {
        $this->connection = $this->getDbServer();
    }

    protected function tearDownFixtures()
    {
        $this->connection->getDbDriver()
            ->dropTable($this->tableName);
    }

    public function testCacheInjection()
    {
        $this->getTestToolkit()->setSettings(
            [
                CacheTools::NAME => [
                    'type' => CacheTools::TYPE_DB,
                    'options' => [
                        'table'    => [
                            'tableName'        => $this->tableName,
                            'keyColumnName'    => 'key',
                            'valueColumnName'  => 'cacheValue',
                            'expireColumnName' => 'cacheExpiration'
                        ],
                        'serverId' => $this->connection->getId()
                    ]
                ]
            ]
        );

        $driver = $this->connection->getDbDriver();
        $table = $driver->addTable($this->tableName);
        $table->addColumn('key', IColumnScheme::TYPE_VARCHAR, [IColumnScheme::OPTION_COMMENT => 'Cache unique key']);
        $table->addColumn('cacheValue', IColumnScheme::TYPE_BLOB, [IColumnScheme::OPTION_COMMENT => 'Cache value']);
        $table->addColumn(
            'cacheExpiration',
            IColumnScheme::TYPE_INT,
            [IColumnScheme::OPTION_COMMENT => 'Cache expire timestamp', IColumnScheme::OPTION_UNSIGNED => true]
        );
        $table->setPrimaryKey('key');
        $table->addIndex('expire')
            ->addColumn('cacheExpiration');
        $driver->applyMigrations();

        $cachingService = new MockCacheAware();
        $this->resolveOptionalDependencies($cachingService);

        $callsCounter = 0;
        $value = $cachingService->get(
            'test_closure',
            function () use (&$callsCounter) {
                $callsCounter++;

                return $callsCounter;
            }
        );
        $this->assertEquals(
            1,
            $value,
            'Ожидается, что, если значение еще не было закешировано, выполнится функция, вычисляющая значение для кеша'
        );

        $newValue = $cachingService->get(
            'test_closure',
            function () use (&$callsCounter) {
                $callsCounter++;

                return $callsCounter;
            }
        );
        $this->assertEquals(
            1,
            $newValue,
            'Ожидается, что, если значение было закешировано, функция, вычисляющая значение для кеша, не выполнится'
        );

    }

    public function testCacheNoInjection()
    {

        $this->getTestToolkit()->setSettings(
            [
                CacheTools::NAME => [
                    'type' => null
                ]
            ]
        );

        $cachingService = new MockCacheAware();
        $this->resolveOptionalDependencies($cachingService);

        $callsCounter = 0;
        $value = $cachingService->get(
            'test_closure',
            function () use (&$callsCounter) {
                $callsCounter++;

                return $callsCounter;
            }
        );
        $this->assertEquals(
            1,
            $value,
            'Ожидается, что, если кеш не был внедрен, функция, вычисляющая значение для кеша, выполнится всегда'
        );

        $newValue = $cachingService->get(
            'test_closure',
            function () use (&$callsCounter) {
                $callsCounter++;

                return $callsCounter;
            }
        );
        $this->assertEquals(
            2,
            $newValue,
            'Ожидается, что, если кеш не был внедрен, функция, вычисляющая значение для кеша, выполнится всегда'
        );
    }

}
 