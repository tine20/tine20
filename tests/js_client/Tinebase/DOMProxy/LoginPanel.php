<?php

class Tinebase_DOMProxy_LoginPanel extends Ext_form_FormPanel
{
    public function __construct($_parent, $_expression, $_selenium = NULL)
    {
        $expression = "window.Tine.loginPanel.getLoginPanel()";
        parent::__construct($_parent, $expression, $_selenium);
    }
    
    public function pressLogin()
    {
        $label = $this->getSelenium()->getEval("window._('Login')");
        
        $proxy = new Ext_Button($this, $label);
        $proxy->click();
    }
    
    /*
    public function setLanguage()
    {
        
    }
    */
}