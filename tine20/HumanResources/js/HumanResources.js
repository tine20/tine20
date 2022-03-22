/*
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.HumanResources');

import './DailyWTReportGridPanel'
import './MonthlyWTReportGridPanel'
import './MonthlyWTReportEditDialog'
import './WTRCorrectionPicker'
import './WTRCorrectionEditDialog'
import './FreeTimeGridPanel'
import './FreeTimePlanningWestPanel'
import './FreeTimePlanningPanel'
import './DivisionEditDialog'
import './Timetracker/hooks'

Tine.HumanResources.Application = Ext.extend(Tine.Tinebase.Application, {

    hasMainScreen: true,

    init: function() {
        if (this.featureEnabled(('workingTimeAccounting'))) {
            Tine.widgets.MainScreen.registerContentType('HumanResources', {
                contentType: 'FreeTimePlanning',
                text: this.i18n._('Free Time Planning'),
                xtype: 'humanresources.freetimeplanning'
            });
        }
    },

    /**
     * Get translated application title of the HumanResources App
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('Human Resources', 'Human Resources', 1);
    },


    registerCoreData: function() {
        Tine.log.info('Tine.HumanResources.Application - registering core data ... ');
        Tine.CoreData.Manager.registerGrid('hr_wts', Tine.HumanResources.WorkingTimeSchemeGridPanel);
    }
});

/**
 * register special renderer for contract workingtime_json
 */
Tine.widgets.grid.RendererManager.register('HumanResources', 'Contract', 'workingtime_json', function(v, m, r) {
    var _ = window.lodash;
    // NOTE: workingtime_json is not longer used
    v = _.get(r, 'data.working_time_scheme.json', 0);

    if (! v) {
        return 0;
    }
    var object = Ext.isString(v) ? Ext.decode(v) : v;
    var sum = 0;
    for (var i=0; i < object.days.length; i++) {
        sum = sum + parseFloat(object.days[i]);
    }
    return sum/3600;
});

// working time schema translations
Tine.widgets.grid.RendererManager.register('HumanResources', 'WorkingTimeScheme', 'type', function(v) {
    var i18n = Tine.Tinebase.appMgr.get('HumanResources').i18n;
    switch(String(v)) {
        case 'template': v = i18n._('Template'); break;
        case 'individual': v = i18n._('Individual'); break;
        case 'shared': v = i18n._('Shared'); break;
    }

    return v;
});
