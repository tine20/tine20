<?php
/**
 * Tine 2.0 tree fake controller
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * tree fake controller, so that Tinebase_Core::getApplicationInstance('Tinebase_Model_Tree_Node') will return this
 * @see Tinebase_Tree_Node::getInstance()
 *
 * @package     Tinebase
 */
class Tinebase_Tree implements Tinebase_Controller_Interface
{
    /**
     * holds the _instance of the singleton
     *
     * @var Tinebase_Tree
     */
    private static $_instance = NULL;

    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone()
    {}

    /**
     * the constructor
     *
     * disabled. use the singleton
     */
    private function __construct()
    {}

    /**
     * the singleton pattern
     *
     * @return Tinebase_Tree
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Tree;
        }

        return self::$_instance;
    }

    public function get($_id)
    {
        return Filemanager_Controller_Node::getInstance()->get($_id);
    }
}