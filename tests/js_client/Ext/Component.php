<?php
/**
 * PHP ExtJS Selenium Proxy
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * proxy for Ext.Component
 */
class Ext_Component
{
    protected $_parent;
    protected $_selenium;
    protected $_expression;
    
    /**
     * create a proxy for an Ext.Component that is contained within an other
     * 
     * @param Ext_Component $_parent        proxy for the container Ext component
     * @param String        $_expression    JavaScript expression that evaluates this proxy's component on that of the container
     * @return void
     */
    public function __construct($_parent, $_expression, $_selenium = NULL)
    {
        $this->_parent = $_parent;
        $this->_expression = $_expression;
        $this->_selenium = $_selenium ? $_selenium : $_parent->getSelenium();
    }
    
    /**
     * returns the absolute expression that resolves this proxy's Ext component
     * 
     * @return string
     */
    public function getExpression()
    {
        return $this->_parent ? $this->_parent->getExpression() . $this->_expression : $this->_expression;
    }
    
    /**
     * returns the ID of the Ext component, found with the proxy's JS expression. This is overridden in some 
     * subclasses for where the expression to get the ID varies
     * 
     * @return string
     */
    public function getId()
    {
        return $this->getSelenium()->getEval($this->getExpression() . ".getId()");
    }
    
    /**
     * returns selenium object
     * 
     * @return selenium
     */
    public function getSelenium()
    {
        return $this->_selenium;
    }
    
    /**
     * returns an XPath to the Ext component, which contains the ID provided by getId()
     * 
     * @return string
     */
    public function getXPath()
    {
        return "//*[@id='" . $this->getId() . "']";
    }
    
    public function isVisible()
    {
        return $this->getSelenium()->getEval($this->getExpression() . ".isVisible()");
    }
    
    /**
     * Returns as soon as expression evals, else throws exception on timeout
     * 
     * @param  string $_expr
     * @return void
     */
    public function waitForEvalTrue($_expr)
    {
        $this->getSelenium()->waitForCondition($this->getExpression() . $_expr);
    }
    
    public function waitForVisible()
    {
        $this->waitForEvalTrue(".isVisible()");
    }
}