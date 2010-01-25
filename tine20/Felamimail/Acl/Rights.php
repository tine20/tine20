<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * this class handles the rights for the Felamimail application
 * 
 * Felamimail has the following rights
 * - MANAGE_ACCOUNTS
 * 
 * @see         Tinebase_Acl_Rights_Abstract
 * @package     Felamimail
 * @subpackage  Acl
 */
class Felamimail_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage (update/delete) email accounts
     *
     * @staticvar string
     */
    const MANAGE_ACCOUNTS = 'manage_accounts';
    
    /**
     * the right to add new email accounts
     *
     * @staticvar string
     */
    const ADD_ACCOUNTS = 'add_accounts';
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Acl_Rights
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
     * @return Felamimail_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Acl_Rights;
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
        
        $addRights = array(
            self::MANAGE_ACCOUNTS,
            self::ADD_ACCOUNTS,
        );
        $allRights = array_merge($allRights, $addRights);
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    private function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Felamimail');
        
        $rightDescriptions = array(
            self::MANAGE_ACCOUNTS => array(
                'text'          => $translate->_('manage email accounts'),
                'description'   => $translate->_('Edit and delete email accounts'),
            ),
            self::ADD_ACCOUNTS => array(
                'text'          => $translate->_('Add email accounts'),
                'description'   => $translate->_('Create new email accounts'),
            ),
        );
        
        return $rightDescriptions;
    }

    /**
     * get right description
     * 
     * @param   string right
     * @return  array with text + description
     * 
     * @todo    remove that when we have static late binding :)
     */
    public function getRightDescription($_right)
    {        
        $result = parent::getRightDescription($_right);
        
        $rightDescriptions = self::getTranslatedRightDescriptions();
        
        if ( isset($rightDescriptions[$_right]) ) {
            $result = $rightDescriptions[$_right];
        }

        return $result;
    }
}
