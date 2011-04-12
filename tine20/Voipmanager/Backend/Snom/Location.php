<?php

/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend to handle Snom location
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Location extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'snom_location';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Voipmanager_Model_Snom_Location';
}
