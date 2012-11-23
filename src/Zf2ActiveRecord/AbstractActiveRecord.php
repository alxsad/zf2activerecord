<?php
/**
 * Zf2ActiveRecord
 *
 * @link http://github.com/alxsad/Zf2ActiveRecord for the canonical source repository
 * @copyright Copyright (c) 2012 Alex Davidovich <alxsad@gmail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @category Zf2ActiveRecord
 */

namespace Zf2ActiveRecord;

use Zend\Stdlib\ArraySerializableInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Update;

/**
 * Zf2ActiveRecord abstract class
 *
 * @category Zf2ActiveRecord
 */
abstract class AbstractActiveRecord implements ArraySerializableInterface,
    ActiveRecordInterface, EventManagerAwareInterface
{
    /**
     * @var string|TableIdentifier
     */
    protected $tableName;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var Sql
     */
    protected $sql;

    /**
     * @var array
     */
    protected $primaryKey;

    /**
     * @var array
     */
    protected $primaryData;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * Event manager instance
     *
     * @var EventManagerInterface
     */
    protected $eventManager = null;

    /**
     * Id for event manager
     *
     * @var string
     */
    protected $eventIdentifier = null;

    /**
     * Retrieve the event manager
     *
     * @return EventManagerInterface
     */
    public function getEventManager ()
    {
        if (!$this->eventManager) {
            $this->setEventManager(new EventManager());
        }
        return $this->eventManager;
    }

    /**
     * Inject an EventManager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return AbstractService
     */
    public function setEventManager (EventManagerInterface $eventManager)
    {
        $eventManager->setIdentifiers(array(
            __CLASS__,
            get_called_class(),
            $this->eventIdentifier,
            substr(get_called_class(), 0, strpos(get_called_class(), '\\'))
        ));
        $this->eventManager = $eventManager;
        return $this;
    }

    /**
     * Getter for table name
     *
     * @return string|TableIdentifier
     */
    public function getTableName ()
    {
        if (!$this->tableName) {
            $this->setTableName(strtolower(
                array_pop(explode('\\', get_called_class()))
            ));
        }
        return $this->tableName;
    }

    /**
     * Setter for table name
     * 
     * @param string|TableIdentifier $tableName
     * @return AbstractActiveRecord
     */
    public function setTableName ($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Getter for adapter
     *
     * @throws Exception\RuntimeException
     * @return Adapter
     */
    public function getAdapter ()
    {
        if (!$this->adapter) {
            throw new Exception\RuntimeException('
                Database adapter should be init before using
            ');
        }
        return $this->adapter;
    }

    /**
     * Setter for adapter
     * 
     * @param Adapter $adapter
     * @return AbstractActiveRecord
     */
    public function setAdapter (Adapter $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Getter for sql
     * 
     * @return Sql
     */
    public function getSql ()
    {
        if (!$this->sql) {
            $this->setSql(new Sql($this->getAdapter(), $this->getTableName()));
        }
        return $this->sql;
    }

    /**
     * Setter for sql
     * 
     * @param Sql $sql
     * @return AbstractActiveRecord
     */
    public function setSql (Sql $sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * Getter for primary key
     * 
     * @return array
     */
    public function getPrimaryKey ()
    {
        if (!$this->primaryKey) {
            $this->setPrimaryKey(array('id'));
        }
        return (array) $this->primaryKey;
    }

    /**
     * Setter for primary key
     * 
     * @param string|array $primaryKey
     * @return AbstractActiveRecord
     */
    public function setPrimaryKey ($primaryKey)
    {
        $this->primaryKey = (array) $primaryKey;
        return $this;
    }

    /**
     * New flag
     * 
     * @return bool
     */
    public function isNew ()
    {
        return null === $this->primaryData;
    }

    /**
     * Default finder
     *
     * @param Where|\Closure|string|array $where
     * @return array
     */
    public function find ($where = null)
    {
        $select = $this->getSql()->select();
        if ($where instanceof \Closure) {
            $where($select);
        } elseif (null !== $where) {
            $select->where($where);
        }
        $events = $this->getEventManager();
        $events->trigger(sprintf('%s.pre', __FUNCTION__), $this, array(
            'select' => $select
        ));
        $result = $this->executeSelect($select);
        $events->trigger(sprintf('%s.post', __FUNCTION__), $this, array(
            'select' => $select,
            'result' => $result,
        ));
        return $result;
    }

    /**
     * Fetch record by primary key
     *
     * @param array|string|int $pk
     * @return ActiveRecordInterface|null
     */
    public function findByPk ($pk)
    {
        $where = $this->createPrimarySqlCondition((array) $pk);
        $select = $this->getSql()->select()->where($where);
        $result = $this->executeSelect($select);
        return $result ? array_shift($result) : null;
    }

    /**
     * Delete current record
     *
     * @return int
     */
    public function delete ()
    {
        if ($this->isNew()) {
            throw new Exception\RuntimeException('This record is not exixts');
        }        
        $events = $this->getEventManager();
        $events->trigger(sprintf('%s.pre', __FUNCTION__), $this);
        $delete = $this->getSql()->delete()->where(
            $this->createPrimarySqlCondition()
        );
        $affectedRows = $this->executeDelete($delete);
        $events->trigger(sprintf('%s.post', __FUNCTION__), $this);
        return $affectedRows;
    }

    /**
     * Save current record
     *
     * @return int
     */
    public function save ()
    {
        $events = $this->getEventManager();
        $events->trigger(sprintf('%s.pre', __FUNCTION__), $this);
        $data = $this->getArrayCopy();
        if ($this->isNew()) {
            $insert = $this->getSql()->insert()->values($data);
            $affectedRows = $this->executeInsert($insert);
        } else {
            $where = $this->createPrimarySqlCondition();
            $update = $this->getSql()->update()->set($data)->where($where);
            $affectedRows = $this->executeUpdate($update);
        }
        $events->trigger(sprintf('%s.post', __FUNCTION__), $this);
        return $affectedRows;
    }

    /**
     * Create a new record
     * 
     * @param array $data
     * @return AbstractActiveRecord
     */
    public function create (array $data = array())
    {
        $record = clone $this;
        $record->exchangeArray($data);
        return $record;
    }

    /**
     * Execute finder
     *
     * @param Select $select
     * @return array
     */
    protected function executeSelect (Select $select)
    {
        $return = array();
        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $rows = $statement->execute();

        foreach ($rows as $row) {
            $record = clone $this;
            $record->applyPrimaryData($row)
                   ->exchangeArray($row);
            $return[] = $record;
        }

        return $return;
    }

    /**
     * Execute delete
     *
     * @param Delete $select
     * @return int
     */
    protected function executeDelete (Delete $delete)
    {
        $statement = $this->getSql()->prepareStatementForSqlObject($delete);
        $result = $statement->exexute();
        $affectedRows = $result->getAffectedRows();
        if (1 == $affectedRows) {
            $this->primaryData = null;
        }
        return $affectedRows;
    }

    /**
     * Execute insert
     *
     * @param Insert $insert
     * @return int
     */
    protected function executeInsert (Insert $insert)
    {
        $statement = $this->getSql()->prepareStatementForSqlObject($insert);
        $result = $statement->execute();
        $generatedValue = $result->getGeneratedValue();
        $primaryKey = $this->getPrimaryKey();

        $primaryData = $generatedValue && 1 == count($primaryKey)
                     ? array($primaryKey[0] => $generatedValue)
                     : $this->getArrayCopy();

        $this->applyPrimaryData($primaryData);

        return $result->getAffectedRows();
    }

    /**
     * Execute update
     *
     * @param Update $update
     * @return int
     */
    protected function executeUpdate (Update $update)
    {
        $statement = $this->getSql()->prepareStatementForSqlObject($update);
        $result = $statement->execute();
        $this->applyPrimaryData($this->getArrayCopy());
        return $result->getAffectedRows();
    }

    /**
     * Create where condition for sql
     *
     * @param array $pk
     * @return array
     */
    protected function createPrimarySqlCondition (array $pk = null)
    {
        $where = array();
        foreach ($this->getPrimaryKey() as $key => $column) {
            $where[$column] = $pk ? $pk[$key] : $this->primaryData[$column];
        }
        return $where;
    }

    /**
     * Apply primary data
     * 
     * @param array $data
     * @throws Exception\RuntimeException
     * @return AbstractActiveRecord
     */
    protected function applyPrimaryData (array $data)
    {
        $this->primaryData = array();
        foreach ($this->getPrimaryKey() as $column) {
            if (!isset($data[$column])) {
                throw new Exception\RuntimeException("$column was not found");
            }
            $this->primaryData[$column] = $data[$column];
        }
        return $this;
    }

    /**
     * Override cloning
     *
     * @return AbstractActiveRecord
     */
    public function __clone ()
    {
        $this->primaryData = null;
        $this->sql = null;
    }

    /**
     * Exchange internal values from provided array
     *
     * @param  array $array
     * @return void
     */
    public function exchangeArray (array $array)
    {
        $this->data = $array;
    }

    /**
     * Return an array representation of the object
     *
     * @return array
     */
    public function getArrayCopy ()
    {
        return $this->data;
    }
}