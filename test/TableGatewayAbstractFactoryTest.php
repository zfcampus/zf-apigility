<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\Stdlib\Hydrator\HydratorPluginManager;
use ZF\Apigility\TableGatewayAbstractFactory;

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
        $this->services->set('Config', []);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingDbConnectedConfigSegment()
    {
        $this->services->set('Config', ['zf-apigility' => []]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingServiceSubSegment()
    {
        $this->services->set('Config', ['zf-apigility' => ['db-connected' => []]]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentIsInvalid()
    {
        $this->services->set('Config', ['zf-apigility' => ['db-connected' => ['Foo' => 'invalid']]]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainTableName()
    {
        $this->services->set('Config', ['zf-apigility' => ['db-connected' => ['Foo' => []]]]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainAdapterInformation()
    {
        $this->services->set('Config', [
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
        $this->services->set('Config', ['zf-apigility' => ['db-connected' => ['Foo' => [
            'table_name'   => 'test',
            'adapter_name' => 'FooAdapter',
        ]]]]);

        $this->services->set('FooAdapter', new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameNoAdapterNameAndServicesContainDefaultAdapter()
    {
        $this->services->set('Config', ['zf-apigility' => ['db-connected' => ['Foo' => [
            'table_name'   => 'test',
        ]]]]);

        $this->services->set('Zend\Db\Adapter\Adapter', new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function validConfig()
    {
        return [
            'named_adapter'   => ['Db\NamedAdapter'],
            'default_adapter' => ['Zend\Db\Adapter\Adapter'],
        ];
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfiguration($adapterServiceName)
    {
        $this->services->set('HydratorManager', new HydratorPluginManager());

        $platform = $this->getMockBuilder('Zend\Db\Adapter\Platform\PlatformInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $platform->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('sqlite'));

        $adapter = $this->getMockBuilder('Zend\Db\Adapter\Adapter')
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
                    ],
                ],
            ],
            'zf-rest' => [
                'Foo\Controller' => [
                    'entity_class' => 'ZFTest\Apigility\TestAsset\Foo',
                ],
            ],
        ];
        if ($adapterServiceName !== 'Zend\Db\Adapter\Adapter') {
            $config['zf-apigility']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->set('Config', $config);

        $gateway = $this->factory->createServiceWithName($this->services, 'footable', 'Foo\Table');
        $this->assertInstanceOf('Zend\Db\TableGateway\TableGateway', $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter, $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf('Zend\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertInstanceOf('Zend\Stdlib\Hydrator\ClassMethods', $resultSet->getHydrator());
        $this->assertAttributeInstanceOf('ZFTest\Apigility\TestAsset\Foo', 'objectPrototype', $resultSet);
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfigurationWithoutZfRest($adapterServiceName)
    {
        $this->services->set('HydratorManager', new HydratorPluginManager());

        $platform = $this->getMockBuilder('Zend\Db\Adapter\Platform\PlatformInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $platform->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('sqlite'));

        $adapter = $this->getMockBuilder('Zend\Db\Adapter\Adapter')
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
                        'entity_class'            => 'ZFTest\Apigility\TestAsset\Bar'
                    ],
                ],
            ],
        ];
        if ($adapterServiceName !== 'Zend\Db\Adapter\Adapter') {
            $config['zf-apigility']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->set('Config', $config);

        $gateway = $this->factory->createServiceWithName($this->services, 'footable', 'Foo\Table');
        $this->assertInstanceOf('Zend\Db\TableGateway\TableGateway', $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter, $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf('Zend\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertInstanceOf('Zend\Stdlib\Hydrator\ClassMethods', $resultSet->getHydrator());
        $this->assertAttributeInstanceOf('ZFTest\Apigility\TestAsset\Bar', 'objectPrototype', $resultSet);
    }
}
