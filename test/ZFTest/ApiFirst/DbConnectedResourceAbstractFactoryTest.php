<?php

namespace ZFTest\ApiFirst;

use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use ZF\ApiFirst\DbConnectedResourceAbstractFactory;

class DbConnectedResourceAbstractFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new TestAsset\ServiceManager();
        $this->factory  = new DbConnectedResourceAbstractFactory();
    }

    public function testWillNotCreateServiceIfConfigServiceMissing()
    {
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApiFirstConfigMissing()
    {
        $this->services->set('Config', array());
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApiFirstConfigIsNotAnArray()
    {
        $this->services->set('Config', array('zf-api-first' => 'invalid'));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApiFirstConfigDoesNotHaveDbConnectedSegment()
    {
        $this->services->set('Config', array('zf-api-first' => array('foo' => 'bar')));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfDbConnectedSegmentDoesNotHaveRequestedName()
    {
        $this->services->set('Config', array('zf-api-first' => array(
            'db-connected' => array(
                'bar' => 'baz',
            ),
        )));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function invalidConfig()
    {
        return array(
            'invalid_table_service' => array(array('table_service' => 'non_existent')),
            'invalid_virtual_table' => array(array()),
        );
    }

    /**
     * @dataProvider invalidConfig
     */
    public function testWillNotCreateServiceIfDbConnectedSegmentIsInvalidConfiguration($configForDbConnected)
    {
        $config = array('zf-api-first' => array(
            'db-connected' => array(
                'Foo' => $configForDbConnected,
            ),
        ));
        $this->services->set('Config', $config);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function validConfig()
    {
        return array(
            'table_service' => array(array('table_service' => 'foobartable'), 'foobartable'),
            'virtual_table' => array(array(), 'Foo\Table'),
        );
    }

    /**
     * @dataProvider validConfig
     */
    public function testWillCreateServiceIfDbConnectedSegmentIsValid($configForDbConnected, $tableServiceName)
    {
        $config = array('zf-api-first' => array(
            'db-connected' => array(
                'Foo' => $configForDbConnected,
            ),
        ));
        $this->services->set('Config', $config);
        $this->services->set($tableServiceName, new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsResourceBasedOnConfiguration($configForDbConnected, $tableServiceName)
    {
        $tableGateway = $this->getMockBuilder('Zend\Db\TableGateway\TableGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $this->services->set($tableServiceName, $tableGateway);

        $config = array('zf-api-first' => array(
            'db-connected' => array(
                'Foo' => $configForDbConnected,
            ),
        ));
        $this->services->set('Config', $config);

        $resource = $this->factory->createServiceWithName($this->services, 'foo', 'Foo');
        $this->assertInstanceOf('ZF\ApiFirst\DbConnectedResource', $resource);
    }
}
