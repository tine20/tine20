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

Ext.ns('Tine.ux.file');


Tine.ux.file.ProgressRenderer = function(current, total, useSoftQuota) {
    // template singleton
    if (! Tine.ux.file.ProgressRenderer.tpl) {
        Tine.ux.file.ProgressRenderer.tpl = new Ext.XTemplate(
            '<div class="x-progress-wrap PercentRenderer" <tpl if="qtip">ext:qtitle="' + i18n._('Upload progress') + '" qwidth="200" qtip="{qtip}"></tpl>',
                '<div class="x-progress-inner PercentRenderer">',
                    '<div class="x-progress-bar PercentRenderer {cls}"<tpl if="Ext.isNumber(percent)"> style="width:{percent}%"</tpl>>',
                        '<div class="PercentRendererText PercentRenderer">',
                            '<div>{[Tine.Tinebase.common.byteRenderer(values.current)]} / {[Tine.Tinebase.common.byteRenderer(values.total)]}</div>',
                        '</div>',
                    '</div>',
                    '<div class="x-progress-text x-progress-text-back PercentRenderer">',
                        '<div>&#160;</div>',
                    '</div>',
                '</div>',
            '</div>'
        ).compile();
    }

    current = parseInt(current);
    total = parseInt(total);

    var data = {
        current: current,
        total: total,
        percent: total ? Math.round(current/total * 100) : null,
        quota: Tine.Tinebase.configManager.get('quota'),
        cls: total ?
            'PercentBar-unlimited':
            'ProgressRenderer-below',
        qtip: total ? (String.format(i18n._('{0} available (total: {1})'),
            Tine.Tinebase.common.byteRenderer(total - current),
            Tine.Tinebase.common.byteRenderer(total))) : ''
    };
    
    if (data.total && data.current >= data.total) {
        data.cls = "PercentBar-below";
    }

     return Tine.ux.file.ProgressRenderer.tpl.apply(data);
};
