<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 *
 */

/**
 * Server Interface for plugins
 *
 * @package     Tinebase
 * @subpackage  Server
 */
interface Tinebase_Server_Plugin_Interface
{
    /**
     * return server class of $request matches specific criteria
     * 
     * @param Zend\Http\Request $request
     * @return Tinebase_Server_Interface
     */
    public static function getServer(\Zend\Http\Request $request);
}
