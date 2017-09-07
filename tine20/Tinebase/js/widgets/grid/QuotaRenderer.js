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

    var data = {
        usage: usage,
        percent: limit ? Math.round(usage/limit * 100) : null,
        quota: Tine.Tinebase.configManager.get('quota'),
        cls: limit ?
            'PercentRenderer-progress-barBelow':
            'QuotaRenderer-unlimited',
        qtip: limit ? (String.format(i18n._('{0} available (total: {1})'),
            Tine.Tinebase.common.byteRenderer(limit - usage),
            Tine.Tinebase.common.byteRenderer(limit))) : ''
    };

    // this will enable a color scheme for soft quota or reached limit
    if (limit && useSoftQuota && data.percentage >= data.quota.softQuota) {
        data.cls = "PercentRenderer-progress-barOver";
    }
    if (limit && data.usage >= limit) {
        data.cls = "PercentRenderer-progress-barLimit";
    }

     return Tine.widgets.grid.QuotaRenderer.tpl.apply(data);
};
