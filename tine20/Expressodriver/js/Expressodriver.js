/*
 * Tine 2.0
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 */

Ext.ns('Tine.Expressodriver');

/**
 * @namespace Tine.Expressodriver
 * @class Tine.Expressodriver.Application
 * @extends Tine.Tinebase.Application
 */
Tine.Expressodriver.Application = Ext.extend(Tine.Tinebase.Application, {
    /**
     * @return {Boolean}
     */
    init: function () {
        Tine.log.info('Initialize Expressodriver');

        if (! Tine.Tinebase.common.hasRight('run', 'Expressodriver', 'main_screen')) {
            Tine.log.debug('No mainscreen right for Expressodriver');
            this.hasMainScreen = false;
        }
    },

    /**
     * Get translated application title of this application
     *
     * @return {String}
     */
    getTitle : function() {
        return this.i18n.gettext('Expressodriver');
    }
});

/*
 * register additional action for genericpickergridpanel
 */
Tine.widgets.relation.MenuItemManager.register('Expressodriver', 'Node', {
    text: 'Save locally',   // _('Save locally')
    iconCls: 'action_expressodriver_save_all',
    requiredGrant: 'readGrant',
    actionType: 'download',
    allowMultiple: false,
    handler: function(action) {
        var node = action.grid.store.getAt(action.gridIndex).get('related_record');
        var downloadPath = node.path;
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Expressodriver.downloadFile',
                requestType: 'HTTP',
                id: '',
                path: downloadPath
            }
        }).start();
    }
});

/**
 * @namespace Tine.Expressodriver
 * @class Tine.Expressodriver.MainScreen
 * @extends Tine.widgets.MainScreen
 */
Tine.Expressodriver.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Node'
});