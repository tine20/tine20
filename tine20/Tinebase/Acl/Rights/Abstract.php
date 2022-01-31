<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
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
     * the right to see an application in the FE
     *
     * @staticvar string
     */
    const MAINSCREEN = 'mainscreen';
    
    /**
     * the right to manage shared folders of an application
     *
     * @staticvar string
     */
    const MANAGE_SHARED_FOLDERS = 'manage_shared_folders';
    
    /**
     * the right to manage shared favorites of an application
     * @deprecated use each application rights class to specify the model, too
     * (e.g. MANAGE_SHARED_LEAD_FAVORITES for crm lead in Tinebase_Acl_Rights_Abstract)
     * @staticvar string
     */
    const MANAGE_SHARED_FAVORITES = 'manage_shared_favorites';
    
    /**
     * the right to use personal tags in an application
     * 
     * @staticvar string
     */
    const USE_PERSONAL_TAGS = 'use_personal_tags';
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        return [self::RUN, self::MAINSCREEN, self::ADMIN];
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        /** @var Zend_Translate_Adapter $translate */
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
            self::MAINSCREEN            => array(
                'text'          => $translate->_('Use Mainscreen'),
                'description'   => $translate->_('App mainscreen is available in UI'),
            ),
            self::USE_PERSONAL_TAGS     => array(
                'text'          => $translate->_('Personal tags'),
                'description'   => $translate->_('Use and see personal tags'),
            ),
        );
        
        return $rightDescriptions;
    }
}
