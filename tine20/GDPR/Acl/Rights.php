<?php
/**
 * Tine 2.0
 * 
 * @package     GDPR
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * 
 */

/**
 * this class handles the rights for the GDPR application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * to add a new right you have to do these 3 steps:
 * - add a constant for the right
 * - add the constant to the $addRights in getAllApplicationRights() function
 * . add getText identifier in getTranslatedRightDescriptions() function
 * 
 * @package     GDPR
 * @subpackage  Acl
 */
class GDPR_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage data provenances in core data
     *
     * @var string
     */
    const MANAGE_CORE_DATA_DATA_PROVENANCE = 'manage_core_data_data_provenance';

    /**
     * the right to manage data intended purposes in core data
     *
     * @var string
     */
    const MANAGE_CORE_DATA_DATA_INTENDED_PURPOSE = 'manage_core_data_data_intended_purpose';

    /**
     * holds the instance of the singleton
     *
     * @var GDPR_Acl_Rights
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() 
    {
    }
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        
    }    
    
    /**
     * the singleton pattern
     *
     * @return self
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        
        $allRights = parent::getAllApplicationRights();
        $allRights[] = self::MANAGE_CORE_DATA_DATA_PROVENANCE;
        $allRights[] = self::MANAGE_CORE_DATA_DATA_INTENDED_PURPOSE;
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation(GDPR_Config::APP_NAME);

        $rightDescriptions = parent::getTranslatedRightDescriptions();
        $rightDescriptions[self::MANAGE_CORE_DATA_DATA_PROVENANCE] = [
            'text'          => $translate->_('Manage data provenances in CoreData'),
            'description'   => $translate->_('View, create, delete or update data provenances in CoreData application'),
        ];
        $rightDescriptions[self::MANAGE_CORE_DATA_DATA_INTENDED_PURPOSE] = [
            'text'          => $translate->_('Manage data intended purposes in CoreData'),
            'description'   =>
                $translate->_('View, create, delete or update data intended purposes in CoreData application'),
        ];
        return $rightDescriptions;
    }
}
