/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.grid');


Tine.widgets.grid.QuotaRenderer = function(usage, limit, useSoftQuota) {
    // template singleton
    if (! Tine.widgets.grid.QuotaRenderer.tpl) {
        Tine.widgets.grid.QuotaRenderer.tpl = new Ext.XTemplate(
            '<div class="x-progress-wrap PercentRenderer" <tpl if="qtip">ext:qtitle="' + i18n._('Quota usage') + '" qwidth="200" qtip="{qtip}"></tpl>',
                '<div class="x-progress-inner PercentRenderer">',
                    '<div class="x-progress-bar PercentRenderer {cls}"<tpl if="Ext.isNumber(percent)"> style="width:{percent}%"</tpl>>',
                        '<div class="PercentRendererText PercentRenderer">',
                            '<div>{[Tine.Tinebase.common.byteRenderer(values.usage)]}</div>',
                        '</div>',
                    '</div>',
                    '<div class="x-progress-text x-progress-text-back PercentRenderer">',
                        '<div>&#160;</div>',
                    '</div>',
                '</div>',
            '</div>'
        ).compile();
    }

    usage = parseInt(usage);
    limit = parseInt(limit);

    var data = {
        usage: usage,
        limit: limit,
        percent: limit ? Math.round(usage/limit * 100) : null,
        quota: Tine.Tinebase.configManager.get('quota'),
        cls: limit ?
            'PercentBar-below':
            'QuotaRenderer-unlimited',
        qtip: limit ? (String.format(i18n._('{0} available (total: {1})'),
            Tine.Tinebase.common.byteRenderer(limit - usage),
            Tine.Tinebase.common.byteRenderer(limit))) : ''
    };

    // this will enable a color scheme for soft quota or reached limit
    if (data.limit && useSoftQuota && data.percent >= data.quota.softQuota) {
        data.cls = "PercentBar-over";
    }
    if (data.limit && data.usage >= data.limit) {
        data.cls = "PercentBar-limit";
    }

     return Tine.widgets.grid.QuotaRenderer.tpl.apply(data);
};
