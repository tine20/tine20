<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Task Pagination Class
 * @package Tasks
 */
class Tasks_Model_Pagination extends Tinebase_Model_Pagination
{
    /**
     * Appends pagination statements to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendPaginationSql($_select)
    {
        if ($this->isValid()) {
            if (!empty($this->sort) && !empty($this->dir) && $this->sort == 'due'){
                $dir = $this->dir == 'ASC' ? 'DESC' : 'ASC';
                $_select->order('is_due' . ' ' . $dir);
            }
        }
        
        parent::appendPaginationSql($_select);
    }
}
