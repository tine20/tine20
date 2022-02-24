/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagsMassDetachAction
 * @extends     Ext.Action
 */
Tine.Felamimail.MessageFileAction = function(config) {
    config.iconCls = 'action_file';
    config.app = Tine.Tinebase.appMgr.get('Felamimail');
    config.i18n = config.app.i18n;
    config.text = config.text ? config.text : config.i18n._('Save Message as');
    config.menu = new Ext.menu.Menu({});
    
    Ext.apply(this, config);

    Tine.Felamimail.MessageFileAction.superclass.constructor.call(this, config);
    
    if (! this.initialConfig?.selectionModel && this.initialConfig?.record) {
        _.assign(this.initialConfig, {
            selections: [this.initialConfig.record],
            selectionModel: {
                getSelectionFilter: () => {
                    return [{field: 'id', operator: 'equals', value: this.initialConfig.record.id }];
                },
                getCount: () => {
                    return 1
                }
            }
        });
    }
    
    this.menu.on('beforeshow', this.showFileMenu, this);
    this.selectionHandler = this.mode === 'fileInstant' ?
        this.fileMessage.createDelegate(this) :
        this.selectLocation.createDelegate(this);
};

Ext.extend(Tine.Felamimail.MessageFileAction, Ext.Action, {

    /**
     * @cfg {String} fileInstant|selectOnly
     */
    mode: 'fileInstant',

    /**
     * @cfg {Tinebase.data.Record} record optional instead of selectionModel (implicit fom grid)
     */
    record: null,

    /**
     * @property {Boolean} isManualSelection (of file locations)
     */
    isManualSelection: false,

    requiredGrant: 'readGrant',
    allowMultiple: true,
    disabled: true,
    suggestionsLoaded: false, 
    composeDialog: null,
    splitButton: null,
    
    initSplitButton: function () {
        if (this.mode === 'fileInstant') {
            return;
        }
        
        if (! this.splitButton) {
            if (! this.items[0]) {
                return;
            }
            this.splitButton = this.items[0];
            
            if (this.mode !== 'fileInstant') {
                this.splitButton.disabled = false;
                this.splitButton.enableToggle = true;
                this.splitButton.pressed = Tine.Felamimail.registry.get('preferences').get('autoAttachNote') === "1";
            
                // check suggestions (file_location) for reply/forward
                if (this.composeDialog) {
                    this.composeDialog.on('load', this.onMessageLoad, this);
                }
            }

            this.splitButton.on('toggle', this.onToggle, this);
        }
        
        return this.splitButton;
    },
    
    arrowHandler: function() {
        if (this.mode === 'fileInstant') {
            return this.showFileMenu();
        }
        
        this.syncRecipients();
    },
    
    
    onToggle: function() {
        if (! this.splitButton) {
            this.splitButton = this.initSplitButton();
        }
        
        if (this.splitButton.pressed) {
            _.each(_.filter(this.menu?.items?.items, {isRecipientItem: true}), function(item) {
                item.suspendEvents();
                item.setChecked(true);
                item.resumeEvents();
            });
        } else {
            _.each(_.filter(this.menu?.items?.items, {checked: true}), function(item) {
                item.suspendEvents();
                item.setChecked(false);
                item.resumeEvents();
            });
        }
    
        const selection = this.getSelected();
        this.splitButton.fireEvent('selectionchange', this.splitButton, selection);
    },

    showFileMenu: function () {
        this.initSplitButton();
        
        const selection = _.map(this.initialConfig.selections, 'data');

        if (! this.suggestionsLoaded || this.mode === 'fileInstant') {
            this.loadSuggestions(selection);
        }
    
        if (this.composeDialog) {
            this.syncRecipients();
        }
    },

    /**
     * message is loaded in compose dialog
     *
     * @param dlg
     * @param message
     * @param ticketFn
     */
    onMessageLoad: function(dlg, message, ticketFn) {
        if (message.get('original_id')) {
            this.loadSuggestions(message.data).then(() => {
                // auto file if original_message (from forwared/reply) was filed
                if (_.find(_.map(this.menu.items, 'suggestion'), { type : 'file_location' })) {
                    // @TODO: select this suggestion!
                    this.handleBtnSelectionEvent();
                }
            }).catch((error) => {
                Tine.log.notice('No file suggestions available for this message');
                Tine.log.notice(error);
                this.addStaticMenuItems();
            })
        } else {
            this.addStaticMenuItems();
        }

        this.composeDialog.recipientGrid.store.on('add', this.syncRecipients, this);
        this.composeDialog.recipientGrid.store.on('update', this.syncRecipients, this);
    },

    syncRecipients: function() {
         const emailsInRecipientGrid = [];

        _.each(this.composeDialog.recipientGrid.store.data.items, (recipient) => {
            const full = recipient.get('address'),
                parsed = addressparser.parse(String(full).replace(/,/g, '\\\\,')),
                email = parsed.length ? parsed[0].address : '';

            if (email) {
                emailsInRecipientGrid.push(email);
                const fileTarget = {
                    record_title: full,
                    model: Tine.Addressbook.Model.EmailAddress,
                    data: {
                        email: email
                    },
                };

                if (! this.menu.getComponent(email)) {
                    const checked = this.splitButton?.pressed && !this.isManualSelection;
                    this.menu.insert(0, {
                        itemId: email,
                        isRecipientItem: true,
                        xtype: 'menucheckitem',
                        checked: checked,
                        fileTarget: fileTarget,
                        // iconCls: fileTarget.model.getIconCls(),
                        text: Ext.util.Format.htmlEncode(fileTarget.record_title),
                        checkHandler: (item) => {
                            this.handleBtnSelectionEvent();
                        }
                    });

                    if (checked) {
                        this.handleBtnSelectionEvent();
                    }
                }
            }
        });

        // remove all items no longer in recipient grid
        _.each(this.menu.items.items, (item) => {
            // check if in grid
            if (_.get(item, 'itemId') && emailsInRecipientGrid.indexOf(item.itemId) === -1) {
                // item no longer in grid
                this.menu.remove(item);
            }
        });
    },
    
    handleBtnSelectionEvent: function () {
        const selection = this.getSelected();
        
        this.splitButton.suspendEvents();
        this.splitButton.toggle(selection.length);
        this.splitButton.resumeEvents();
        this.splitButton.fireEvent('selectionchange', this.splitButton, selection);
    },
    
    loadSuggestions: function(messages) {
        const suggestionIds = [];

        this.setIconClass('x-btn-wait');
        this.menu.hide();
        this.menu.removeAll();

        return Tine.Felamimail.getFileSuggestions(messages).then((suggestions) => {
            //sort by suggestion.type so file_location record survives deduplication
            
            _.each(_.sortBy(suggestions, 'type'), (suggestion) => {
                let model = null;
                let record = null;
                let id;
                let suggestionId;
                let fileTarget;

                // file_location means message reference is already filed (global registry)
                if (suggestion.type === 'file_location') {
                    id = suggestion.record.record_id;
                    fileTarget = {
                        record_title: suggestion.record.record_title,
                        model: Tine.Tinebase.data.RecordMgr.get(suggestion.record.model),
                        data: id
                    };
                    
                } else {
                    model = Tine.Tinebase.data.RecordMgr.get(suggestion.model);
                    record = Tine.Tinebase.data.Record.setFromJson(suggestion.record, model);
                    id = record.getId();
                    fileTarget = {
                        record_title: record.getTitle(),
                        model: model,
                        data: id
                    };

                }
                suggestionId = fileTarget.model.getPhpClassName() + '-' + id;

                if (suggestionIds.indexOf(suggestionId) < 0) {
                    this.menu.addItem({
                        itemId: suggestionId,
                        isSuggestedItem: true,
                        suggestion: suggestion,
                        fileTarget: fileTarget,
                        iconCls: fileTarget.model.getIconCls(),
                        text: Ext.util.Format.htmlEncode(fileTarget.record_title),
                        handler: this.selectionHandler
                    });
                    suggestionIds.push(suggestionId);
                }
            });

            this.addStaticMenuItems();
            this.addDownloadMenuItem();

            this.suggestionsLoaded = true;
            this.setIconClass('action_file');
        });
    },

    addStaticMenuItems: function() {
        this.menu.addItem('-');
        this.menu.addItem({
            text: this.app.i18n._('File (in Filemanager) ...'),
            hidden: ! Tine.Tinebase.common.hasRight('run', 'Filemanager'),
            handler: this.selectFilemanagerFolder.createDelegate(this)
        });
        this.menu.addItem({
            text: this.app.i18n._('Attachment (of Record)'),
            menu:_.reduce(Tine.Tinebase.data.RecordMgr.items, (menu, model) => {
                if (model.hasField('attachments') && model.getMeta('appName') !== 'Felamimail') {
                    menu.push({
                        text: model.getRecordName() + ' ...',
                        iconCls: model.getIconCls(),
                        handler: this.selectAttachRecord.createDelegate(this, [model], true)
                    });
                }
                return menu;
            }, [])
        });
    },

    addDownloadMenuItem: function() {
        if (! _.isFunction(_.get(this, 'initialConfig.selectionModel.getSelectionFilter'))) return;

        const messageFilter = this.initialConfig.selectionModel.getSelectionFilter();
        const messageIds = messageFilter.length === 1 && messageFilter[0].field === 'id' 
            ? messageFilter[0].value 
            : null;
        const messageCount = this.initialConfig.selectionModel.getCount();

        if (messageCount === 1 && messageIds) {
            this.menu.addItem('-');
            this.menu.addItem({
                text: this.app.i18n._('Download'),
                iconCls: 'action_download',
                handler: this.onMessageDownload.createDelegate(this, [messageIds])
            });
        }
    },

    onMessageDownload: function(messageId) {
        const downloader = new Ext.ux.file.Download({
            params: {
                method: 'Felamimail.downloadMessage',
                requestType: 'HTTP',
                messageId: messageId
            }
        });
        downloader.start();
    },

    /**
     * directly file a single message
     * 
     * TODO: support file multi messages?
     *
     * @param item
     * @param e
     */
    fileMessage: function(item, e) {
        const  messageFilter = this.initialConfig.selectionModel.getSelectionFilter();
        const  messageCount = this.initialConfig.selectionModel.getCount();
        const  locations = [this.itemToLocation(item)];

        this.setIconClass('x-btn-wait');
        Tine.Felamimail.fileMessages(messageFilter, locations)
            .then(() => {
                const msg = this.app.formatMessage('{messageCount, plural, one {Message was saved} other {# messages where saved}}',
                    {messageCount: messageCount });
                Ext.ux.MessageBox.msg(this.app.formatMessage('Success'), msg);
            })
            .catch((error) => {
                Ext.Msg.show({
                    title: this.app.formatMessage('Error'),
                    msg: error.message,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
            })
            .then(() => {
                this.setIconClass('action_file');

                window.postal.publish({
                    channel: "recordchange",
                    topic: 'Felamimail.Message.massupdate',
                    data: {}
                });
            });
    },

    /**
     * returns currently selected locations
     */
    getSelected: function() {
        return _.reduce(this.menu.items.items, (selected, item) => {
            if (item.checked) {
                selected.push(this.itemToLocation(item));
            }
            return selected;
        }, []);
    },

    /**
     * converts (internal) item representation to location
     * @param item
     * @return {{type: string, model: String, record_id: data|{email}|*}}
     */
    itemToLocation:function(item) {
        return {
            type: item.fileTarget.model.getMeta('appName') === 'Filemanager' ? 'node' : 'attachment',
            model: item.fileTarget.model.getPhpClassName(),
            record_id: item.fileTarget.data,
            record_title: item.fileTarget.record_title
        };
    },

    selectLocation: function(item, e) {
        item.setVisible(!item.isSuggestedItem);
        item.selectItem = this.menu.insert(Math.max(0, this.menu.items.indexOf(item)), {
            text: item.fileTarget ? Ext.util.Format.htmlEncode(item.fileTarget.record_title) : item.text,
            checked: true,
            instantItem: item,
            fileTarget: item.fileTarget,
            checkHandler: (item) => {
                item.setVisible(!item.instantItem.isSuggestedItem);
                item.instantItem.show();
                this.handleBtnSelectionEvent();
            }
        });

        this.isManualSelection = true;
        this.handleBtnSelectionEvent();
    },

    selectFilemanagerFolder: function(item, e) {
        const filePickerDialog = new Tine.Filemanager.FilePickerDialog({
            constraint: 'folder',
            singleSelect: true,
            requiredGrants: ['addGrant']
        });

        filePickerDialog.on('selected', this.onFilemanagerNodesSelected.createDelegate(this, [item, e], 0));
        filePickerDialog.openWindow();
    },

    onFilemanagerNodesSelected: function(item, e, nodes) {
        let nodeData = _.get(nodes[0], 'nodeRecord', nodes[0]);
        const fakeItem = new Ext.menu.Item();

        nodeData = _.get(nodeData, 'data', nodeData);

        fakeItem.fileTarget = {
            record_title: nodeData.name,
            model: Tine.Filemanager.Model.Node,
            data: nodeData,
        };
        this.selectionHandler(fakeItem, e)
    },

    selectAttachRecord: function(item, e, model) {
        const pickerDialog = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 250,
            height: 100,
            padding: '5px',
            modal: true,
            title: this.app.i18n._('Save Messages as Attachment'),
            items: new Tine.Tinebase.dialog.Dialog({
                listeners: {
                    scope: this,
                    apply: function(fileTarget) {
                        item.fileTarget = fileTarget;
                        this.selectionHandler(item, e);
                    }
                },
                getEventData: (eventName) => {
                    if (eventName === 'apply') {
                        const attachRecord = this.getForm().findField('attachRecord').selectedRecord;
                        return {
                            record_title: attachRecord.getTitle(),
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

Tine.Felamimail.MessageFileAction.getFileLocationText = function(locations, glue='') {
    const _ = window.lodash,
        formatMessage = Tine.Tinebase.appMgr.get('Felamimail').formatMessage;

    return _.reduce(locations, function(text, location) {
        const model = _.isString(location.model) ? Tine.Tinebase.data.RecordMgr.get(location.model) : location.model,
            iconCls = model ? model.getIconCls() : '',
            icon = iconCls ? '<span class="felamimail-location-icon ' + iconCls +'"></span>' : '',
            span = model ? '<span class="felamimail-location" ' +
                'onclick="Tine.Felamimail.MessageFileAction.locationClickHandler(\'' + model.getPhpClassName() +
                "','" + location.record_id + '\')">' + icon + '<span class="felamimail-location-text">'
                + Ext.util.Format.htmlEncode(location.record_title) + '</span></span>' : '';

        return text.concat(span);
    }, []).join(glue);
};

Tine.Felamimail.MessageFileAction.locationClickHandler = function (recordClassName, recordId) {
    let recordClass = Tine.Tinebase.data.RecordMgr.get(recordClassName);
    let recordData = {};
    let editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(recordClass);
    recordData[recordClass.getMeta('idProperty')] = recordId;

    editDialogClass.openWindow({
        record: Tine.Tinebase.data.Record.setFromJson(recordData, recordClass),
        recordId: recordId
    });
};
