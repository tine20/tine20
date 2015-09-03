<?php
/**
 * Tine 2.0
 *
 * MAIN controller for expressodriver, does event and container handling
 *
 * @package     Expressodriver
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * main controller for Expressodriver
 *
 * @package     Expressodriver
 * @subpackage  Controller
 */
class Expressodriver_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Expressodriver_Model_Node';

    /**
     * holds the instance of the singleton
     *
     * @var Filemamager_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor (get current user)
     */
    private function __construct()
    {
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Expressodriver_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressodriver_Controller;
        }

        return self::$_instance;
    }

    /**
     * event handler function
     *
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     *
     * @todo    write test
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));

        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                break;
            case 'Admin_Event_DeleteAccount':
                break;
        }
    }

    /**
     * get expressodriver settings
     *
     * @param bool $_resolve
     * @return array
     */
    public function getConfigSettings($_resolve = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fetching Expressodriver Settings ...');

        $defaults = array(
            'default' => array(
                'useCache' => true,
                'cacheLifetime' => 86400, // one day
            ),
            'adapters' => array(),
        );
        $result = Expressodriver_Config::getInstance()->get('expressodriverSettings', new Tinebase_Config_Struct($defaults))->toArray();

        return $result;
    }

    /**
     * save expressodriver settings
     *
     * @param array $_settings
     * @return Crm_Model_Config
     *
     */
    public function saveConfigSettings($_settings)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Updating Crm Settings: ' . print_r($_settings, TRUE));

        $_settings = array(
            'default' => $_settings['default'],
            'adapters' => $_settings['adapters'],
        );

        Expressodriver_Config::getInstance()->set('expressodriverSettings', $_settings);

        return $this->getConfigSettings();
    }
}
