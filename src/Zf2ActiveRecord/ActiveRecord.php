<?php
/**
 * Zf2ActiveRecord
 *
 * @link http://github.com/alxsad/zf2activerecord for the canonical source repository
 * @copyright Copyright (c) 2012 Alex Davidovich <alxsad@gmail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @category Zf2ActiveRecord
 */

namespace Zf2ActiveRecord;

use Zend\Db\Adapter\Adapter;

/**
 * Zf2ActiveRecord default class
 *
 * @category Zf2ActiveRecord
 */
class ActiveRecord extends AbstractActiveRecord
{
    /**
     * Constructor
     *
     * @return EventManagerInterface
     */
    public function __construct (Adapter $adapter, array $options = array())
    {
        $this->setAdapter($adapter);
        if (array_key_exists('primaryKey', $options)) {
            $this->setPrimaryKey($options['primaryKey']);
        }
        if (array_key_exists('tableName', $options)) {
            $this->setTableName($options['tableName']);
        }
    }
}