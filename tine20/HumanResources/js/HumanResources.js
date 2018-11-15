/*
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.HumanResources');

Tine.HumanResources.Application = Ext.extend(Tine.Tinebase.Application, {

    hasMainScreen: true,

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
        Tine.CoreData.Manager.registerGrid(
            'hr_wtm',
            Tine.widgets.grid.GridPanel,
            {
                recordClass: Tine.HumanResources.Model.WorkingTime,
                app: this,
                initialLoadAfterRender: false,
                gridConfig: {
                    autoExpandColumn: 'title',
                    columns: [{
                        id: 'title',
                        header: this.i18n._("Title"),
                        width: 300,
                        sortable: true,
                        dataIndex: 'title'
                    }, {
                        id: 'evaluation_period_start',
                        header: this.i18n._("Work Start"),
                        width: 300,
                        sortable: true,
                        dataIndex: 'evaluation_period_start',
                        renderer: Tine.Tinebase.common.timeRenderer
                    }, {
                        id: 'evaluation_period_end',
                        header: this.i18n._("Work End"),
                        width: 300,
                        sortable: true,
                        dataIndex: 'evaluation_period_end',
                        renderer: Tine.Tinebase.common.timeRenderer
                    }]
                }
            }
        );
    }
});

/**
 * register special renderer for contract workingtime_json
 */
Tine.widgets.grid.RendererManager.register('HumanResources', 'Contract', 'workingtime_json', function(v) {
    if (! v) {
        return 0;
    }
    var object = Ext.decode(v);
    var sum = 0;
    for (var i=0; i < object.days.length; i++) {
        sum = sum + parseFloat(object.days[i]);
    }
    return sum;
});

Tine.widgets.grid.RendererManager.register('HumanResources', 'FreeTime', 'account_id', function(v) {
    if (! v) {
        return '';
    }
    
    return v.year;
});
