<?php

namespace ZF\ApiFirst;

use Zend\Db\TableGateway\TableGatewayInterface as TableGateway;
use Zend\Paginator\Adapter\DbTableGateway as TableGatewayPaginator;
use ZF\Rest\AbstractResourceListener;

class DbConnectedResource extends AbstractResourceListener
{
    protected $collectionClass;

    protected $identifierName;

    protected $table;

    public function __construct(TableGateway $table, $collectionClass, $identifierName)
    {
        $this->table           = $table;
        $this->collectionClass = $collectionClass;
        $this->identifierName  = $identifierName;
    }

    public function create($data)
    {
        $data = (array) $data;

        $this->table->insert($data);
        $id = $this->table->getLastInsertValue();
        return $this->fetch($id);
    }

    public function update($id, $data)
    {
        $data = (array) $data;

        $this->table->update($data, array($this->identifierName => $id));
        return $this->fetch($id);
    }

    public function patch($id, $data)
    {
        return $this->update($id, $data);
    }

    public function delete($id)
    {
        $item = $this->table->delete(array($this->identifierName => $id));
        return ($item > 0);
    }

    public function fetch($id)
    {
        $resultSet = $this->table->select(array($this->identifierName => $id));
        if (0 === $resultSet->count()) {
            throw new \Exception('Item not found', 404);
        }
        return $resultSet->current();
    }

    public function fetchAll($data = array())
    {
        $adapter = new TableGatewayPaginator($this->table);
        return new $this->collectionClass($adapter);
    }
}
