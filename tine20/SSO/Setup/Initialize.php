<?php
/**
 * Tine 2.0
  * 
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for SSO initialization
 *
 */
class SSO_Setup_Initialize extends Setup_Initialize
{
    /**
     * create group lists
     */
    protected function _initializeWebfinger()
    {
        $webfingerHandler = Tinebase_Config::getInstance()->{Tinebase_Config::WEBFINGER_REL_HANDLER};
        $webfingerHandler[SSO_Controller::WEBFINGER_REL] = [SSO_Controller::class, 'webfingerHandler'];
        Tinebase_Config::getInstance()->{Tinebase_Config::WEBFINGER_REL_HANDLER} = $webfingerHandler;
    }
}
