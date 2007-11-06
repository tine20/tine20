<?php

/**
 * Abstract class for an EGW2.0 application with Json interface
 * Each egw application must extend this class to gain an native egw2.0 user
 * interface.
 *
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
abstract class Egwbase_Application_Json_Abstract extends Egwbase_Application_Abstract
{
    protected $_appname;
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array of filenames
     */
    public function getJsFilesToInclude()
    {
        return "{$this->_appname}/js/{$this->_appname}.js";
    }
    
    /**
     * Retruns all CSS files which must be inclued for this app
     *
     * @return array of filenames
     */
    public function getCssFilesToInclude()
    {
        return "{$this->_appname}/css/{$this->_appname}.css";
    }
    
    /**
     * Returns the structure of the initial tree for this application.
     *
     * This function returns the needed structure, to display the initial tree, 
     * after the the login. Additional tree items get loaded on demand see 
     * getSubTree.
     * 
     * @todo discuss getInitialTree vs. getInitialData
     * @param string $_location 
     *
     * @return array of Egwbase_Ext_Treenode
     */
    abstract public function getInitialTree();
    
    /**
     * returns the nodes for the dynamic tree
     * 
     * @todo discuss concept of dynamic trees 
     * @param string $node which node got selected in the UI
     * @param 
     * @param string $datatype what kind of data to search
     * @param
     * 
     * @return string json encoded array
     */
    public function getSubTree($node, $owner, $datatype, $location)
    {
        return;
    }
    
}
