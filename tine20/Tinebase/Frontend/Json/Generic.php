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
 * generic json frontend class for apps that do not define their own json frontends
 *
 * @package     Tinebase
 * @subpackage  Application
 */
class Tinebase_Frontend_Json_Generic extends Tinebase_Frontend_Json_Abstract
{
    /**
     * Tinebase_Frontend_Json_Generic constructor.
     *
     * @param $applicationName
     */
    public function __construct($applicationName)
    {
        $this->_applicationName = $applicationName;
    }
}
