<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * event class for deleted node - fired in \Tinebase_FileSystem::deleteFileNode
 *
 * @package     Tinebase
 */
class Tinebase_Event_Observer_DeleteFileNode extends Tinebase_Event_Observer_Abstract
{
    /**
     * the node object
     *
     * @var Tinebase_Model_Tree_Node
     */
    public $observable;
}
