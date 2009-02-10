<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */


/**
 * Asterisk voicemail sql backend
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Asterisk_Voicemail extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     * 
     * @param Zend_Db_Adapter_Abstract $_db
     */
    public function __construct($_db = NULL)
    {
        parent::__construct(SQL_TABLE_PREFIX . 'asterisk_voicemail', 'Voipmanager_Model_Asterisk_Voicemail', $_db);
    }
}
