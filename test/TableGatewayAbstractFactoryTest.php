<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Db\Adapter\AdapterInterface as DbAdapterInterface;
use Zend\Db\Adapter\Platform\PlatformInterface as DbPlatformInterface;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Hydrator\ClassMethods;
use Zend\Hydrator\HydratorPluginManager;
use ZF\Apigility\TableGatewayAbstractFactory;

class TableGatewayAbstractFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = $this->prophesize(ContainerInterface::class);
        $this->factory  = new TableGatewayAbstractFactory();
    }

    public function testWillNotCreateServiceWithoutAppropriateSuffix()
    {
        $this->services->has('config')->shouldNotBeCalled();
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfConfigServiceIsMissing()
    {
        $this->services->has('config')->willReturn(false);
        $this->services->get('config')->shouldNotBeCalled();
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingApigilityConfig()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn([]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingDbConnectedConfigSegment()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['zf-apigility' => []]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingServiceSubSegment()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['zf-apigility' => ['db-connected' => []]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentIsInvalid()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['zf-apigility' => ['db-connected' => ['Foo' => 'invalid']]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainTableName()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['zf-apigility' => ['db-connected' => ['Foo' => []]]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainAdapterInformation()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
            ->willReturn([
                'zf-apigility' => [
                    'db-connected' => [
                        'Foo' => [
                            'table_name' => 'test',
                        ],
                    ],
                ],
            ]);
        $this->services->has(DbAdapterInterface::class)->willReturn(false);
        $this->services->has(DbAdapter::class)->willReturn(false);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameAndAdapterName()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
            ->willReturn([
                'zf-apigility' => [
                    'db-connected' => [
                        'Foo' => [
                            'table_name'   => 'test',
                            'adapter_name' => 'FooAdapter',
                        ],
                    ],
                ],
            ]);

        $this->services->has('FooAdapter')->willReturn(true);
        $this->assertTrue($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameNoAdapterNameAndServicesContainDefaultAdapter()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
            ->willReturn([
                'zf-apigility' => [
                    'db-connected' => [
                        'Foo' => [
                            'table_name' => 'test',
                        ],
                    ],
                ],
            ]);

        $this->services->has(DbAdapterInterface::class)->willReturn(false);
        $this->services->has(DbAdapter::class)->willReturn(true);
        $this->assertTrue($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function validConfig()
    {
        return [
            'named_adapter'   => ['Db\NamedAdapter'],
            'default_adapter' => [DbAdapter::class],
        ];
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfiguration($adapterServiceName)
    {
        $hydrator = $this->prophesize(ClassMethods::class)->reveal();
        $hydrators = $this->prophesize(HydratorPluginManager::class);
        $hydrators->get('ClassMethods')->willReturn($hydrator);
        $this->services->get('HydratorManager')->willReturn($hydrators->reveal());

        $platform = $this->prophesize(DbPlatformInterface::class);
        $platform->getName()->willReturn('sqlite');

        $adapter = $this->prophesize(DbAdapter::class);
        $adapter->getPlatform()->willReturn($platform->reveal());

        $this->services->has($adapterServiceName)->willReturn(true);
        $this->services->get($adapterServiceName)->willReturn($adapter->reveal());

        $config = [
            'zf-apigility' => [
                'db-connected' => [
                    'Foo' => [
                        'controller_service_name' => 'Foo\Controller',
                        'table_name'              => 'foo',
                        'hydrator_name'           => 'ClassMethods',
                    ],
                ],
            ],
            'zf-rest' => [
                'Foo\Controller' => [
                    'entity_class' => TestAsset\Foo::class,
                ],
            ],
        ];
        if ($adapterServiceName !== DbAdapter::class) {
            $config['zf-apigility']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->get('config')->willReturn($config);

        $gateway = $this->factory->__invoke($this->services->reveal(), 'Foo\Table');
        $this->assertInstanceOf(TableGateway::class, $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter->reveal(), $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf(HydratingResultSet::class, $resultSet);
        $this->assertSame($hydrator, $resultSet->getHydrator());
        $this->assertAttributeInstanceOf(TestAsset\Foo::class, 'objectPrototype', $resultSet);
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfigurationWithoutZfRest($adapterServiceName)
    {
        $hydrator = $this->prophesize(ClassMethods::class)->reveal();
        $hydrators = $this->prophesize(HydratorPluginManager::class);
        $hydrators->get('ClassMethods')->willReturn($hydrator);
        $this->services->get('HydratorManager')->willReturn($hydrators->reveal());

        $platform = $this->prophesize(DbPlatformInterface::class);
        $platform->getName()->willReturn('sqlite');

        $adapter = $this->prophesize(DbAdapter::class);
        $adapter->getPlatform()->willReturn($platform->reveal());

        $this->services->has($adapterServiceName)->willReturn(true);
        $this->services->get($adapterServiceName)->willReturn($adapter);

        $config = [
            'zf-apigility' => [
                'db-connected' => [
                    'Foo' => [
                        'controller_service_name' => 'Foo\Controller',
                        'table_name'              => 'foo',
                        'hydrator_name'           => 'ClassMethods',
                        'entity_class'            => TestAsset\Bar::class,
                    ],
                ],
            ],
        ];
        if ($adapterServiceName !== DbAdapter::class) {
            $config['zf-apigility']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->get('config')->willReturn($config);

        $gateway = $this->factory->__invoke($this->services->reveal(), 'Foo\Table');
        $this->assertInstanceOf(TableGateway::class, $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter->reveal(), $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf(HydratingResultSet::class, $resultSet);
        $this->assertInstanceOf(ClassMethods::class, $resultSet->getHydrator());
        $this->assertAttributeInstanceOf(TestAsset\Bar::class, 'objectPrototype', $resultSet);
    }
}
