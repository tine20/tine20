/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.Tinebase');

/**
 * user profile panel
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.UserProfilePanel
 * @extends     Ext.form.FormPanel
 * @consturctor
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Tinebase.UserProfilePanel = Ext.extend(Ext.Panel, {
    genericFields: ['account_id', 'created_by', 'creation_time', 'last_modified_by',
                      'last_modified_time', 'is_deleted', 'deleted_time', 'deleted_by'],
    layout: 'form',
    border: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '95%',
        labelSeparator: ''
    },
    bodyStyle: 'padding:5px',
    
    initComponent: function() {
        Tine.Tinebase.getUserProfile(Tine.Tinebase.registry.get('currentAccount').accountId, this.onData.createDelegate(this));
        this.items = [];
        
        this.supr().initComponent.apply(this, arguments);
    },
    
    onData: function(userProfileInfo) {
        var userProfile      = userProfileInfo.userProfile,
            readableFields   = userProfileInfo.readableFields,
            updateableFields = userProfileInfo.updateableFields;
            
        var adbI18n = new Locale.Gettext();
        adbI18n.textdomain('Addressbook');
        
        Ext.each(readableFields, function(fieldName) {
            // don't display generic fields
            var fieldDefinition = Tine.Addressbook.Model.Contact.getField(fieldName);
            
            switch(fieldName) {
                default: 
                    this.add(new Ext.form.TextField({
                        hidden: this.genericFields.indexOf(fieldName) >= 0,
                        name: fieldName, 
                        value: userProfile[fieldName],
                        fieldLabel: adbI18n._hidden(fieldDefinition.label),
                        readOnly: updateableFields.indexOf(fieldName) < 0
                    }));
                    break;
            }
        }, this);
        
        this.doLayout();
    }
});