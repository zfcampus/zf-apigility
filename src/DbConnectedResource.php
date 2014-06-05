<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility;

use Zend\Db\TableGateway\TableGatewayInterface as TableGateway;
use Zend\Paginator\Adapter\DbTableGateway as TableGatewayPaginator;
use ZF\Rest\AbstractResourceListener;

class DbConnectedResource extends AbstractResourceListener
{
    protected $collectionClass;

    protected $identifierName;

    protected $table;

    public function __construct(TableGateway $table, $identifierName, $collectionClass)
    {
        $this->table           = $table;
        $this->identifierName  = $identifierName;
        $this->collectionClass = $collectionClass;
    }

    public function create($data)
    {
        $data = $this->retrieveData($data);
        $this->table->insert($data);
        $id = $this->table->getLastInsertValue();
        return $this->fetch($id);
    }

    public function update($id, $data)
    {
        $data = $this->retrieveData($data);
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

    /**
     * Retrieve data
     *
     * Retrieve data from composed input filter, if any; if none, cast the data
     * passed to the method to an array.
     *
     * @param mixed $data
     * @return array
     */
    protected function retrieveData($data)
    {
        $filter = $this->getInputFilter();
        if (null !== $filter) {
            return $filter->getValues();
        }

        return (array) $data;
    }
}
