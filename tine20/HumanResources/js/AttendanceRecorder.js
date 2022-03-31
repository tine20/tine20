/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/* HumanResources_Model_AttendanceRecorderDevice */
const FLD_ALLOW_MULTI_START = 'allowMultiStart';
const FLD_ALLOW_PAUSE = 'allowPause';
const FLD_BLPIPE = 'blpipe';
const FLD_IS_TINE_UI_DEVICE = 'is_tine_ui_device';
const FLD_NAME = 'name';
const FLD_STOPS = 'stops';
const FLD_STARTS = 'starts';

const SYSTEM_WORKING_TIME_ID = 'wt00000000000000000000000000000000000000';
const SYSTEM_PROJECT_TIME_ID = 'pt00000000000000000000000000000000000000';

/* HumanResources_Model_AttendanceRecord */
const STATUS_CLOSED = 'closed';
const STATUS_FAULTY = 'faulty';
const STATUS_OPEN = 'open';

const TYPE_CLOCK_IN = 'clock_in';
const TYPE_CLOCK_OUT = 'clock_out';
const TYPE_CLOCK_PAUSED = 'clock_paused';

const FLD_ACCOUNT_ID = 'account_id';
const FLD_AUTOGEN = 'autogen';
const FLD_BLPROCESSED = 'blprocessed';
const FLD_CREATION_CONFIG = 'creation_config';
const FLD_DEVICE_ID = 'device_id';
const FLD_FREETIMETYPE_ID = 'freetimetype_id';
const FLD_SEQUENCE = 'sequence';
const FLD_STATUS = 'status';
const FLD_TIMESTAMP = 'ts';
const FLD_TYPE = 'type';
const FLD_REFID = 'refId';

const META_DATA = 'metaData';
const CLOCK_OUT_GRACEFULLY = 'clockOutGracefully';

const attendanceRecorder = Ext.extend(Ext.Button, {

    /**
     * @cfg {Number} autoRefreshInterval (seconds)
     */
    autoRefreshInterval: 300,

    /**
     * @property autoRefreshTask
     * @type Ext.util.DelayedTask
     */
    autoRefreshTask: null,

    // internal config
    showIcon: true,
    iconCls: 'attendance-clock',
    stateful: true,
    stateId: 'attendance-clock',

    // properties
    ptAllowPause: true,
    ptAllowMultiStart: true,

    initComponent() {
        const me = this;
        this.app = Tine.Tinebase.appMgr.get('HumanResources');

        this.actionClockIn = new Ext.Action({
            text: this.app.i18n._('Clock in'),
            iconCls: 'clock-in',
            handler: _.bind(this.onWTClock, this, 'clockIn', null),
            disabled: true,
        });

        this.actionClockPause = new Ext.Action({
            text: this.app.i18n._('Absence'),
            iconCls: 'clock-break',
            handler: this.onClockPause,
            disabled: true,
            scope: this
        });

        this.actionClockOut = new Ext.Action({
            text: this.app.i18n._('Clock out'),
            iconCls: 'clock-out',
            handler:_.bind(this.onWTClock, this, 'clockOut', null),
            disabled: true,
        });

        this.actionESC = new Ext.Action({
            text: this.app.i18n._('ESC'),
            iconCls: 'clock-esc',
            handler: this.onESC,
            scope: this
        });

        this.actionProjectTime = new Ext.Action({
            text: this.app.i18n._('Project Time'),
            iconCls: 'clock-projecttime',
            handler: this.onProjectTime,
            // disabled: true,
            scope: this
        });

        this.actionInfo = new Ext.Action({
            text: this.app.i18n._('Info'),
            iconCls: 'clock-info',
            handler: this.onInfo,
            // disabled: true,
            scope: this
        });

        const defaults = {
            scale: 'medium',
            width: 100,
            rowspan: 2,
            iconAlign: 'top',
        };
        this.menu = new Ext.menu.Menu({
            cls: 'attendance-recorder',
            width: 300,
            height: 450,
            items: new Ext.Container({
                layout: 'vbox',
                layoutConfig: {
                    align:'stretch'
                },
                // height: 400,
                border: false,
                items: [{
                    height: 150,
                    // setHeight: function() {},
                    border: false,
                    bodyStyle: 'background-color: #f2f2f2;',
                    items: {
                        ref: '../../displayPanel',
                        style: 'margin: 10px; box-sizing: border-box;',
                        height: 130,
                        html: ''
                    }
                },{
                    title: this.app.i18n._('Working Time'),
                    height: 20
                },new Ext.Toolbar({
                    items: [
                        Object.assign(new Ext.Button(this.actionClockIn), defaults),
                        Object.assign(new Ext.Button(this.actionClockPause), defaults),
                        Object.assign(new Ext.Button(this.actionClockOut), defaults)
                    ]
                }),new Ext.Toolbar({
                    items: [
                        Object.assign(new Ext.Button(this.actionESC), defaults),
                        Object.assign(new Ext.Button(this.actionProjectTime), defaults),
                        Object.assign(new Ext.Button(this.actionInfo), defaults)
                    ]
                }), new Tine.widgets.grid.PickerGridPanel({
                    border: false,
                    title: this.app.i18n._('Project Time'),
                    ref: '../timeAccountPickerGrid',
                    flex: 1,
                    recordClass: 'Timetracker.Timeaccount',
                    hideHeaders: true,
                    enableBbar: false,
                    editDialogClass: false,
                    getColumnModel: function() {
                        const colModel = Tine.widgets.grid.PickerGridPanel.prototype.getColumnModel.call(this);
                        colModel.columns.unshift({
                            width: 65,
                            id: 'buttons',
                            renderer: (value, metaData, record) => {
                                const type = _.get(record, 'data.xprops.HumanResources_Model_AttendanceRecord.type', TYPE_CLOCK_OUT);

                                return `<div class="tine-row-action-icons">
                                            <div class="tine-recordclass-gridicon ${type === TYPE_CLOCK_IN ? 'x-item-disabled' : ''} project-clock-in">&nbsp;</div>
                                            <div class="tine-recordclass-gridicon ${!me.ptAllowPause || type !== TYPE_CLOCK_IN ? 'x-item-disabled' : ''} ${true || me.ptAllowPause ? '' : 'x-hidden'} project-clock-pause"></div>
                                            <div class="tine-recordclass-gridicon ${type === TYPE_CLOCK_OUT ? 'x-item-disabled' : ''} project-clock-out">&nbsp;</div>
                                        </div>`;
                            }
                        }, {
                            width: 40,
                            // reziseable: false,
                            renderer: (value, metaData, record) => {
                                const type = _.get(record, 'data.xprops.HumanResources_Model_AttendanceRecord.type', TYPE_CLOCK_OUT);
                                if (type === TYPE_CLOCK_OUT) return `--:--`;

                                const {time, lastClockIn} = _.get(record, 'data.xprops.HumanResources_Model_AttendanceRecord.records', []).reduce((a, record) => {
                                    const lastClockIn = record[FLD_TYPE] === TYPE_CLOCK_IN ? record[FLD_TIMESTAMP] : a.lastClockIn;
                                    return Object.assign(a, {
                                        lastClockIn: lastClockIn,
                                        time: a.time + (record[FLD_TYPE] !== TYPE_CLOCK_IN ? Date.parseDate(record[FLD_TIMESTAMP], Date.patterns.ISO8601Long).getTime() - Date.parseDate(lastClockIn, Date.patterns.ISO8601Long).getTime() : 0)
                                    });
                                }, {time: 0, lastClockIn: 0});
                                const duration = time + (type === TYPE_CLOCK_IN ? (new Date().getTime() - Date.parseDate(lastClockIn, Date.patterns.ISO8601Long).getTime()) : 0);

                                window.setTimeout(() => {
                                    me.menu.timeAccountPickerGrid.view.refresh()
                                }, 60000 - Math.floor(duration)%60000);

                                return  Ext.ux.form.DurationSpinner.durationRenderer(duration, { baseUnit: 'milliseconds' }).replace(':',
                                    `<span style="font-weight: bolder;" class="${type === TYPE_CLOCK_IN ? 'attendance-clock-blink' : 'attendance-clock-stale'}">\u2236</span>`);;
                            }
                        })
                        return colModel;
                    },
                    listeners: { change: (pgp, value, old) => {
                        this.saveState();
                    }}
                })]
            })
        });
        this.menu.on('show', this.showClock, this);
        this.menu.on('hide', this.hideClock, this);
        this.menu.on('beforehide', () => {
            const [x ,y] = Ext.EventObject.getXY();
            const box = this.menu.el.getBox();
            return !((x>box.x && x<box.x+box.width && y>box.y && y<box.y+box.height)
                || Ext.EventObject.getTarget('.x-combo-selected'));
        }, this);
        this.menu.on('render', () => {
            this.menu.mon(this.menu.el, { 'click': this.onMenuClick, scope: this });
        })

        this.autoRefreshTask = new Ext.util.DelayedTask(this.applyDeviceStates, this);
        this.autoRefreshTask.delay(2000);

        this.sounds = {
            shortbeep: new Audio('Tinebase/assets/sounds/shortbeep.mp3'),
            failure: new Audio('Tinebase/assets/sounds/failure.mp3'),
        };

        Tine.Tinebase.UploadManagerStatusButton.superclass.initComponent.call(this);
    },

    // component state
    getState() {
        let state = this.state || {};
        if (this.menu.timeAccountPickerGrid) {
            state.timeaccounts = _.map(this.menu.timeAccountPickerGrid.getValue(), 'id');
        }
        return state;
    },

    // component state
    applyState(state) {
        if (_.get(state, 'timeaccounts.length', 0) > 0) {
            Tine.Timetracker.searchTimeaccounts([{field: 'id', operator: 'in', value: state.timeaccounts}]).then((timeaccounts) => {
                this.menu.timeAccountPickerGrid.store.suspendEvents();
                state.timeaccounts.forEach((timeaccount) => {
                    const data = _.find(timeaccounts.results, {id: timeaccount});
                    if (data) {
                        this.menu.timeAccountPickerGrid.store.add(Tine.Tinebase.data.Record.setFromJson(data, Tine.Timetracker.Model.Timeaccount));
                    }
                });
                this.menu.timeAccountPickerGrid.store.resumeEvents();
            });
        }
    },

    async applyDeviceStates() {
        this.autoRefreshTask.delay(this.autoRefreshInterval * 1000);
        const { results: deviceRecords } = await Tine.HumanResources.getAttendanceRecorderDeviceStates();

        const wtDeviceRecord = _.findLast(deviceRecords, { [FLD_DEVICE_ID]: { id: SYSTEM_WORKING_TIME_ID } });
        this.wtType = _.get(wtDeviceRecord, FLD_TYPE, TYPE_CLOCK_OUT); // NOTE: wt device is allowMultiStart === 0
        this.freeTimeType = _.get(wtDeviceRecord, FLD_FREETIMETYPE_ID);
        const wtAllowPause = !!+_.get(wtDeviceRecord, `device_id.${FLD_ALLOW_PAUSE}`, '0');

        this.actionClockIn.setDisabled(this.wtType === TYPE_CLOCK_IN);
        this.actionClockPause.setDisabled(!wtAllowPause || this.wtType !== TYPE_CLOCK_IN);
        this.actionClockOut.setDisabled(this.wtType === TYPE_CLOCK_OUT);

        // NOTE: pt device is allowMultiStart === 1
        const ptDeviceRecords = _.filter(deviceRecords, { [FLD_DEVICE_ID]: { id: SYSTEM_PROJECT_TIME_ID } });
        const missingTimeAccounts = _.difference(_.compact(_.uniq(_.map(ptDeviceRecords, `xprops.${META_DATA}.Timetracker_Model_Timeaccount`))), this.menu.timeAccountPickerGrid.store.data.keys)
        if (missingTimeAccounts.length) {
            const { results: timeAccounts } = await Tine.Timetracker.searchTimeaccounts([{field: 'id', operator: 'in', value: missingTimeAccounts}]);
            timeAccounts.forEach((timeAccount) => { this.menu.timeAccountPickerGrid.store.add(Tine.Tinebase.data.Record.setFromJson(timeAccount, Tine.Timetracker.Model.Timeaccount)) });
        }

        this.menu.timeAccountPickerGrid.store.suspendEvents();
        this.menu.timeAccountPickerGrid.store.each((record) => {
            record.set('xprops', Object.assign(record.get('xprops') || {}, { HumanResources_Model_AttendanceRecord: { } }));
        });

        _.forEach(_.groupBy(ptDeviceRecords, FLD_REFID), (records, refId) => {
            // records per timeaccount
            records = _.sortBy(records);
            const top = _.last(records);
            const timeAccountId = _.get(_.compact(_.uniq(_.map(records, `xprops.${META_DATA}.Timetracker_Model_Timeaccount`))), [0]);
            const record = this.menu.timeAccountPickerGrid.store.getById(timeAccountId);
            record.set('xprops', Object.assign(record.get('xprops') || {}, { HumanResources_Model_AttendanceRecord: { top, records,
                type: _.get(top, FLD_TYPE, TYPE_CLOCK_OUT),
            }}));
        });
        // @FIXME this is bad as we don't get device config w.o. records!
        this.ptAllowPause = !!+_.get(_.first(ptDeviceRecords), `device_id.${FLD_ALLOW_PAUSE}`, '0');
        this.ptAllowMultiStart = !!+_.get(_.first(ptDeviceRecords), `device_id.${FLD_ALLOW_MULTI_START}`, '0');
        // this.menu.timeAccountPickerGrid.colModel.setColumnWidth(this.menu.timeAccountPickerGrid.colModel.getIndexById('buttons'), this.ptAllowPause ? 65 : 45, true);

        this.menu.timeAccountPickerGrid.store.resumeEvents();
        this.menu.timeAccountPickerGrid.view.refresh();
    },

    async onWTClock(fn, options, btn) {
        _.defer(_.bind(btn.setDisabled, btn, true))
        this.sounds.shortbeep.play();
        this.hideClock();
        this.menu.displayPanel.update(`<div class="attendance-clock-msg">${this.app.i18n._('Data saving is performed ...')}</div>`);
        // let result
        try {
            const result = await Tine.HumanResources[fn](Object.assign({ [FLD_DEVICE_ID]: SYSTEM_WORKING_TIME_ID }, options));
            this.menu.displayPanel.update(`<div class="attendance-clock-msg">${this.app.i18n._('Data saving successful!')}</div>`);
            return result;
        } catch (e) {
            this.sounds.failure.play();
            this.menu.displayPanel.update(`<div class="attendance-clock-msg attendance-clock-error">${this.app.i18n._hidden(e.message).replace(/^((\S+\s+){4}\S+)\s+/, '$1<br>')}</div>`);
        } finally {
            _.delay(_.bind(this.showClock, this), 2000);
            this.applyDeviceStates();
        }
    },

    async onClockPause(btn) {
        this.hideClock();
        const menu = this.menu;
        const picker = this.displayCmp = Tine.widgets.form.RecordPickerManager.get('HumanResources', Tine.HumanResources.Model.FreeTimeType, {
            renderTo: this.menu.displayPanel.body,
            getListParent() { return menu.el },
            blurOnSelect: true,
            additionalFilters: [{field: 'allow_booking', operator: 'equals', value: true}],
            listWidth: 276,
            listeners: { select: (combo, type) => {
                this.onESC();
                this.onWTClock('clockPause', { [FLD_FREETIMETYPE_ID]: type.id }, btn)
            }}
        });
        picker.onTriggerClick();
    },

    onESC() {
        if (this.displayCmp) {
            this.displayCmp.destroy();
            this.displayCmp = null;
        }
        this.showClock();
    },

    onProjectTime() {
        this.menu.timeAccountPickerGrid.getSearchCombo().focus(true);
        // this.hideClock();

    },

    async onInfo(btn) {
        _.defer(_.bind(btn.setDisabled, btn, true))
        this.sounds.shortbeep.play();
        this.hideClock();
        this.menu.displayPanel.update(`<div class="attendance-clock-msg">${this.app.i18n._('Data loading is performed ...')}</div>`);

        try {
            const message = await Tine.HumanResources.wtInfo();
            this.menu.displayPanel.update(`<div class="attendance-clock-msg">${ Ext.util.Format.nl2br(message) }</div>`);

        } catch (e) {
            this.sounds.failure.play();
            this.menu.displayPanel.update(`<div class="attendance-clock-msg attendance-clock-error">${this.app.i18n._hidden(e.message)}</div>`);
        }

        _.delay(() => {
            this.showClock();
            btn.setDisabled(false);
        }, 3000);
    },

    async onMenuClick(e) {
        const el = e.getTarget('.tine-recordclass-gridicon');
        if (el) {
            const row = this.menu.timeAccountPickerGrid.view.findRowIndex(el);
            const timeAccount = this.menu.timeAccountPickerGrid.store.getAt(row);
            const action = Ext.fly(el).hasClass('project-clock-in') ? 'clockIn' : (Ext.fly(el).hasClass('project-clock-pause') ? 'clockPause' : 'clockOut');
            _.defer(() => { Ext.fly(el).addClass('x-item-disabled') });

            if (action === 'clockIn' && !this.ptAllowMultiStart) {
                // wtf - shoudn't this be handled by blpipe?
                const open = _.find(this.menu.timeAccountPickerGrid.store.data.items, (ta) => { return _.get(ta, `data.xprops.HumanResources_Model_AttendanceRecord.top.${FLD_STATUS}`) === STATUS_OPEN});
                if (open && open.id !== timeAccount.id) {
                    await this.onWTClock('clockOut', {
                        [FLD_DEVICE_ID]: SYSTEM_PROJECT_TIME_ID,
                        [FLD_REFID]: _.get(open, `data.xprops.HumanResources_Model_AttendanceRecord.top.${FLD_REFID}`),
                        xprops: {
                            [META_DATA]: {
                                'Timetracker_Model_Timeaccount': open.id }
                        }
                    }, this.actionProjectTime);
                }
            }

            const result = await this.onWTClock(action, {
                [FLD_DEVICE_ID]: SYSTEM_PROJECT_TIME_ID,
                [FLD_REFID]: _.get(timeAccount, `data.xprops.HumanResources_Model_AttendanceRecord.top.${FLD_REFID}`),
                xprops: {
                    [META_DATA]: {
                        [CLOCK_OUT_GRACEFULLY]: !(e.ctrlKey || e.shiftKey),
                        'Timetracker_Model_Timeaccount': timeAccount.id }
                }
            }, this.actionProjectTime);
            this.actionProjectTime.setDisabled(false);
        }
    },

    showClock() {
        const date = new Date();
        let wtStatus = this.wtType === TYPE_CLOCK_PAUSED ? `${this.app.i18n._('Away')}: ${this.app.i18n._hidden(this.freeTimeType.name)}`  : (this.wtType === TYPE_CLOCK_IN ?  this.app.i18n._('Clocked-in') : this.app.i18n._('Clocked-out'));
        this.menu.displayPanel.update(`
<div class="attendance-clock-status">${wtStatus}</div>
<div class="attendance-clock-msg attendance-clock-clock">${date.format('H')}<span class="attendance-clock-blink">\u2236</span>${date.format('i')}</div>
`);
        this.clockTimeout = window.setTimeout(this.showClock.createDelegate(this), 60000 - date.getSeconds()*1000 + date.getMilliseconds());
    },

    hideClock() {
        this.menu.displayPanel.update('');
        if (this.clockTimeout) {
            window.clearTimeout(this.clockTimeout);
        }
    },

    startProject(timeAccount) {

    }
});

Ext.ux.ItemRegistry.registerItem('Tine.Tinebase.MainMenu', attendanceRecorder, 10);
