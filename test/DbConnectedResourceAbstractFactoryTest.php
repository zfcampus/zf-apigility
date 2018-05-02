<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zend\Db\TableGateway\TableGateway;
use ZF\Apigility\DbConnectedResource;
use ZF\Apigility\DbConnectedResourceAbstractFactory;

class DbConnectedResourceAbstractFactoryTest extends TestCase
{
    protected function setUp()
    {
        $this->services = $this->prophesize(ContainerInterface::class);
        $this->factory  = new DbConnectedResourceAbstractFactory();
    }

    public function testWillNotCreateServiceIfConfigServiceMissing()
    {
        $this->services->has('config')->willReturn(false);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfApigilityConfigMissing()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn([]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfApigilityConfigIsNotAnArray()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['zf-apigility' => 'invalid']);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfApigilityConfigDoesNotHaveDbConnectedSegment()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['zf-apigility' => ['foo' => 'bar']]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfDbConnectedSegmentDoesNotHaveRequestedName()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
           ->willReturn(['zf-apigility' => [
                'db-connected' => [
                    'bar' => 'baz',
                ],
            ]]);
        $this->services->has('Foo\Table')->willReturn(false);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function invalidConfig()
    {
        return [
            'invalid_table_service' => [['table_service' => 'non_existent']],
            'invalid_virtual_table' => [[]],
        ];
    }

    /**
     * @dataProvider invalidConfig
     */
    public function testWillNotCreateServiceIfDbConnectedSegmentIsInvalidConfiguration($configForDbConnected)
    {
        $config = ['zf-apigility' => [
            'db-connected' => [
                'Foo' => $configForDbConnected,
            ],
        ]];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);

        if (isset($configForDbConnected['table_service'])) {
            $this->services->has($configForDbConnected['table_service'])->willReturn(false);
        } else {
            $this->services->has('Foo\Table')->willReturn(false);
        }

        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function validConfig()
    {
        return [
            'table_service' => [['table_service' => 'foobartable'], 'foobartable'],
            'virtual_table' => [[], 'Foo\Table'],
        ];
    }

    /**
     * @dataProvider validConfig
     */
    public function testWillCreateServiceIfDbConnectedSegmentIsValid($configForDbConnected, $tableServiceName)
    {
        $config = ['zf-apigility' => [
            'db-connected' => [
                'Foo' => $configForDbConnected,
            ],
        ]];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);
        $this->services->has($tableServiceName)->willReturn(true);

        $this->assertTrue($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsResourceBasedOnConfiguration($configForDbConnected, $tableServiceName)
    {
        $tableGateway = $this->prophesize(TableGateway::class)->reveal();
        $this->services->has($tableServiceName)->willReturn(true);
        $this->services->get($tableServiceName)->willReturn($tableGateway);

        $config = ['zf-apigility' => [
            'db-connected' => [
                'Foo' => $configForDbConnected,
            ],
        ]];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);

        $resource = $this->factory->__invoke($this->services->reveal(), 'Foo');
        $this->assertInstanceOf(DbConnectedResource::class, $resource);
    }
}
