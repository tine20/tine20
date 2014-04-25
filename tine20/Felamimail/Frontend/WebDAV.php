<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle container tree
 *
 * @package     Felamimail
 * @subpackage  Frontend
 */
class Felamimail_Frontend_WebDAV extends Tinebase_Frontend_WebDAV_Abstract
{
    /**
     * app has personal folders
     *
     * @var string
     */
    protected $_hasPersonalFolders = false;
    
    /**
     * app has records folder
     *
     * @var string
     */
    protected $_hasRecordFolder = false;
}
