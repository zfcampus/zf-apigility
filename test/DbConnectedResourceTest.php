<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Zend\Db\ResultSet\AbstractResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\InputFilter\InputFilter;
use ZF\Apigility\DbConnectedResource;

class DbConnectedResourceTest extends TestCase
{
    protected function setUp()
    {
        $this->table    = $this->prophesize(TableGateway::class);
        $this->resource = new DbConnectedResource($this->table->reveal(), 'id', ArrayObject::class);
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
        $filtered = [
            'foo' => 'BAR',
            'baz' => 'QUZ',
        ];

        $filter = $this->prophesize(InputFilter::class);
        $filter->getValues()->willReturn($filtered);
        $this->setInputFilter($this->resource, $filter->reveal());

        $this->table->insert($filtered)->shouldBeCalled();
        $this->table->getLastInsertValue()->willReturn('foo');

        $resultSet = $this->prophesize(AbstractResultSet::class);
        $resultSet->count()->willReturn(1);
        $resultSet->current()->willReturn($filtered);

        $this->table->select(['id' => 'foo'])->willReturn($resultSet->reveal());

        $this->assertEquals($filtered, $this->resource->create(['foo' => 'bar']));
    }

    public function testUpdatePullsDataFromComposedInputFilterWhenPresent()
    {
        $filtered = [
            'foo' => 'BAR',
            'baz' => 'QUZ',
        ];

        $filter = $this->prophesize(InputFilter::class);
        $filter->getValues()->willReturn($filtered);
        $this->setInputFilter($this->resource, $filter->reveal());

        $this->table->update($filtered, ['id' => 'foo'])->shouldBeCalled();

        $resultSet = $this->prophesize(AbstractResultSet::class);
        $resultSet->count()->willReturn(1);
        $resultSet->current()->willReturn($filtered);

        $this->table->select(['id' => 'foo'])->willReturn($resultSet->reveal());

        $this->assertEquals($filtered, $this->resource->update('foo', ['foo' => 'bar']));
    }

    public function testPatchPullsDataFromComposedInputFilterWhenPresent()
    {
        $filtered = [
            'foo' => 'BAR',
            'baz' => 'QUZ',
        ];

        $filter = $this->prophesize(InputFilter::class);
        $filter->getValues()->willReturn($filtered);
        $this->setInputFilter($this->resource, $filter->reveal());

        $this->table->update($filtered, ['id' => 'foo'])->shouldBeCalled();

        $resultSet = $this->prophesize(AbstractResultSet::class);
        $resultSet->count()->willReturn(1);
        $resultSet->current()->willReturn($filtered);

        $this->table->select(['id' => 'foo'])->willReturn($resultSet->reveal());

        $this->assertEquals($filtered, $this->resource->patch('foo', ['foo' => 'bar']));
    }
}
