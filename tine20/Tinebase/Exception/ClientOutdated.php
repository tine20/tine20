<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 *
 */

/**
 * outdated client
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_ClientOutdated extends Tinebase_Exception_ProgramFlow
{
    /**
     * @var string
     */
    protected $_appName = 'Tinebase';

    public function __construct($_message = 'Client Outdated', $_code = 426)
    {
        parent::__construct($_message, $_code);
    }

    /**
     * returns existing nodes info as array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'code'      => $this->getCode(),
            'message'   => $this->getMessage(),
            'version'   => array(
                'buildType'     => TINE20_BUILDTYPE,
                'codeName'      => TINE20_CODENAME,
                'packageString' => TINE20_PACKAGESTRING,
                'releaseTime'   => TINE20_RELEASETIME,
                'assetHash'     => Tinebase_Frontend_Http_SinglePageApplication::getAssetHash(),
            ),
        );
    }
}
