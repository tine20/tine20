/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
    accountId: null,
    
    initComponent: function() {
        const accountId = this.accountId ?? Tine.Tinebase.registry.get('currentAccount').accountId;
        this.updateUserProfile(accountId);
        this.items = [];
        
        this.supr().initComponent.apply(this, arguments);
    },
    
    updateUserProfile: function (accountId) {
        Tine.Tinebase.getUserProfile(accountId, this.onData.createDelegate(this));
    },
    
    onData: function(userProfileInfo) {
        if (!userProfileInfo?.userProfile) return;
        
        const userProfile  = userProfileInfo.userProfile;
        const readableFields   = userProfileInfo.readableFields;
        const updateableFields = userProfileInfo.updateableFields;
            
        const adbI18n = new Locale.Gettext();
        adbI18n.textdomain('Addressbook');
        
        Ext.each(readableFields, function(fieldName) {
            // don't display generic fields
            const fieldDefinition = Tine.Addressbook.Model.Contact.getField(fieldName);
            
            if (! fieldDefinition) {
                return;
            }
            
            switch (fieldName) {
                default:
                    const item = new Ext.form.TextField({
                        hidden: this.genericFields.indexOf(fieldName) >= 0,
                        name: fieldName,
                        value: userProfile[fieldName],
                        fieldLabel: adbI18n._hidden(fieldDefinition.label),
                        readOnly: updateableFields.indexOf(fieldName) < 0
                    })
    
                    const idx = this.items.findIndex('name',fieldName);
                    if (idx > -1) {
                        this.remove(this.items.get(idx));
                    } 
                    
                    this.add(item);
                    break;
            }
        }, this);
        
        this.doLayout();
    }
});
