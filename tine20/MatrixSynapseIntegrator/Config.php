<?php
/**
 * Tine 2.0
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * MatrixSynapseIntegrator config class
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Config
 *
 */
class MatrixSynapseIntegrator_Config extends Tinebase_Config_Abstract
{
    const APP_NAME = 'MatrixSynapseIntegrator';

    const MATRIX_DOMAIN = 'matrixDomain';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties   = [
        self::MATRIX_DOMAIN            => [
            //_('Matrix Domain')
            self::LABEL                     => 'Matrix Domain',
            //_('Matrix Domain')
            self::DESCRIPTION               => 'Matrix Domain',
            self::TYPE                      => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::SETBYSETUPMODULE          => true,
        ],
    ];

    /**
     * holds the instance of the singleton
     *
     * @var MatrixSynapseIntegrator_Config
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __clone()
    {
    }

    /**
     * Returns instance of DFCom_Config
     *
     * @return MatrixSynapseIntegrator_Config
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
