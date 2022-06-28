import validatorFactory from './tierValidatorFactory'
import getTierTypes from './tierTypes'

Promise.all([
    Tine.Tinebase.ApplicationStarter.isInitialised(),
    Tine.Tinebase.appMgr.isInitialised('Filemanager'),
    Tine.Tinebase.appMgr.isInitialised('EFile')
]).then(async (rs) => {
    const app = Tine.Tinebase.appMgr.get('EFile');
    const tierTypes = await getTierTypes();

    const createEFile = async function (tierType) {
        const action = _.get(this, 'baseAction', this);
        const path = _.get(action, 'filteredContainers[0].path');
        const tierLabel = _.find(tierTypes, {tierType: tierType}).label
        const win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 370,
            height: 150,
            modal: true,
            title: String.format(app.i18n._('Create new {0} Folder'), tierLabel),
            items: new Tine.Tinebase.dialog.Dialog({
                enableKeyEvents: true,
                layout: 'hbox',
                layoutConfig: {
                    padding: '5',
                    align: 'stretch'
                },
                items: [{
                    width: 50,
                    border: false,
                    html: '<div class="efile-create-new-dlg efile-tiertype-' + tierType + '"/>'
                }, {
                    flex: 1,
                    border: false,
                    layout: 'form',
                    items: [{
                        xtype: 'label',
                        text: String.format(app.i18n._('Name of the new {0} folder:'), tierLabel)
                    }, new Ext.form.TextField({
                        hideLabel: true,
                        anchor: '100%',
                        fieldLabel: app.i18n._('Name'),
                        name: 'name',
                        ref: '../nameField',
                        listeners: {
                            render: function(field) { field.focus.defer(300, this) },
                        }
                    }), {
                        xtype: 'label',
                        text: String.format(app.i18n._('Note: A number prefix will be automatically assigned when creating this folder.'), tierLabel)
                    }]
                }],
                getEventData: function() {return {name: this.nameField.getValue()};},
                listeners: {
                    apply: (data) => {
                        const folderPath = Tine.Filemanager.Model.Node.sanitize(path + '/' + data.name);
                        Tine.Filemanager.nodeBackend.createFolder(folderPath, {
                            headers: {
                                'X-TINE20-REQUEST-CONTEXT-efile-tier-type': tierType
                            }
                        })
                    }
                }
            })
        });
    };

    const fileManagerCreateEFileAction = new Ext.Action({
        app: app,
        allowMultiple: true,
        iconCls: 'action_efile',
        scope: this,
        text: app.i18n._('Electronic File'),
        menu: _.reduce(tierTypes, (items, tierType) => {
            return items.concat(tierType.nodeType === 'folder' ? new Ext.Action({
                text: String.format(i18n._hidden('Add {0}'), _.find(tierTypes, tierType).label),
                iconCls: 'efile-tiertype-' + tierType.tierType,
                tierType: tierType.tierType,
                handler: _.partial(createEFile, tierType.tierType),
                actionUpdater: async function(action, grants, records, isFilterSelect, filteredContainers) {
                    const validator = await validatorFactory();
                    // make sure we deal with the action and not a concrete btn!
                    action = _.get(action, 'baseAction', action);

                    const recordData = _.get(filteredContainers, '[0]');

                    let enabled = validator.validate({
                        parent: recordData,
                        efile_tier_type: _.get(action, 'initialConfig.tierType')
                    });
                    
                    enabled = enabled && _.get(recordData, 'account_grants.addGrant', false);
                    action.filteredContainers = filteredContainers;
                    action.setDisabled(!enabled);
                }
            }) : []);
        }, [])
    });
    
    Ext.ux.ItemRegistry.registerItem('Filemanager-Node-GridPanel-ActionToolbar-leftbtngrp', Ext.apply(new Ext.Button(fileManagerCreateEFileAction), {
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top'
    }), 80);
    
    // with spit -> extra click for each createFoler // w.o. split can't reach efile actions w.o. createFolder
    // Tine.Filemanager.nodeActions.CreateFolder.menu = fileManagerCreateEFileAction.initialConfig.menu;
});
