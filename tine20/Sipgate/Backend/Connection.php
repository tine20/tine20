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
 * @package  Voipmanager
 */
class Sipgate_Backend_Connection extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'sipgate_connection';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Sipgate_Model_Connection';
}
