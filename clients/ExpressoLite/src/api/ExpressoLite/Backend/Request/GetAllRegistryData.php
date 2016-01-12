<?php
/**
 * Expresso Lite
 * Handler for GetAllRegistryData calls (self explanatory).
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class GetAllRegistryData extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $response = $this->jsonRpc('Tinebase.getAllRegistryData');
        $response->result->liteConfig = (object) array(
            'classicUrl' => CLASSIC_URL,
            'androidUrl' => ANDROID_URL,
            'iosUrl' => IOS_URL,
            'packageString' => PACKAGE_STRING
        );
        return $response->result;
    }

    /**
     * Allows this request to be executed even without a previously
     * estabilished TineSession.
     *
     * @return true.
     */
    public function allowAccessWithoutSession()
    {
        return true;
    }
}
