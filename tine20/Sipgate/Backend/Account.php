<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * Sipgate Connection sql backend
 *
 * @package  Sipgate
 */
class Sipgate_Backend_Account extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'sipgate_account';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Sipgate_Model_Account';
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     * @var boolean
     */
    protected $_modlogActive = true;
}
