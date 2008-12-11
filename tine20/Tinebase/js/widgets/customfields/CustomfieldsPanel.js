/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.customfields');

/**
 * Customfields panel
 */
Tine.widgets.customfields.CustomfieldsPanel = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {Tine.Tinebase.Record} record
     * the record this customfields panel is for
     */
    record: null,
    
    //private
    layout: 'fit',
    border: true,
    frame: true,
    
    initComponent: function() {
        this.title = _('Custom Fields');
        
        var cfd = this.getCustomFieldDefinition();
        if (cfd) {
            this.items = [new Tine.widgets.customfields.CustomfieldsPanelFormField()];
        } else {
            this.html = '<div class="x-grid-empty">' + _('There are no custom fields yet') + "</div>";
        }
        
        Tine.widgets.customfields.CustomfieldsPanel.superclass.initComponent.call(this);
    },
    
    getCustomFieldDefinition: function() {
        
        if (this.record && typeof(this.record.getMeta) == 'function') {
            var appName = this.record.getMeta('appName');
            //Tine[appName].registry.get()
            return true;
        } else {
            return false;
        }
    }
});

/**
 * @private Helper class to have customfields processing in the standard form/record cycle
 */
Tine.widgets.customfields.CustomfieldsPanelFormField = Ext.extend(Ext.form.Field, {
    name: 'customfields',
    hidden: true,
    labelSeparator: '',
    /**
     * @private
     *
    initComponent: function() {
        Tine.widgets.customfields.CustomfieldsPanelFormField.superclass.initComponent.call(this);
        //this.hide();
    },*/
    
    /**
     * returns tags data of the current record
     */
    getValue: function() {
        var value = [];
        this.recordTagsStore.each(function(tag){
            if(tag.id.length > 5) {
                //if we have a valid id we just return the id
                value.push(tag.id);
            } else {
                //it's a new tag and will be saved on the fly
                value.push(tag.data);
            }
        });
        return value;
    },
    
    /**
     * sets tags from an array of tag data objects (not records)
     */
    setValue: function(value){
        this.recordTagsStore.loadData(value);
    }

});