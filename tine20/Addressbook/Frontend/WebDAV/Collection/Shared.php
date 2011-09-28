<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle generic folders in WebDAV tree
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
class Addressbook_Frontend_WebDAV_Collection_Shared extends Tinebase_WebDav_Collection_Shared_Abstract
{
    protected $_applicationName = 'Addressbook';
    
    protected $_model = 'Contact';    
}
