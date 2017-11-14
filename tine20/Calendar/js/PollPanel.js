/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Calendar');

require('../css/PollPanel.css');

Tine.Calendar.PollPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Tine.widgets.dialog.EditDialog}
     */
    editDialog: null,

    requiredGrant: 'editGrant',

    /**
     * @property {Tine.Calendar.Model.Poll} poll
     */
    poll: null,

    canonicalName: 'PollPanel',
    xtype: 'form',
    frame: true,
    layout: 'vbox',
    layoutConfig: {
        align : 'stretch',
        pack  : 'start'
    },

    statusWeightMap: ["DECLINED", "NEEDS-ACTION", "TENTATIVE", "ACCEPTED"],

    initComponent: function() {
        var _ = window.lodash,
            me = this;

        this.app = Tine.Tinebase.appMgr.get('Calendar');

        this.title = this.app.i18n._('Poll');

        this.on('activate', this.onPanelActivate, this);

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);
        this.editDialog.on('dtStartChange', this.onMainTabDtStartChange, this);

        this.tbar = [{
            xtype: 'buttongroup',
            frame: false,
            items: [{
                xtype: 'tbbtnlockedtoggle',
                toggleGroup: 'cal-poll-panel',
                pressed: true,
                text: this.app.i18n._('Options'),
                handler: this.changeCardPanel.createDelegate(this, [0]),
                enableToggle: true
            }, {
                xtype: 'tbbtnlockedtoggle',
                toggleGroup: 'cal-poll-panel',
                ref: '../../alternativeDatesButton',
                disabled: true,
                pressed: false,
                text: this.app.i18n._('Alternative Dates'),
                handler: this.changeCardPanel.createDelegate(this, [1]),
                enableToggle: true
            }]
        }];

        this.action_setDefiniteEvent = new Ext.Action({
            text: this.app.i18n._('Set as definite event'),
            iconCls: 'cal-polls-set-definite-action',
            scope: this,
            handler: this.onSetDefiniteEvent
        });

        this.items = [{
            layout: 'card',
            ref: 'cardPanel',
            activeItem: 0,
            layoutConfig: {
                layoutOnCardChange: true,
                deferredRender: true
            },
            flex: 1,
            items: [{
                // layout: 'form',
                // frame: true,
                xtype: 'columnform',
                border: false,
                labelAlign: 'top',
                width: '100%',
                items: [[{
                    xtype: 'checkbox',
                    columnWidth: 0.40,
                    ref: '../../../../createPollCheckbox',
                    disabled: true,
                    hideLabel: true,
                    boxLabel: this.app.i18n._('Create Poll for this Event'),
                    listeners: {scope: this, check: this.onCreatePollCheck}
                }, {
                    xtype: 'textfield',
                    columnWidth: 0.60,
                    ref: '../../../../pollNameField',
                    disabled: true,
                    hideLabel: true,
                    emptyText: this.app.i18n._('Name for poll')
                }], [{
                    xtype: 'displayfield',
                    columnWidth: 1,
                    hideLabel: true,
                    value: [
                        this.app.i18n._("Create scheduling suggestions and pick the best alternative based on user feedback."),
                        this.app.i18n._("Share the link below to poll for attendee status feedback."),
                    ].join(' ')
                }], [{
                    // @TODO implement clipbord field (trigger field)
                    xtype: 'field',
                    columnWidth: 1,
                    ref: '../../../../urlField',
                    fieldLabel: this.app.i18n._('Public URL'),
                    readOnly: true,
                    anchor: '100%'
                }], [{
                    xtype: 'checkbox',
                    columnWidth: 0.40,
                    ref: '../../../../isPasswordProtectedCheckbox',
                    disabled: true,
                    hideLabel: true,
                    boxLabel: this.app.i18n._('Protect poll with a password'),
                    listeners: {scope: this, check: this.onIsPasswordProtectedCheck}
                }, {
                    xtype: 'textfield',
                    columnWidth: 0.30,
                    ref: '../../../../pollPasswordField',
                    disabled: true,
                    hideLabel: true
                }], [{
                    xtype: 'checkbox',
                    ref: '../../../../isLockedPollCheckbox',
                    disabled: true,
                    hideLabel: true,
                    boxLabel: this.app.i18n._('Only invited attendee can reply')
                }]]
            }, new Tine.widgets.grid.QuickaddGridPanel({
                ref: '../alternativeEventsGrid',
                layout: 'fit',
                border: false,
                frame: false,
                autoExpandColumn: 'info',
                quickaddMandatory: 'dtstart',
                recordClass: Tine.Calendar.Model.Event,
                store:  new Tine.Tinebase.data.RecordStore({
                    readOnly: true,
                    autoLoad: false,
                    recordClass: Tine.Calendar.Model.Event,
                    proxy: Tine.Calendar.backend,
                    pruneModifiedRecords: true,
                    getModifiedRecords: function() {return window.lodash.filter(this.modified, {dirty: true});},
                    sort: this.sortEventStore,
                    sortInfo: {
                        field: 'dtstart',
                        direction: 'ASC'
                    }
                }),
                resetAllOnNew: false,
                getContextMenuItems: function() {
                    return [me.action_setDefiniteEvent];
                },
                listeners: {
                    scope: this,
                    afteredit: this.onAfterEventEdit,
                    activate: this.onEventsGridActivate
                },
                cm: new Ext.grid.ColumnModel([{
                    id: 'dtstart',
                    header: this.app.i18n._('Start Date'),
                    width: 200,
                    dataIndex: 'dtstart',
                    hideable: false,
                    sortable: true,
                    renderer: this.dateRenderer.createDelegate(this),
                    quickaddField: new Ext.ux.form.DateTimeField({
                        resetOnNew: false
                    }),
                    editor: new Ext.ux.form.DateTimeField({
                        allowBlank: false
                    })
                }, {
                    id: 'info',
                    header: this.app.i18n._('Info'),
                    dataIndex: 'info',
                    hideable: false,
                    sortable: true,
                    renderer: this.infoRenderer.createDelegate(this)
                }])
            })]
        }];

        this.supr().initComponent.apply(this, arguments);
    },

    dateRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        if (this.editDialog.record.get('id') == record.get('id')) {
            metaData.css = 'cal-pollpanel-alternativeevents-currentevent';
        }
        return value.format('l') + ', ' + Tine.Tinebase.common.dateTimeRenderer(value);
    },

    infoRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        var _ = window.lodash,
            attendeeStatus = _.groupBy(_.get(record, 'data.attendee', {}), 'status'),
            statusStore = store = Tine.Tinebase.widgets.keyfield.StoreMgr.get(this.app, 'attendeeStatus'),
            renderer = Tine.Tinebase.widgets.keyfield.Renderer.get(this.app, 'attendeeStatus');

        return _.map(_.reverse([].concat(Tine.Calendar.PollPanel.prototype.statusWeightMap)), function(statusId) {
            var count = _.get(attendeeStatus, statusId, []).length;

            return '<span class="cal-pollpanel-alternativeevents-statustoken">' +
                        renderer(statusId) +
                        '<span class="cal-pollpanel-alternativeevents-statuscount">' + count + '</span>' +
                '</span>';
        }).join('');
    },

    infoSortValueFn: function(event) {
        var _ = window.lodash,
            attendeeStatus = _.groupBy(_.get(event, 'data.attendee', {}), 'status');

        return _.reduce(attendeeStatus, function(result, attendee, id) {
            return result + attendee.length * Math.max(0, _.indexOf(Tine.Calendar.PollPanel.prototype.statusWeightMap, id));
        }, 0);
    },

    sortEventStore: function(fieldName, dir) {
        if (fieldName != 'info') {
            return Ext.data.JsonStore.prototype.sort.apply(this, arguments);
        }

        dir = this.sortToggle['info'] = (this.sortToggle['info'] || 'ASC').toggle('ASC', 'DESC');

        this.data.sort(dir, function(a, b) {
            return Tine.Calendar.PollPanel.prototype.infoSortValueFn(a) -
                Tine.Calendar.PollPanel.prototype.infoSortValueFn(b);
        });
        this.sortInfo = {
            field: 'info',
            direction: dir
        };
        this.fireEvent('datachanged', this);
    },

    onPanelActivate: function() {
        this.editDialog.rrulePanel.onRecordUpdate(this.editDialog.record);
        this.setReadOnly(this.readOnly);
    },

    onCreatePollCheck: function(cb, checked) {
        var pollData = null;

        // initialize new poll
        if (checked && !this.poll) {

            var pollId = Tine.Tinebase.data.Record.generateUID(40),
                event = this.editDialog.record;

            this.alternativeEventsGrid.store.add([new Tine.Calendar.Model.Event({
                // id: event.get('id'),
                dtstart: event.get('dtstart'),
                dtend: event.get('dtdend')
            }, event.id)]);
            this.alternativeEventsGrid.pollId = pollId;

            pollData = {
                id: pollId
            };

        }

        this.onPollLoad(pollData);
    },

    onIsPasswordProtectedCheck: function(cb, checked) {
        this.pollPasswordField.setDisabled(!checked);
    },

    onRecordLoad: function() {
        // preset quickadd
        this.alternativeEventsGrid.getCols(true)[0].quickaddField.setValue(this.editDialog.record.get('dtstart'));

        // load poll
        this.onPollLoad(this.editDialog.record.get('poll_id'));
    },

    onPollLoad: function(pollData) {
        var _ = window.lodash,
            me = this,
            record = me.editDialog.record,
            evalGrants = me.editDialog.evalGrants,
            isClosed = !!+_.get(pollData, 'closed', false),
            hasRequiredGrant = !evalGrants || _.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant);

        if (pollData) {
            this.poll = new Tine.Calendar.Model.Poll(pollData);
            Tine.Tinebase.common.assertComparable(this.poll.data);
        } else {
            this.poll = null;
        }

        // click vs. setValue!
        this.createPollCheckbox.suspendEvents();
        this.createPollCheckbox.setValue(!!this.poll);
        this.createPollCheckbox.resumeEvents();

        this.urlField.setValue(this.poll ?
            Tine.Tinebase.common.getUrl() + 'Calendar/view/poll/' + this.poll.id : '');
        this.pollNameField.setValue(this.poll ? this.poll.get('name') : '');
        this.isPasswordProtectedCheckbox.setValue(this.poll ? !!this.poll.get('password') : false);
        this.pollPasswordField.setValue(this.poll && this.poll.get('password') ? this.poll.get('password') : '');
        this.isLockedPollCheckbox.setValue(this.poll ? !!+this.poll.get('locked') : false);

        if (this.poll) {
            this.editDialog.rrulePanel.setDisabled(true);
        }

        this.setReadOnly(! hasRequiredGrant || isClosed);
    },

    setReadOnly: function(readOnly) {
        var hasPoll = this.createPollCheckbox.getValue(),
            isRecur = this.editDialog.record.get('rrule') || this.editDialog.record.isRecurException(),
            hasPassword = this.isPasswordProtectedCheckbox.getValue();

        // NOTE: it's not possible to remove a persistent poll here
        //       -> use choose definite event
        this.createPollCheckbox.setDisabled(readOnly ||this.poll ||isRecur);
        this.pollNameField.setDisabled(!hasPoll || readOnly);
        this.isPasswordProtectedCheckbox.setDisabled(!hasPoll || readOnly);
        this.pollPasswordField.setDisabled(!hasPoll || !hasPassword || readOnly);
        this.isLockedPollCheckbox.setDisabled(!hasPoll || readOnly);
        this.alternativeEventsGrid.setReadOnly(!hasPoll || readOnly);
        this.alternativeDatesButton.setDisabled(!hasPoll);

        this.readOnly = readOnly;
    },

    onEventsGridActivate: function() {
        var me = this,
            pollId = this.poll.get('id'),
            event = this.editDialog.record;

        if (! this.alternativeEventsGrid.loadMask) {
            this.alternativeEventsGrid.loadMask = new Ext.LoadMask(this.alternativeEventsGrid.getEl(), {
                msg: this.app.i18n._('loading alternative dates')
            });
        }

        if (pollId != this.alternativeEventsGrid.pollId) {
            this.alternativeEventsGrid.loadMask.show();
            Tine.Calendar.getPollEvents(this.poll.get('id'))
                .then(function (response) {
                    me.alternativeEventsGrid.store.loadData(response);
                    // event copy
                    if (!event.get('id')) {
                        me.alternativeEventsGrid.store.add([new Tine.Calendar.Model.Event({
                            // id: event.get('id'),
                            dtstart: event.get('dtstart'),
                            dtend: event.get('dtdend')
                        }, event.id)])
                    }
                    me.alternativeEventsGrid.pollId = pollId;
                    me.alternativeEventsGrid.loadMask.hide();
                });
        }

        this.alternativeEventsGrid.setReadOnly(this.readOnly);
        this.action_setDefiniteEvent.setDisabled(this.readOnly);
    },

    onRecordUpdate: function(editDialog, record) {
        if (this.poll) {
            this.poll.set('name', this.pollNameField.getValue());
            this.poll.set('password', this.isPasswordProtectedCheckbox.getValue() ?
                this.pollPasswordField.getValue() : null
            );
            this.poll.set('locked', this.isLockedPollCheckbox.getValue());
            this.poll.set('alternative_dates', null);
            if (this.poll.get('id') == this.alternativeEventsGrid.pollId) {
                this.poll.set('alternative_dates', this.alternativeEventsGrid.getFromStoreAsArray(true));
            }
            record.set('poll_id', this.poll.data);
        }
    },

    onMainTabDtStartChange: function(data) {
        var scheduling = Ext.decode(data),
            newValue = new Date(scheduling.newValue),
            oldValue = new Date(scheduling.oldValue),
            event = this.alternativeEventsGrid.store.getById(this.editDialog.record.id);

        if (event) {
            event.set('dtstart', newValue);
        }
    },

    onAfterEventEdit: function(o) {
        if (o.field == 'dtstart' && o.record.get('id') == this.editDialog.record.get('id')) {
            var dtStartField = this.editDialog.getForm().findField('dtstart'),
                newValue = o.record.get('dtstart'),
                oldValue = o.record.modified['dtstart'];

            dtStartField.setValue(newValue);
            this.editDialog.onDtStartChange(dtStartField, newValue, oldValue);
        }
    },

    onSetDefiniteEvent: function() {
        require('./PollSetDefiniteEventAction');
        var me = this,
            ns = Tine.Calendar.eventActions.setDefiniteEventAction,
            selected = this.alternativeEventsGrid.getSelectionModel().getSelections(),
            event = selected[0],
            dlg = this.editDialog;

        if (selected.length > 1) {
            Ext.Msg.alert(
                this.app.i18n._('Select one Event Only'),
                this.app.i18n._('You need to select exactly one event as the definite event.')
            );
            return;
        }

        ns.confirm()
            .then(function() {
                me.setDefiniteEventMask = new Ext.LoadMask(dlg.getEl(), {
                    msg: me.app.i18n._('Setting definite event')
                });
                me.setDefiniteEventMask.show();
                return Tine.Calendar.setDefinitePollEvent(event.data);
            })
            .then(function(response) {
                window.postal.publish({
                    channel  : "thirdparty",
                    topic    : "data.changed"
                });
                dlg.onCancel.defer(1000, dlg);
            })
            .catch(function(error) {
                me.setDefiniteEventMask.hide();
            });
    },

    changeCardPanel: function(idx) {
        this.cardPanel.layout.setActiveItem(idx);
    }
});