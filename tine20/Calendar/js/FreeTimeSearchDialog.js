/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.FreeTimeSearchDialog
 * @extends Ext.Panel
 */
Tine.Calendar.FreeTimeSearchDialog = Ext.extend(Ext.Panel, {

    cls: 'tw-editdialog',
    layout: 'fit',
    windowNamePrefix: 'CalFreeTimeSearchWindow_',
    optionsStateId: 'FreeTimeSearchOptions',

    initComponent: function() {
        var _ = window.lodash;
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.window.setTitle(this.app.i18n._('Free Time Search'));

        this.recordClass = Tine.Calendar.Model.Event;
        this.recordProxy = Tine.Calendar.backend;

        if (Ext.isString(this.record)) {
            this.record = this.recordProxy.recordReader({responseText: this.record});
        }

        var prefs = this.app.getRegistry().get('preferences');
        Ext.DatePicker.prototype.startDay = parseInt((prefs ? prefs.get('firstdayofweek') : 1), 10);

        this.store = new Ext.data.JsonStore({
            fields: Tine.Calendar.Model.Event,
            proxy: Tine.Calendar.backend,
            reader: new Ext.data.JsonReader({})
        });

        this.calendarView = new Tine.Calendar.DaysView({
            store: this.store,
            startDate: new Date(),
            numOfDays: 7,
            readOnly: true
        });

        this.calendarView.getSelectionModel().on('selectionchange', this.onViewSelectionChange, this);
        this.store.on('beforeload', this.onStoreBeforeload, this);
        this.store.on('load', this.onStoreLoad, this);

        this.detailsPanel = new Tine.Calendar.EventDetailsPanel();

        this.freeTimeSlots = [];

        this.tbar = [{
            text: i18n._('Back'),
            minWidth: 70,
            ref: '../buttonBack',
            iconCls: 'action_previous',
            scope: this,
            disabled: true,
            handler: this.onButtonBack
        }, {
            text: i18n._('Next'),
            minWidth: 70,
            ref: '../buttonNext',
            iconCls: 'action_next',
            scope: this,
            handler: this.onButtonNext
        }, '-', {
            text: i18n._('Options'),
            minWidth: 70,
            ref: '../buttonOptions',
            iconCls: 'action_options',
            scope: this,
            handler: this.onButtonOptions
        }];

        this.fbar = ['->', {
            text: i18n._('Cancel'),
            minWidth: 70,
            ref: '../buttonCancel',
            scope: this,
            handler: this.onButtonCancel,
            iconCls: 'action_cancel'
        }, {
            text: i18n._('Ok'),
            minWidth: 70,
            ref: '../buttonApply',
            scope: this,
            handler: this.onButtonApply,
            iconCls: 'action_saveAndClose'
        }];

        this.items = [{
            layout: 'border',
            border: false,
            items: [{
                region: 'center',
                layout: 'fit',
                border: false,
                items: this.calendarView
            }, {
                region: 'south',
                border: false,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                split: true,
                layout: 'fit',
                height: this.detailsPanel.defaultHeight ? this.detailsPanel.defaultHeight : 125,
                items: this.detailsPanel
            }]
        }];

        Tine.Calendar.FreeTimeSearchDialog.superclass.initComponent.call(this);

        this.constraints = Ext.state.Manager.get(this.optionsStateId, Tine.Calendar.EventFinderOptionsDialog.prototype.defaultOptions);
        this.doFreeTimeSearch();
    },

    afterRender: function() {
        Tine.Calendar.FreeTimeSearchDialog.superclass.afterRender.call(this);

        this.loadMask = new Ext.LoadMask(this.getEl(), {msg: this.app.i18n._("Searching for Free Time...")});
        this.loadMask.show.defer(100, this.loadMask);
    },

    applyOptions: function(constraints) {
        // nothing to do
        if (Ext.encode(this.constraints) == Ext.encode(constraints)) return;

        this.constraints = constraints;
        this.doFreeTimeSearch()
    },

    doFreeTimeSearch: function(from) {
        if (this.loadMask) {
            this.loadMask.show();
        }

        var _ = window.lodash,
            datePart = this.record.get('dtstart').add(Date.DAY, -7).format('Y-m-d '),
            groupedConstraints = _.groupBy(_.filter(this.constraints, {active: true}), function(constraint) {
                return constraint.period.from + '-' + constraint.period.until;
            }),
            constraints = _.reduce(groupedConstraints, function(result, constraintsGroup) {
                return result.concat({
                    dtstart: datePart + constraintsGroup[0].period.from,
                    dtend: datePart + constraintsGroup[0].period.until,
                    rrule: 'FREQ=WEEKLY;INTERVAL=1;BYDAY=' + _.map(constraintsGroup, 'id').join(',')
                });
            }, []),
            options = {
                from: from ? from : this.record.get('dtstart'),
                constraints: constraints
            };

        Tine.Calendar.searchFreeTime(this.record.data, options, this.onFreeTimeData, this);
    },

    onFreeTimeData: function(result) {
        if (result.timeSearchStopped) {
            return this.onFreeTimeSearchTimeout(result);
        }

        var _ = window.lodash,
            timing = _.get(result, 'results[0]'),
            dtStart = new Date(timing.dtstart),
            from = dtStart.clearTime(true).add(Date.DAY, -1 * dtStart.getDay())
                .add(Date.DAY, Ext.DatePicker.prototype.startDay - (dtStart.getDay() == 0 ? 7 : 0)),
            dtEnd = new Date(timing.dtend),
            until = from.add(Date.DAY, 7),
            period = {from: from, until: until},
            filterHash = Ext.encode(this.store.baseParams.filter);

        this.store.remove(this.record);
        this.record.set('dtstart', dtStart);
        this.record.set('dtend', dtEnd);

        this.freeTimeSlot = result;

        // calculate period start
        if (Ext.DatePicker.prototype.startDay) {
            from = from.add(Date.DAY, Ext.DatePicker.prototype.startDay - (dtStart.getDay() == 0 ? 7 : 0));
        }

        this.store.baseParams.filter = [
            {field: 'period', operator: 'within', value: period},
            {field: 'attender', operator: 'in', value: this.record.get('attendee')},
            {field: 'attender_status', operator: 'notin', value: 'DECLINED'}
        ];

        if (this.record.get('id')) {
            this.store.baseParams.filter.push({field: 'id', operator: 'not', value: this.record.get('id')});
        }

        if (filterHash == Ext.encode(this.store.baseParams.filter)) {
            this.onStoreLoad();
        } else {
            this.calendarView.updatePeriod(period);
            this.store.load();
        }
    },

    onFreeTimeSearchTimeout: function(result) {
        var _ = window.lodash,
            dtStopped = new Date(_.get(result, 'timeSearchStopped')),
            stoppedString = Tine.Tinebase.common.dateRenderer(dtStopped);

        Tine.widgets.dialog.MultiOptionsDialog.openWindow({
            title: this.app.i18n._('No Free Time found'),
            questionText: String.format(this.app.i18n._('No free timeslot could be found Until {0}. Do you want to continue?'), '<b>' + stoppedString + '</b>') + '<br><br>',
            height: 170,
            scope: this,
            options: [
                {text: this.app.i18n._('Continue'), name: 'continue'},
                {text: this.app.i18n._('Give up'), name: 'giveUp'}
            ],

            handler: function(option) {
                switch(option) {
                    case 'giveUp':
                        this.onButtonCancel();
                        break;
                    case 'continue':
                        this.doFreeTimeSearch(new Date(dtStopped));
                        break;
                }
            }
        });
    },

    onStoreBeforeload: function() {
        if (this.loadMask) {
            this.loadMask.show();
        }
    },

    onStoreLoad: function() {
        this.store.each(function(event) {
            if (event.ui) {
                event.ui.setOpacity(0.5, 0);
            }
        }, this);

        this.store.add([this.record]);

        this.record.ui.setOpacity(1, 0);

        // @TODO check if event is visible and skip scrolling -> defered scrolling looks ugly
        // this.calendarView.scrollTo.defer(500, this.calendarView, [this.record.get('dtstart')]);

        this.loadMask.hide();
    },

    onViewSelectionChange: function(sm, selections) {
        this.detailsPanel.onDetailsUpdate(sm);
    },

    onButtonBack: function() {
        this.onFreeTimeData(this.freeTimeSlots.pop());
        this.buttonBack.setDisabled(!this.freeTimeSlots.length);
    },

    onButtonNext: function() {
        this.freeTimeSlots.push(this.freeTimeSlot);

        this.doFreeTimeSearch(this.record.get('dtend'));
        this.buttonBack.enable();
    },

    onButtonOptions: function() {
        Tine.Calendar.EventFinderOptionsDialog.openWindow({
            titleText: this.app.i18n._('Free Time Search Options'),
            stateId: this.optionsStateId,
            recordId: this.record.id,
            listeners: {
                scope: this,
                apply: this.onOptionsApply
            }
        });
    },

    onOptionsApply: function(dialog, options) {
        this.applyOptions(Ext.decode(options));
    },

    onButtonCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },

    onButtonApply: function() {
        this.fireEvent('apply', this, Ext.encode(this.record.data));
        this.purgeListeners();
        this.window.close();
    }
});

/**
 * Opens a new free time serach dialog window
 *
 * @return {Ext.ux.Window}
 */
Tine.Calendar.FreeTimeSearchDialog.openWindow = function (config) {
    var _ = window.lodash;

    if (! _.isString(config.record)) {
        config.record = Ext.encode(config.record.data);
    }

    return Tine.WindowFactory.getWindow({
        width: 1024,
        height: 768,
        name: Tine.Calendar.FreeTimeSearchDialog.prototype.windowNamePrefix + _.get(config, 'record.id', 0),
        contentPanelConstructor: 'Tine.Calendar.FreeTimeSearchDialog',
        contentPanelConstructorConfig: config
    });
};