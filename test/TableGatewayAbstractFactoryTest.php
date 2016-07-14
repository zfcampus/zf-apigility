<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Db\Adapter\Platform\PlatformInterface as DbPlatformInterface;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Hydrator\ClassMethods;
use Zend\Hydrator\HydratorPluginManager;
use ZF\Apigility\TableGatewayAbstractFactory;

/**
 * @todo Rewrite this to use a prophesized ContainerInterface/ServiceLocatorInterface,
 *     and see if that will resolve the issues reported in https://github.com/zendframework/zend-servicemanager/pull/136#issuecomment-232782751
 */
class TableGatewayAbstractFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new TestAsset\ServiceManager();
        $this->factory  = new TableGatewayAbstractFactory();
    }

    public function testWillNotCreateServiceWithoutAppropriateSuffix()
    {
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfConfigServiceIsMissing()
    {
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingApigilityConfig()
    {
        $this->services->set('config', []);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingDbConnectedConfigSegment()
    {
        $this->services->set('config', ['zf-apigility' => []]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingServiceSubSegment()
    {
        $this->services->set('config', ['zf-apigility' => ['db-connected' => []]]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentIsInvalid()
    {
        $this->services->set('config', ['zf-apigility' => ['db-connected' => ['Foo' => 'invalid']]]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainTableName()
    {
        $this->services->set('config', ['zf-apigility' => ['db-connected' => ['Foo' => []]]]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainAdapterInformation()
    {
        $this->services->set('config', [
            'zf-apigility' => [
                'db-connected' => [
                    'Foo' => [
                        'table_name' => 'test',
                    ],
                ],
            ],
        ]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameAndAdapterName()
    {
        $this->services->set('config', ['zf-apigility' => ['db-connected' => ['Foo' => [
            'table_name'   => 'test',
            'adapter_name' => 'FooAdapter',
        ]]]]);

        $this->services->set('FooAdapter', new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameNoAdapterNameAndServicesContainDefaultAdapter()
    {
        $this->services->set('config', ['zf-apigility' => ['db-connected' => ['Foo' => [
            'table_name'   => 'test',
        ]]]]);

        $this->services->set(DbAdapter::class, new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
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
        $this->fail('In testFactoryReturnsTableGatewayInstanceBasedOnConfiguration');
        $hydrator = $this->prophesize(ClassMethods::class)->reveal();
        $hydrators = $this->prophesize(HydratorPluginManager::class);
        $hydrators->get(ClassMethods::class)->willReturn($hydrator->reveal());

        $this->services->set('HydratorManager', $hydrators->reveal());

        $platform = $this->prophesize(DbPlatformInterface::class);
        $platform->getName()->willReturn('sqlite');

        $adapter = $this->prophesize(DbAdapter::class);
        $adapter->getPlatform()->willReturn($platform->reveal());

        $this->services->set($adapterServiceName, $adapter->reveal());

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
                    'entity_class' => 'ZFTest\Apigility\TestAsset\Foo',
                ],
            ],
        ];
        if ($adapterServiceName !== DbAdapter::class) {
            $config['zf-apigility']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->set('config', $config);

        $gateway = $this->factory->__invoke($this->services, 'Foo\Table');
        $this->assertInstanceOf(TableGateway::class, $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter, $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf(HydratingResultSet::class, $resultSet);
        $this->assertSame($hydrator->reveal(), $resultSet->getHydrator());
        $this->assertAttributeInstanceOf(TestAsset\Foo::class, 'objectPrototype', $resultSet);
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfigurationWithoutZfRest($adapterServiceName)
    {
        $this->services->set('HydratorManager', new HydratorPluginManager());

        $platform = $this->getMockBuilder(DbPlatformInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $platform->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('sqlite'));

        $adapter = $this->getMockBuilder(DbAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->any())
            ->method('getPlatform')
            ->will($this->returnValue($platform));

        $this->services->set($adapterServiceName, $adapter);

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
        $this->services->set('config', $config);

        $gateway = $this->factory->createServiceWithName($this->services, 'footable', 'Foo\Table');
        $this->assertInstanceOf(TableGateway::class, $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter, $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf(HydratingResultSet::class, $resultSet);
        $this->assertInstanceOf(ClassMethods::class, $resultSet->getHydrator());
        $this->assertAttributeInstanceOf(TestAsset\Bar::class, 'objectPrototype', $resultSet);
    }
}
