/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.TimelineViewEventUI = Ext.extend(Tine.Calendar.EventUI, {
    render: function(view) {
        Tine.Calendar.TimelineViewEventUI.superclass.render.call(this, view);

        var from = view.period.from.getTime(),
            until = view.period.until.getTime(),
            start = Math.max(from, this.dtStart.getTime()),
            end = Math.min(until, this.dtEnd.getTime()),
            length = end - start,
            f = view.scalingFactor,
            pos = this.event.parallelEventRegistry.position;

        this.zIndex = pos * 100;

        var data = {
            id: view.id + '-event:' + this.event.get('id'),
            width: f * length +'%',
            height: 15 +'px',
            left: f * (start - from) +'%',
            top: 3 + (view.collapsed ? 0 : pos * 23) +'px',
            tagsHtml: Tine.Tinebase.common.tagsRenderer(this.event.get('tags')),
            summary: this.event.get('summary'),
            startTime: this.dtStart.format('H:i'),
            extraCls: this.extraCls,
            color: this.colorSet.color,
            bgColor: this.colorSet.light,
            textColor: this.colorSet.text,
            zIndex: this.zIndex,
            statusIcons: this.statusIcons
        };

        view.templates.event.insertFirst(view.getEl(), data, true);
        this.domIds.push(data.id);
    }
});