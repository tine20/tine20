<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend to handle Snom software
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Software extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'snom_software';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Voipmanager_Model_Snom_Software';
}
