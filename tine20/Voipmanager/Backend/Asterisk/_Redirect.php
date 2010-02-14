<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */


/**
 * Asterisk redirects sql backend
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Asterisk_Redirect extends Tinebase_Backend_Sql_Abstract
{    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'asterisk_redirects';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Voipmanager_Model_Asterisk_Redirect';
}
