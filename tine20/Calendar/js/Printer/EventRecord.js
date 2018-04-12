Tine.Calendar.Printer.EventRenderer = Ext.extend(Ext.ux.Printer.EditDialogRenderer, {
    stylesheetPath: 'Calendar/css/print.css',

    generateBody: function(component, data) {
        var i18n = Tine.Tinebase.appMgr.get('Calendar').i18n;

        return new Promise(function (fulfill, reject) {
            var bodyTpl = new Ext.XTemplate(
                '<div class="cal-print-single">',
                    '<div class="cal-print-single-logo">',
                        '<img src="' + Tine.installLogo + '">',
                    '</div>',
                    '<div class="cal-print-single-summary">',
                        '<span class="cal-print-single-label">', i18n._('Summary'), '</span>',
                        '<span class="cal-print-single-value">{summary}</span>',
                    '</div>',
                    '<div class="cal-print-single-organizer">',
                        '<span class="cal-print-single-label">', i18n._('Organizer'), '</span>',
                        '<span class="cal-print-single-value">{values.organizer}</span>',
                    '</div>',
                    '<div class="cal-print-single-block-heading">', i18n._('Description'), '</div>',
                    '<div class="cal-print-single-block">',
                        '<div class="cal-print-single-description">{description}</div>',
                    '</div>',
                    // @TODO sort out css
                    // new Ext.ux.Printer.TagsRenderer().generateBody(component.getForm().findField('tags').tagsPanel),
                    '<div class="cal-print-single-block-heading">', i18n._('Details'), '</div>',
                    '<div class="cal-print-single-block">',
                        '<div class="print-single-details-row">',
                            '<span class="cal-print-single-label">', i18n._('Location'), '</span>',
                            '<span class="cal-print-single-value">{location}</span>',
                        '</div>',
                        '<div class="print-single-details-row">',
                            '<span class="cal-print-single-label">', i18n._('Start Time'), '</span>',
                            '<span class="cal-print-single-value">{values.dtstart}</span>',
                        '</div>',
                        '<div class="print-single-details-row">',
                            '<span class="cal-print-single-label">', i18n._('End Time'), '</span>',
                            '<span class="cal-print-single-value">{values.dtend}</span>',
                        '</div>',
                        // @TODO print rrule
                        '{[this.customFieldRenderer(values.customfields)]}',
                    '</div>',
                    '<div class="cal-print-single-block-heading">', i18n._('Attendee'), '</div>',
                    '<div class="cal-print-single-block">',
                        '{[this.attendeeRenderer(values.attendee)]}',
                    '</div>',
                '</div>',

                {
                    attendeeRenderer: Tine.Calendar.EventDetailsPanel.prototype.attendeeRenderer,
                    customFieldRenderer: function(values) {
                        return Tine.widgets.customfields.Renderer.renderAll('Calendar', Tine.Calendar.Model.Event, values);
                    }
                }
            );

            fulfill(bodyTpl.apply(data));
        });
    }
});
