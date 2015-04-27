<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * sql backend class for Expressomail Accounts
 *
 * @package     Expressomail
 */
class Expressomail_Backend_Account extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'expressomail_account';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Expressomail_Model_Account';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
}
