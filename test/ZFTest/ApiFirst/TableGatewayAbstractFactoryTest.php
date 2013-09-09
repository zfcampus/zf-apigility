<?php

namespace ZFTest\ApiFirst;

use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Zend\Stdlib\Hydrator\HydratorPluginManager;
use ZF\ApiFirst\TableGatewayAbstractFactory;

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

    public function testWillNotCreateServiceIfMissingApiFirstConfig()
    {
        $this->services->set('Config', array());
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingDbConnectedConfigSegment()
    {
        $this->services->set('Config', array('zf-api-first' => array()));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingServiceSubSegment()
    {
        $this->services->set('Config', array('zf-api-first' => array('db-connected' => array())));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentIsInvalid()
    {
        $this->services->set('Config', array('zf-api-first' => array('db-connected' => array('Foo' => 'invalid'))));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainTableName()
    {
        $this->services->set('Config', array('zf-api-first' => array('db-connected' => array('Foo' => array()))));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainAdapterInformation()
    {
        $this->services->set('Config', array('zf-api-first' => array('db-connected' => array('Foo' => array('table_name' => 'test')))));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameAndAdapterName()
    {
        $this->services->set('Config', array('zf-api-first' => array('db-connected' => array('Foo' => array(
            'table_name'   => 'test',
            'adapter_name' => 'FooAdapter',
        )))));

        $this->services->set('FooAdapter', new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameNoAdapterNameAndServicesContainDefaultAdapter()
    {
        $this->services->set('Config', array('zf-api-first' => array('db-connected' => array('Foo' => array(
            'table_name'   => 'test',
        )))));

        $this->services->set('Zend\Db\Adapter\Adapter', new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function validConfig()
    {
        return array(
            'named_adapter'   => array('Db\NamedAdapter'),
            'default_adapter' => array('Zend\Db\Adapter\Adapter'),
        );
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

        $config = array('zf-api-first' => array('db-connected' => array(
            'Foo' => array(
                'table_name'    => 'foo',
                'hydrator_name' => 'ClassMethods',
                'entity_class'  => 'ZFTest\ApiFirst\TestAsset\Foo',
            ),
        )));
        if ($adapterServiceName !== 'Zend\Db\Adapter\Adapter') {
            $config['zf-api-first']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->set('Config', $config);

        $gateway = $this->factory->createServiceWithName($this->services, 'footable', 'Foo\Table');
        $this->assertInstanceOf('Zend\Db\TableGateway\TableGateway', $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter, $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf('Zend\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertInstanceOf('Zend\Stdlib\Hydrator\ClassMethods', $resultSet->getHydrator());
        $this->assertAttributeInstanceOf('ZFTest\ApiFirst\TestAsset\Foo', 'objectPrototype', $resultSet);
    }
}
