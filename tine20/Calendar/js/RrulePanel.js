/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.RrulePanel = Ext.extend(Ext.Panel, {
    
    
    layout: 'form',
    frame: true,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.title = this.app.i18n._('Recurrances');
        
        this.freq = new Ext.form.ComboBox({
            triggerAction : 'all',
            hideLabel: true,
            fieldLabel    : this.app.i18n._('Recurrances'),
            value         : false,
            editable      : false,
            mode          : 'local',
            store         : [
                [false,      this.app.i18n._('Single Event')   ],
                ['DAILY',    this.app.i18n._('Daily')   ],
                ['WEEKLY',   this.app.i18n._('Weekly')  ],
                ['MONTHLY',  this.app.i18n._('Monthly') ],
                ['YEARLY',   this.app.i18n._('Yearly')  ]
            ]
        });
        
        this.interval = new Ext.form.TextField({
            fieldLabel    : this.app.i18n._('interval')
        });
        
        this.wkst = new Ext.form.TextField({
            fieldLabel    : this.app.i18n._('wkst')
        });
        
        this.byday = new Ext.form.TextField({
            fieldLabel    : this.app.i18n._('byday')
        });
        
        this.bymonth = new Ext.form.TextField({
            fieldLabel    : this.app.i18n._('bymonth')
        });
        
        this.bymonthday = new Ext.form.TextField({
            fieldLabel    : this.app.i18n._('bymonthday')
        });
        
        this.until = new Ext.form.TextField({
            fieldLabel    : this.app.i18n._('until')
        });
        
        this.items = [
            this.freq,
            this.interval,
            this.wkst,
            this.byday,
            this.bymonth,
            this.bymonthday,
            this.until
        ];
        
        Tine.Calendar.RrulePanel.superclass.initComponent.call(this);
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        this.rrule = this.record.get('rrule');
        
        for (var part in this.rrule) {
            if (this.rrule.hasOwnProperty(part) && this[part] && typeof this[part].setValue == 'function') {
                this[part].setValue(this.rrule[part]);
            }
        }
        
    },
    
    onRecordUpdate: function(record) {
        console.log(this.record.get('rrule'));
    }
});