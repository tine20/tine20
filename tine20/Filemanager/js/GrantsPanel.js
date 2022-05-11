/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

Tine.Filemanager.GrantsPanel = Ext.extend(Ext.Panel, {

    /**
     * @cfg {Tine.widgets.dialog.EditDialog}
     */
    editDialog: null,

    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,

    requiredGrant: 'editGrant',
    layout: 'fit',
    border: false,

    initComponent: function() {
        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
        this.recordClass = Tine.Filemanager.Model.Node;
        this.title = this.title || this.app.i18n._('Grants');

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        this.hasOwnGrantsCheckbox = new Ext.form.Checkbox({
            readOnly: true,
            boxLabel: this.app.i18n._('This folder has its own grants'),
            listeners: {scope: this, check: this.onOwnGrantsCheck}
        });
        this.inheritAclNodePathInfo = new Ext.Component({
            style: {
                marginTop: '10px',
                width: 'auto',
                minHeight: '15px'
            },
        });

        this.hasOwnRightsDescription = new Ext.form.Label({
            text: this.app.i18n._("Grants of a folder also apply recursively to all sub folders unless they have their own grants.")
        });
        this.pinProtectionCheckbox = new Ext.form.Checkbox({
            readOnly: true,
            hidden: ! Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName).length,
            boxLabel: this.app.i18n._('This folder is part of the data safe')
        });
        this.pinProtectionDescription = new Ext.form.Label({
            text: this.app.i18n._("If the data safe is activated, this folder and its contents can only be accessed when the data safe is open.")
        });
        this.grantsGrid = new Tine.widgets.container.GrantsGrid({
            app: this.app,
            alwaysShowAdminGrant: true,
            readOnly: true,
            flex: 1,
            grantContainer: {
                application_id: this.app.id,
                model: 'Filemanager_Model_Node',
            },
        });

        this.items = [{
            layout: 'vbox',
            pack: 'start',
            border: false,
            items: [{
                layout: 'form',
                frame: true,
                hideLabels: true,
                width: '100%',
                items: [
                    this.hasOwnGrantsCheckbox,
                    this.hasOwnRightsDescription,
                    this.pinProtectionCheckbox,
                    this.pinProtectionDescription,
                    this.inheritAclNodePathInfo,
                ]},
                this.grantsGrid
            ]
        }];

        this.supr().initComponent.call(this);
    },

    onOwnGrantsCheck: function(cb, checked) {
        this.grantsGrid.setReadOnly(!checked);
        
        if (this.editDialog) {
            if (checked) {
                this.grantsGrid.getStore().loadData({results: this.aclNodeOwnGrants});
            } else {
                const grants = this.inheriteAclNodeRecord ? this.inheriteAclNodeRecord.get('grants') : this.aclNodeOwnGrants;
                this.grantsGrid.getStore().loadData({results: grants});
            }
            this.renderAclNodePathInfo(!checked);
        }
    },

    onRecordLoad: async function (editDialog, record, ticketFn) {
        const path = record.get('path');
        const evalGrants = editDialog.evalGrants;
        const hasOwnGrants = record.get('acl_node') && record.get('acl_node') === record.id;
        const hasRequiredGrant = !evalGrants || _.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant);
        const ownGrantsReadOnly = (record.get('type') !== 'folder' ||
            !_.get(record, 'data.account_grants.adminGrant', false) ||
            path.match(/^\/personal(\/[^/]+){0,2}\/$/) ||
            path.match(/^\/shared(\/[^/]+){0,1}\/$/)) ?? false;
    
        const pinProtectionReadOnly = record.get('type') !== 'folder' || !record.data?.account_grants?.adminGrant;
        this.aclNodeOwnGrants = record.get('grants');
        this.hasOwnGrantsCheckbox.setValue(hasOwnGrants);
        this.hasOwnGrantsCheckbox.setReadOnly(ownGrantsReadOnly);
        this.pinProtectionCheckbox.setValue(!!record.get('pin_protected_node'));
        this.pinProtectionCheckbox.setReadOnly(pinProtectionReadOnly);
    
        this.grantsGrid.useGrant('admin', !!String(record.get('path')).match(/^\/shared/));
        this.grantsGrid.getStore().loadData({results: record.data.grants});
        
        let defaultInheritAclNode = !hasOwnGrants ? record.get('acl_node') : null;
        // always prepare parent's acl node
        if (record?.data?.parent_id) {
            await Tine.Filemanager.getNode(record?.data?.parent_id).then((result) => {
                this.parentNode = Tine.Tinebase.data.Record.setFromJson(result, Tine.Filemanager.Model.Node);
            });
        }
        
        if (hasOwnGrants || ! record.get('acl_node')) {
            defaultInheritAclNode = this.parentNode.get('acl_node');
        } 
        
        if(defaultInheritAclNode) {
            await Tine.Filemanager.getNode(defaultInheritAclNode).then((result) => {
                this.inheriteAclNodeRecord = Tine.Tinebase.data.Record.setFromJson(result, Tine.Filemanager.Model.Node);
            });
            if (!hasOwnGrants) {
                this.renderAclNodePathInfo(true);
            }
        }
        
        this.setReadOnly(!hasRequiredGrant);
        this.grantsGrid.setReadOnly(!hasOwnGrants || !hasRequiredGrant);
    },
    
    renderAclNodePathInfo(checked) {
        let text = '&nbsp;';
        if (checked && this.inheriteAclNodeRecord) {
            const location = {
                model: "Filemanager_Model_Node",
                record_id: this.inheriteAclNodeRecord.data,
                record_title: this.inheriteAclNodeRecord.get('path'),
                type: "node"
            }
            text = this.app.formatMessage('Grants are inherited from {locationsHtml} as follows', {
                locationsHtml: Tine.Felamimail.MessageFileAction.getFileLocationText([location], ', ')
            });
        }
        
        this.inheritAclNodePathInfo.show();
        
        if (this.inheritAclNodePathInfo.rendered) {
            this.inheritAclNodePathInfo.update(text);
        } else {
            this.inheritAclNodePathInfo.html = text;
        }
        return text;
    },

    // grants-grid only - checkboxes have own state
    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        this.grantsGrid.setReadOnly(readOnly);
    },

    onRecordUpdate: function(editDialog, record) {
        const acl_node = this.hasOwnGrantsCheckbox.getValue() ? record.id : null;
        const pin_protected_node = !!this.pinProtectionCheckbox.getValue();
        let grants = [];
    
        this.grantsGrid.getStore().each(function(r) {grants.push(r.data)});

        if (acl_node) {
            this.aclNodeOwnGrants = grants;
        }
        
        record.set('acl_node', acl_node);
        record.set('grants', grants);
        record.set('pin_protected_node', pin_protected_node ? acl_node : null);
    }
});
