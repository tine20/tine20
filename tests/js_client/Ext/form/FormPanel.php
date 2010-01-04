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
 * proxy for Ext.form.FormPanel
 */
class Ext_form_FormPanel extends Ext_Panel
{
    
    public function findField($_fieldName)
    {
        return new Ext_Component($this, ".getForm().findField('$_fieldName')");
    }
    
    public function setField($_fieldName, $_value)
    {
        $proxy = $this->findField($_fieldName);
        $this->getSelenium()->type($proxy->getXPath(), $_value);
    }
}