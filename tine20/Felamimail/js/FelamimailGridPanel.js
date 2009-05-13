/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * @todo        finish reply all implementation
 * @todo        add header to preview
 * @todo        add more filters (to/cc/date...)
 * @todo        add \recent flag
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * Message grid panel
 */
Tine.Felamimail.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Felamimail.Model.Message,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'received', direction: 'DESC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'subject',
        // drag n drop
        enableDragDrop: true,
        ddGroup: 'mailToTreeDDGroup'
    },
    
    /**
     * Return CSS class to apply to rows depending upon flags
     * - checks Flagged, Deleted and Seen
     * 
     * @param {} record
     * @param {} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var flags = record.get('flags');
        var className = '';
        if(flags !== null) {
            if (flags.match(/Flagged/)) {
                className += ' flag_flagged';
            }
            if (flags.match(/Deleted/)) {
                className += ' flag_deleted';
            }
        }
        if (flags === null || !flags.match(/Seen/)) {
            className += ' flag_unread';
        }
        return className;
    },
    
    /**
     * init message grid
     */
    initComponent: function() {
        this.recordProxy = Tine.Felamimail.messageBackend;
        
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);         
        
        Tine.Felamimail.GridPanel.superclass.initComponent.call(this);
        
        //this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Felamimail', 'records'));
        //this.action_editInNewWindow.requiredGrant = 'editGrant';
        
        this.grid.getSelectionModel().on('rowselect', function(selModel, rowIndex, r) {
            // toggle read/seen flag of mail (only if 1 selected row)
            if (selModel.getCount() == 1) {
                Ext.get(this.grid.getView().getRow(rowIndex)).removeClass('flag_unread');
            }
        }, this);
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {

        this.action_write = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.app.i18n._('Write'),
            handler: this.onEditInNewWindow,
            iconCls: this.app.appName + 'IconCls',
            scope: this
        });

        this.action_reply = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            text: this.app.i18n._('Reply'),
            handler: this.onEditInNewWindow,
            iconCls: 'action_email_reply',
            scope: this,
            disabled: true
        });

        this.action_replyAll = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'replyAll',
            text: this.app.i18n._('Reply To All'),
            handler: this.onEditInNewWindow,
            iconCls: 'action_email_replyAll',
            scope: this,
            disabled: true
        });

        this.action_forward = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'forward',
            text: this.app.i18n._('Forward'),
            handler: this.onEditInNewWindow,
            iconCls: 'action_email_forward',
            scope: this,
            disabled: true
        });

        this.action_flag = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Toggle Flag'),
            handler: this.onToggleFlag,
            iconCls: 'action_email_flag',
            allowMultiple: true,
            scope: this,
            disabled: true
        });
        
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.app.i18n._('Delete'),
            pluralText: this.app.i18n._('Delete'),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.tranlation,
            text: this.app.i18n._('Delete'),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        
        this.actions = [
            this.action_write,
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_flag,
            this.action_deleteRecord
        ];
        
        this.actionToolbar = new Ext.Toolbar({
            split: false,
            height: 26,
            items: this.actions
        });
        
        this.contextMenu = new Ext.menu.Menu({
            items: this.actions.concat(this.contextMenuItems)
        });
        
        // pool together all our actions, so that we can hand them over to our actionUpdater
        for (var all=this.actionToolbarItems.concat(this.contextMenuItems), i=0; i<all.length; i++) {
            if(this.actions.indexOf(all[i]) == -1) {
                this.actions.push(all[i]);
            }
        }
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Subject'),    field: 'subject',       operators: ['contains']}
                // @todo add filters
                /*
                {label: this.app.i18n._('Message'),    field: 'query',       operators: ['contains']},
                {label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
                new Tine.Felamimail.TimeAccountStatusGridFilter({
                    field: 'status'
                }),
                */
                //new Tine.widgets.tags.TagFilter({app: this.app})
             ],
             defaultFilter: 'subject',
             filters: []
        });
    },    
    
    /**
     * the details panel (shows message content)
     * 
     * @todo add headers
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.widgets.grid.DetailsPanel({
            defaultHeight: 300,
            gridpanel: this,
            currentId: null,
            
            updateDetails: function(record, body) {
                if (record.id !== this.currentId) {
                    this.currentId = record.id;
                    Tine.Felamimail.messageBackend.loadRecord(record, {
                        scope: this,
                        success: function(message) {
                            record.data.body = message.data.body;                            
                            record.data.flags = message.data.flags;
                            
                            this.tpl.overwrite(body, message.data);
                            this.getEl().down('div').down('div').scrollTo('top', 0, false);
                            this.getLoadMask().hide();
                            
                            // toggle read/seen flag of mail
                            //Ext.get(this.grid.getView().getRow(rowIndex)).removeClass('flag_unread');
                        }
                    });
                    this.getLoadMask().show();
                } else {
                    this.tpl.overwrite(body, record.data);
                }
            },

            tpl: new Ext.XTemplate(
                '<div class="preview-panel-felamimail-body">',
                    //'<tpl for="Body">',
                            '<div class="Mail-Body-Content">{[this.encode(values.body)]}</div>',
                    // '</tpl>',
                '</div>',{
                
                encode: function(value, type, prefix) {
                    if (value) {
                        /*
                        if (type) {
                            switch (type) {
                                case 'longtext':
                                    value = Ext.util.Format.ellipsis(value, 150);
                                    break;
                                default:
                                    value += type;
                            }                           
                        }
                        */
                        
                        var encoded = Ext.util.Format.htmlEncode(value);
                        encoded = Ext.util.Format.nl2br(encoded);
                        
                        return encoded;
                    } else {
                        return '';
                    }
                    return value;
                }
            }),
            
            // use default Tpl for default and multi view
            defaultTpl: new Ext.XTemplate(
                '<div class="preview-panel-felamimail-body">',
                    '<div class="Mail-Body-Content"></div>',
                '</div>'
            )
        });
    },
    
    /**
     * returns cm
     * @private
     */
    getColumns: function(){
        return [{
            id: 'id',
            header: this.app.i18n._("Id"),
            width: 100,
            sortable: true,
            dataIndex: 'id',
            hidden: true
        }, {
            id: 'attachment',
            width: 12,
            sortable: true,
            dataIndex: 'attachment',
            renderer: this.attachmentRenderer
        }, {
            id: 'flags',
            width: 24,
            sortable: true,
            dataIndex: 'flags',
            renderer: this.flagRenderer
        },{
            id: 'subject',
            header: this.app.i18n._("Subject"),
            width: 300,
            sortable: true,
            dataIndex: 'subject'
        },{
            id: 'from',
            header: this.app.i18n._("From"),
            width: 150,
            sortable: true,
            dataIndex: 'from'
        },{
            id: 'to',
            header: this.app.i18n._("To"),
            width: 150,
            sortable: true,
            dataIndex: 'to',
            hidden: true
        },{
            id: 'sent',
            header: this.app.i18n._("Sent"),
            width: 150,
            sortable: true,
            dataIndex: 'sent',
            hidden: true,
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'received',
            header: this.app.i18n._("Received"),
            width: 150,
            sortable: true,
            dataIndex: 'received',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 80,
            sortable: true,
            dataIndex: 'size',
            hidden: true
        }];
    },
    
    /**
     * attachment column renderer
     * @param {string} value
     * @return {string}
     */
    attachmentRenderer: function(value) {
        return (value == 1) ? '<img class="FelamimailFlagIcon" src="images/oxygen/16x16/actions/attach.png">' : '';
    },
    
    /**
     * get flag icon
     * 
     * @param {} flags
     * @return {}
     * 
     * @todo use spacer if first flag is not set
     */
    flagRenderer: function(flags) {
        if (!flags) {
            return '';
        }
        
        var icons = [];
        if (flags.match(/Answered/)) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-reply-sender.png', qtip: _('Answered')});
        }   
        if (flags.match(/Passed/)) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-forward.png', qtip: _('Forwarded')});
        }   
        if (flags.match(/Recent/)) {
            icons.push({src: 'images/oxygen/16x16/actions/knewstuff.png', qtip: _('Recent')});
        }   
        
        var result = '';
        for (var i=0; i < icons.length; i++) {
            result = result + '<img class="FelamimailFlagIcon" src="' + icons[i].src + '" ext:qtip="' + icons[i].qtip + '">';
        }
        
        return result;
    },
    
    /********************************* event handler **************************************/
    
    /**
     * generic edit in new window handler
     * - overwritten parent func
     * - action type edit: reply/replyAll/forward
     * 
     * @param {} button
     * @param {} event
     * 
     * @todo add quoting to reply body text
     * @todo add forwarding message
     */
    onEditInNewWindow: function(button, event) {
        var recordData = this.recordClass.getDefaultData();
        var recordId = 0;
        
        if (    button.actionType == 'reply'
            ||  button.actionType == 'replyAll'
            ||  button.actionType == 'forward'
        ) {
            var selectedRows = this.grid.getSelectionModel().getSelections();
            var selectedRecord = selectedRows[0];
            
            recordId = selectedRecord.id;
            
            switch (button.actionType) {
                case 'replyAll':
                case 'reply':
                    recordData.id = recordId;
                    recordData.to = selectedRecord.get('from');
                    recordData.body = Ext.util.Format.nl2br(selectedRecord.get('body'));
                    recordData.subject = _('Re: ') + selectedRecord.get('subject');
                    recordData.flags = '\\Answered';
                    break;
                case 'forward':
                    recordData.id = recordId;
                    recordData.body = Ext.util.Format.nl2br(selectedRecord.get('body'));
                    recordData.subject = _('Fwd: ') + selectedRecord.get('subject');
                    recordData.flags = 'Passed';
                    break;
            }
        }
        
        var record = new this.recordClass(recordData, recordId);
        
        var popupWindow = Tine[this.app.appName][this.recordClass.getMeta('modelName') + 'EditDialog'].openWindow({
            record: record,
            listeners: {
                scope: this,
                'update': function(record) {
                    this.store.load({});
                }
            }
        });
    },
    
    /**
     * toggle flagged status of mail(s)
     * 
     * @param {} button
     * @param {} event
     */
    onToggleFlag: function(button, event) {
        var messages = this.grid.getSelectionModel().getSelections();            
        var toUpdateIds = [];
        for (var i = 0; i < messages.length; ++i) {
            toUpdateIds.push(messages[i].data.id);
        }
        
        // check if set or clear flag
        var method = (messages[0].get('flags').match(/Flagged/)) ? 'clearFlag' : 'setFlag';
        
        this.grid.loadMask.show();
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.' + method,
                ids: Ext.util.JSON.encode(toUpdateIds),
                flag: Ext.util.JSON.encode('\\Flagged')
            },
            success: function(_result, _request) {
                this.store.load();
                this.grid.loadMask.hide();
            },
            failure: function(result, request){
                Ext.MessageBox.alert(
                    this.app.i18n._('Failed'), 
                    this.app.i18n._('Some error occured while trying to update the messages.')
                );
            },
            scope: this
        });
    }
});
