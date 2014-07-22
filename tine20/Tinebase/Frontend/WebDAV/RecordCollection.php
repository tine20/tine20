<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class to handle WebDAV record collection  tree
 *
 * @package     Tinebase
 * @subpackage  Frontend
 */
class Tinebase_Frontend_WebDAV_RecordCollection extends Tinebase_WebDav_Collection_Abstract
{
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($_name)
    {
        return new Tinebase_Frontend_WebDAV_Record($this->_path . '/' . $_name);
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre\DAV\INode[]
     */
    function getChildren()
    {
        return array();
    }
}
