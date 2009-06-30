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
        
        this.title = this.app.i18n._('Recuring');
        
        this.freqCombo = new Ext.form.ComboBox({
            triggerAction : 'all',
            fieldLabel    : this.app.i18n._('Recurrance'),
            value         : false,
            editable      : false,
            mode          : 'local',
            store         : [
                [false,      this.app.i18n._('None')   ],
                ['DAILY',    this.app.i18n._('Daily')   ],
                ['WEEKLY',   this.app.i18n._('Weekly')  ],
                ['MONTHLY',  this.app.i18n._('Monthly') ],
                ['YEARLY',   this.app.i18n._('Yearly')  ]
            ] 
            
        });
        
        this.items = [
            this.freqCombo
        ];
        
        Tine.Calendar.RrulePanel.superclass.initComponent.call(this);
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        console.log(this.record.rrule);
        
    },
    
    onRecordUpdate: function(record) {
        console.log(this.record.rrule);
    }
});