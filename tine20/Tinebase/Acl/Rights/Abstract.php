<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Abstract class for application rights
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Acl_Rights_Abstract implements Tinebase_Acl_Rights_Interface
{
    /**
     * the right to be an administrative account for an application
     *
     * @staticvar string
     */
    const ADMIN = 'admin';
        
    /**
     * the right to run an application
     *
     * @staticvar string
     */
    const RUN = 'run';
    
    /**
     * the right to manage shared folders of an application
     *
     * @staticvar string
     */
    const MANAGE_SHARED_FOLDERS = 'manage_shared_folders';
    
    /**
     * the right to manage shared favorites of an application
     * 
     * @staticvar string
     */
    const MANAGE_SHARED_FAVORITES = 'manage_shared_favorites';
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        $allRights = array ( self::RUN, self::ADMIN );
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    private function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        $rightDescriptions = array(
            self::ADMIN                 => array(
                'text'          => $translate->_('admin'),
                'description'   => $translate->_('admin right description'),
            ),
            self::RUN                   => array(
                'text'          => $translate->_('run'),
                'description'   => $translate->_('run right description'),
            ),
        );
        
        return $rightDescriptions;
    }
    
    /**
     * get right description
     * 
     * @param   string right
     * @return  array with text + description
     */
    public function getRightDescription($_right)
    {        
        $result = array(
            'text'          => $_right,
            'description'   => $_right . " right",
        );
        
        $rightDescriptions = self::getTranslatedRightDescriptions();
        
        if ( isset($rightDescriptions[$_right]) ) {
            $result = $rightDescriptions[$_right];
        }

        return $result;
    }
    
}
