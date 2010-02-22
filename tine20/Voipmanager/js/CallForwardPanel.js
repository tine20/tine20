/*
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Voipmanager');

/**
 * Account Picker GridPanel
 * 
 * @namespace   Tine.Voipmanager
 * @class       Tine.Voipmanager.CallForwardPanel
 * @extends     Ext.form.FormPanel
 * 
 * <p>Call Forward Form Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Voipmanager.CallForwardPanel
 */
Tine.Voipmanager.CallForwardPanel = Ext.extend(Ext.form.FormPanel, {

    /**
     * @type Tine.Tinebase.data.Record
     */
    record: null,
    
    /**
     * @private
     */
    initComponent: function() {
        
        this.items = this.getFormItems();
        
        Tine.Voipmanager.CallForwardPanel.superclass.initComponent.call(this);
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * TODO add form items
     */
    getFormItems: function() { 
    },
    
    /**
     * 
     * @param {Object} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        // TODO set form
    },

    /**
     * 
     * @param {Object} record
     */
    onRecordUpdate: function(record) {
        // TODO get form
    }
    
});

