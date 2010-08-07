<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 */


/**
 * backend for Courses
 *
 * @package     Courses
 * @subpackage  Backend
 */
class Courses_Backend_Course extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'courses';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Courses_Model_Course';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = false;
}
