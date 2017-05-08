<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle container tree
 *
 * @package     Tinebase
 * @subpackage  Frontend
 */
class Tinebase_Frontend_WebDAV extends Tinebase_Frontend_WebDAV_Abstract
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

    /**
     * container model name
     *
     * one of: Tinebase_Model_Container | Tinebase_Model_Tree_Node
     *
     * @var string
     */
    protected $_containerModel = 'Tinebase_Model_Tree_Node';
}
