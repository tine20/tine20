<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 */


/**
 * backend for Timetracker preferences
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Timetracker_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * use tine user credentials for imap connection
     *
     */
    const TSODSEXPORTCONFIG = 'tsOdsExportConfig';

    /**
     * application
     *
     * @var string
     */
    protected $_application = 'Timetracker';    
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::TSODSEXPORTCONFIG,
        );
            
        return $allPrefs;
    }
    
    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications preferences
     */
    public function getTranslatedPreferences()
    {
        $translate = Tinebase_Translation::getTranslation($this->_application);

        $prefDescriptions = array(
            self::TSODSEXPORTCONFIG  => array(
                'label'         => $translate->_('Timesheets ODS export configuration'),
                'description'   => $translate->_('Use this configuration for the timesheet ODS export.'),
            ),
        );
        
        return $prefDescriptions;
    }
    
    /**
     * get preference defaults if no default is found in the database
     *
     * @param string $_preferenceName
     * @return Tinebase_Model_Preference
     */
    public function getPreferenceDefaults($_preferenceName, $_accountId = NULL, $_accountType=Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::TSODSEXPORTCONFIG:
                $preference->value      = 'default';
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
    
    /**
     * get special options
     *
     * @param string $_value
     * @return array
     */
    protected function _getSpecialOptions($_value)
    {
        $translate = Tinebase_Translation::getTranslation($this->_application);
        
        $result = array();
        switch($_value) {
            case self::TSODSEXPORTCONFIG:
                // get all export config labels
                $configs = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::ODSEXPORTCONFIG, 'Timetracker', array());
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . print_r($configs, TRUE));
                if (! empty($configs)) {
                    foreach($configs['timesheets'] as $key => $tsConfig) {
                        $result[] = array($key, $key);
                    }
                } else {
                    $result[] = array('default', $translate->_('default'));
                }
                break;
            default:
                $result = parent::_getSpecialOptions($_value);
        }
        
        return $result;
    }
}
