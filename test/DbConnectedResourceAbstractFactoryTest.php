<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use ZF\Apigility\DbConnectedResourceAbstractFactory;

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

    public function testWillNotCreateServiceIfApigilityConfigMissing()
    {
        $this->services->set('Config', array());
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApigilityConfigIsNotAnArray()
    {
        $this->services->set('Config', array('zf-apigility' => 'invalid'));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApigilityConfigDoesNotHaveDbConnectedSegment()
    {
        $this->services->set('Config', array('zf-apigility' => array('foo' => 'bar')));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfDbConnectedSegmentDoesNotHaveRequestedName()
    {
        $this->services->set('Config', array('zf-apigility' => array(
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
        $config = array('zf-apigility' => array(
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
        $config = array('zf-apigility' => array(
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

        $config = array('zf-apigility' => array(
            'db-connected' => array(
                'Foo' => $configForDbConnected,
            ),
        ));
        $this->services->set('Config', $config);

        $resource = $this->factory->createServiceWithName($this->services, 'foo', 'Foo');
        $this->assertInstanceOf('ZF\Apigility\DbConnectedResource', $resource);
    }
}
