/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.Felamimail.MessageFileButton = Ext.extend(Ext.SplitButton, {

    /**
     * @cfg {String} fileInstant|selectOnly
     */
    mode: 'fileInstant',

    autoAttach: null,
    requiredGrant: 'readGrant',
    allowMultiple: true,
    iconCls: 'action_file',
    disabled: true,
    suggestionsLoaded: false,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.i18n = this.app.i18n;

        this.text = this.i18n._('File Message');

        this.menu = [];

        this.selectionHandler = this.mode == 'fileInstant' ?
            this.fileMessage.createDelegate(this) :
            this.selectLocation.createDelegate(this);

        if (this.mode != 'fileInstant') {
            this.autoAttach = Tine.Felamimail.registry.get('preferences').get('autoAttachNote');
            this.disabled = false;
            this.enableToggle = true;
            this.pressed = this.autoAttach;
        }
        this.supr().initComponent.call(this);
    },

    handler: function() {
        if (this.mode != 'fileInstant') {
            // just toggle
            return;
        }

        this.loadMenu();
    },

    arrowHandler: function() {
        if (this.mode == 'fileInstant') {
            return this.loadMenu();
        }
    },

    loadMenu: function () {
        var _ = window.lodash,
            selection = _.map(this.initialConfig.selections, 'data');

        if (! this.suggestionsLoaded || this.mode == 'fileInstant') {
            this.setIconClass('x-btn-wait');
            this.hideMenu();

            Tine.Felamimail.getFileSuggestions(selection[0])
                .then(this.onSuggestionsLoad.createDelegate(this));
        } else {
            this.showMenu();
        }
    },

    onSuggestionsLoad: function(suggestions) {
        var _ = window.lodash,
            me = this,
            suggestionIds = [];

        this.menu.removeAll();

        // suggestions
        _.each(suggestions, function(suggestion) {
            var model, record, title,  id, suggestionId, fileTarget;

            if (suggestion.type == 'file_location') {
                model = Tine.Tinebase.data.RecordMgr.get(suggestion.record.model);
                title = suggestion.record.record_title;
                id = suggestion.record.record_id;
            } else {
                model = Tine.Tinebase.data.RecordMgr.get(suggestion.model);
                record = Tine.Tinebase.data.Record.setFromJson(suggestion.record, model);
                title = record.getTitle();
                id = record.getId();
            }
            suggestionId = model.getPhpClassName() + '-' + id;

            if (suggestionIds.indexOf(suggestionId) < 0) {
                fileTarget = {
                    title: title,
                    model: model,
                    data: suggestion.record,
                };

                me.menu.addItem({
                    isSuggestedItem: true,
                    fileTarget: fileTarget,
                    iconCls: model.getIconCls(),
                    text: title,
                    handler: me.selectionHandler
                });
                suggestionIds.push(suggestionId);
            }
        });


        // other items
        this.menu.addItem('-');
        this.menu.addItem({
            text: this.app.i18n._('Filemanager ...'),
            hidden: ! Tine.Tinebase.common.hasRight('run', 'Filemanager'),
            handler: this.selectFilemanagerFolder.createDelegate(this)
        });
        this.menu.addItem({
            text: this.app.i18n._('Attachment'),
            menu:_.reduce(Tine.Tinebase.data.RecordMgr.items, function(menu, model) {
                if (model.hasField('attachments') && model.getMeta('appName') != 'Felamimail') {
                    menu.push({
                        text: model.getRecordName(),
                        iconCls: model.getIconCls(),
                        handler: me.selectAttachRecord.createDelegate(me, [model], true)
                    });
                }
                return menu;
            }, [])
        });

        this.suggestionsLoaded = true;
        this.showMenu();
        this.setIconClass('action_file');
    },

    /**
     * directly file a single message
     *
     * @param item
     * @param e
     */
    fileMessage: function(item, e) {
        var me = this,
            messageFilter = this.initialConfig.selectionModel.getSelectionFilter(),
            messageCount = this.initialConfig.selectionModel.getCount(),
            locations = [{
                type: item.fileTarget.model.getMeta('appName') == 'Filemanager' ? 'node' : 'attachment',
                model: item.fileTarget.model.getPhpClassName(),
                record_id: item.fileTarget.data
            }];

        this.setIconClass('x-btn-wait');
        Tine.Felamimail.fileMessages(messageFilter, locations)
            .then(function() {
                var msg = formatMessage('{messageCount, plural, one {Message was filed} other {# messages where filed}}',
                    {messageCount: messageCount });
                Ext.ux.MessageBox.msg(formatMessage('Success'), msg);
            })
            .catch(function(error) {

                Ext.Msg.show({
                    title: formatMessage('Error'),
                    msg: error.message,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
            })
            .then(function() {
                me.setIconClass('action_file');

                window.postal.publish({
                    channel: "recordchange",
                    topic: 'Felamimail.Message',
                    data: null
                });
            });
    },

    getSelected: function() {
        var _ = window.lodash;

        return _.reduce(this.menu.items.items, function(selected, item) {
            if (item.checked) {
                // @TODO convert into some representation ?
                selected.push(item.fileTarget);
            }
            return selected;
        }, []);
    },

    selectLocation: function(item, e) {
        item.setVisible(!item.isSuggestedItem);
        item.selectItem = this.menu.insert(Math.max(0, this.menu.items.indexOf(item)), {
            text: item.fileTarget ? item.fileTarget.title : item.text,
            checked: true,
            instantItem: item,
            fileTarget: item.fileTarget,
            checkHandler: function(item) {
                item.setVisible(!item.instantItem.isSuggestedItem);
                item.instantItem.show();
            }
        });
    },

    selectFilemanagerFolder: function(item, e) {
        var filePickerDialog = new Tine.Filemanager.FilePickerDialog({
            constraint: 'folder',
            singleSelect: true,
            requiredGrants: ['addGrant']
        });

        filePickerDialog.on('selected', this.onFilemanagerNodesSelected.createDelegate(this, [item, e], 0));
        filePickerDialog.openWindow();
    },

    onFilemanagerNodesSelected: function(item, e, nodes) {
        var _ = window.lodash,
            nodeData = _.get(nodes[0], 'nodeRecord', nodes[0]),
            fakeItem = new Ext.menu.Item();

        fakeItem.fileTarget = {
            title: nodeData.name,
            model: Tine.Filemanager.Model.Node,
            data: nodeData,
        };
        this.selectionHandler(fakeItem, e)
    },

    selectAttachRecord: function(item, e, model) {
        var pickerDialog = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 250,
            height: 100,
            padding: '5px',
            modal: true,
            title: this.app.i18n._('File Messages as Attachment'),
            items: new Tine.Tinebase.dialog.Dialog({
                listeners: {
                    scope: this,
                    apply: function(fileTarget) {
                        item.fileTarget = fileTarget;
                        this.selectionHandler(item, e);
                    }
                },
                getEventData: function(eventName) {
                    if (eventName == 'apply') {
                        var attachRecord = this.getForm().findField('attachRecord').selectedRecord;
                        return {
                            title: attachRecord.getTitle(),
                            model: model,
                            data: attachRecord.data,
                        };
                    }
                },
                items: Tine.widgets.form.RecordPickerManager.get(model.getMeta('appName'), model.getMeta('modelName'), {
                    fieldLabel: model.getRecordName(),
                    name: 'attachRecord'
                })
            })
        });
    }
});
