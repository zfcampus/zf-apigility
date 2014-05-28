<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;
use ZF\Apigility\DbConnectedResource;

class DbConnectedResourceTest extends TestCase
{
    public function setUp()
    {
        $this->table = $this->getMockBuilder('Zend\Db\TableGateway\TableGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resource = new DbConnectedResource($this->table, 'id', 'ArrayObject');
    }

    protected function setInputFilter($resource, $inputFilter)
    {
        $r = new ReflectionObject($resource);
        $p = $r->getProperty('inputFilter');
        $p->setAccessible(true);
        $p->setValue($resource, $inputFilter);
    }

    public function testCreatePullsDataFromComposedInputFilterWhenPresent()
    {
        $filtered = array(
            'foo' => 'BAR',
            'baz' => 'QUZ',
        );

        $filter = $this->getMock('Zend\InputFilter\InputFilter');
        $filter->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($filtered));

        $this->setInputFilter($this->resource, $filter);

        $this->table->expects($this->once())
            ->method('insert')
            ->with($this->equalTo($filtered));

        $this->table->expects($this->once())
            ->method('getLastInsertValue')
            ->will($this->returnValue('foo'));

        $resultSet = $this->getMock('Zend\Db\ResultSet\AbstractResultSet');

        $this->table->expects($this->once())
            ->method('select')
            ->with($this->equalTo(array('id' => 'foo')))
            ->will($this->returnValue($resultSet));

        $resultSet->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));

        $resultSet->expects($this->once())
            ->method('current')
            ->will($this->returnValue($filtered));

        $this->assertEquals($filtered, $this->resource->create(array('foo' => 'bar')));
    }

    public function testUpdatePullsDataFromComposedInputFilterWhenPresent()
    {
        $filtered = array(
            'foo' => 'BAR',
            'baz' => 'QUZ',
        );

        $filter = $this->getMock('Zend\InputFilter\InputFilter');
        $filter->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($filtered));

        $this->setInputFilter($this->resource, $filter);

        $this->table->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo($filtered),
                array('id' => 'foo')
            );

        $resultSet = $this->getMock('Zend\Db\ResultSet\AbstractResultSet');

        $this->table->expects($this->once())
            ->method('select')
            ->with($this->equalTo(array('id' => 'foo')))
            ->will($this->returnValue($resultSet));

        $resultSet->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));

        $resultSet->expects($this->once())
            ->method('current')
            ->will($this->returnValue($filtered));

        $this->assertEquals($filtered, $this->resource->update('foo', array('foo' => 'bar')));
    }

    public function testPatchPullsDataFromComposedInputFilterWhenPresent()
    {
        $filtered = array(
            'foo' => 'BAR',
            'baz' => 'QUZ',
        );

        $filter = $this->getMock('Zend\InputFilter\InputFilter');
        $filter->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($filtered));

        $this->setInputFilter($this->resource, $filter);

        $this->table->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo($filtered),
                array('id' => 'foo')
            );

        $resultSet = $this->getMock('Zend\Db\ResultSet\AbstractResultSet');

        $this->table->expects($this->once())
            ->method('select')
            ->with($this->equalTo(array('id' => 'foo')))
            ->will($this->returnValue($resultSet));

        $resultSet->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));

        $resultSet->expects($this->once())
            ->method('current')
            ->will($this->returnValue($filtered));

        $this->assertEquals($filtered, $this->resource->patch('foo', array('foo' => 'bar')));
    }
}
