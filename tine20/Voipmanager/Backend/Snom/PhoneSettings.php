<?php

/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: PhoneSettings.php 4616 2008-09-27 04:43:39Z l.kneschke@metaways.de $
 *
 */
 

/**
 * backend to handle Snom PhoneSetting
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_PhoneSettings extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     * 
     * @param Zend_Db_Adapter_Abstract $_db
     */
    public function __construct($_db = NULL)
    {
        parent::__construct(SQL_TABLE_PREFIX . 'snom_phone_settings', 'Voipmanager_Model_SnomPhoneSettings', $_db);
    }
}
