/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.Felamimail');

/**
 * folder select trigger field
 * 
 * @namespace   Tine.widgets.container
 * @class       Tine.Felamimail.FolderSelectTriggerField
 * @extends     Ext.form.ComboBox
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * TODO         make it work
 */
Tine.Felamimail.FolderSelectTriggerField = Ext.extend(Ext.form.TriggerField, {
    
    triggerClass: 'x-form-search-trigger',
    
    /**
     * onTriggerClick
     */
    onTriggerClick: function(e) {
        //Tine.log.debug('click');
        // TODO open ext window with (folder-)tree panel that fires event on select
        this.el.focus();
    }
});
Ext.reg('felamimailfolderselect', Tine.Felamimail.FolderSelectTriggerField);
