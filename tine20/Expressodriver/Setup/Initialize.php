<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * class for Expressodriver initialization
 *
 * @package Expressodriver
 */
class Expressodriver_Setup_Initialize extends Setup_Initialize
{


    /**
     * initialize key fields
     */
    protected function _initializeKeyFields()
    {
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));

        $externalDrivers = array(
            'name' => Expressodriver_Config::EXTERNAL_DRIVERS,
            'records' => array(
                array('id' => 'webdav', 'value' => 'Webdav', 'system' => true),
                array('id' => 'owncloud', 'value' => 'Owncloud', 'system' => true),
            ),
        );

        Expressodriver_Config::getInstance()->set(Expressodriver_Config::EXTERNAL_DRIVERS, $externalDrivers);
    }
}
