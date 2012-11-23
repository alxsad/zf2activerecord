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

/**
 * Zf2ActiveRecord interface
 *
 * @category Zf2ActiveRecord
 */
interface ActiveRecordInterface
{
    /**
     * Save current record
     *
     * @return int
     */
    public function save ();

    /**
     * Delete current record
     *
     * @return int
     */
    public function delete ();

    /**
     * Create a new record
     *
     * @param array $data
     * @return ActiveRecordInterface
     */
    public function create (array $data = array());

    /**
     * Default finder
     * 
     * @param Where|\Closure|string|array $where
     * @return array
     */
    public function find ($where = null);

    /**
     * Fetch record by primary key
     *
     * @param array|string|int $pk
     * @return ActiveRecordInterface|null
     */
    public function findByPk ($pk);
}