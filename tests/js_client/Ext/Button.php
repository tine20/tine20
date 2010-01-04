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
 * proxy for Ext.Button
 */
class Ext_Button extends Ext_Component
{
    public function __construct($_parent, $_text)
    {
        parent::__construct($_parent, ".findBy(function(component) {" 
            . "return (component.isXType && component.isXType('button'))"
            . "&& (component.text && component.text == '" . $_text . "')"
            . "})[0]");
    }
    
    public function isEnabled()
    {
        return ( ! $this->getSelenium()->getEval($this->getExpression() . ".disabled"));
    }

    public function click()
    {
        //log("click()");

        $this->waitForEvalTrue(".disabled == false");

        $this->getSelenium()->click($this->getXPath());
    }
}