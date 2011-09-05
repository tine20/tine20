<?php
/**
 * Tine 2.0
 *
 * @package     Projects
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for Projects
 *
 * @package     Projects
 * @subpackage  Backend
 */
class Projects_Backend_Project extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'projects_project';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Projects_Model_Project';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
}
