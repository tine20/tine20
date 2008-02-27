<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the felamimail application
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Felamimail_Http extends Tinebase_Application_Http_Abstract
{
    protected $_appname = 'Felamimail';
    
    public function getInitialMainScreenData()
    {
        return array('initialTree' => Felamimail_Json::getInitialTree());
    }
}