<?php
/**
 * @package     Expressodriver
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 */

/**
 * Expressodriver config class
 *
 * @package     Expressodriver
 * @subpackage  Config
 */
class Expressodriver_Config extends Tinebase_Config_Abstract
{
    /**
     * the const to manage external drivers of an application
     *
     * @staticvar string
     */
    const EXTERNAL_DRIVERS = 'externalDrivers';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::EXTERNAL_DRIVERS => array(
            'label'                 => 'External Drivers Available',
            'description'           => 'Possible External Drivers.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Expressodriver_Model_ExternalAdapter'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'owncloud'
        )
    );

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Expressodriver';

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {}

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __clone() {}

    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
