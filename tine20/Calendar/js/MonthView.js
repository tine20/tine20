/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.MonthView = function(config){
    Ext.apply(this, config);
    Tine.Calendar.MonthView.superclass.constructor.call(this);
    
    this.addEvents(
        /**
         * @event addEvent
         * fired when a new event got inserted
         * 
         * @param {Tine.Calendar.Event} event
         */
        'addEvent',
        /**
         * @event updateEvent
         * fired when an event go resised/moved
         * 
         * @param {Tine.Calendar.Event} event
         */
        'updateEvent'
    );
};

Ext.extend(Tine.Calendar.MonthView, Ext.util.Observable, {
    /**
     * @cfg {Date} startDate
     * start date
     */
    startDate: new Date(),
    /**
     * @cfg {String} newEventSummary
     */
    newEventSummary: 'New Event',
    /**
     * @cfg {String} calWeekString
     */
    calWeekString: 'WK',
    /**
     * @cfg {Array} monthNames
     * An array of textual month names which can be overriden for localization support (defaults to Date.monthNames)
     */
    monthNames : Date.monthNames,
    /**
     * @cfg {Array} dayNames
     * An array of textual day names which can be overriden for localization support (defaults to Date.dayNames)
     */
    dayNames : Date.dayNames,
    /**
     * @cfg {Number} startDay
     * Day index at which the week should begin, 0-based
     */
    startDay: Ext.DatePicker.prototype.startDay,
    /**
     * @private {Array} dateMesh
     */
    dateMesh: null,
    
    init: function(calPanel) {
        this.calPanel = calPanel;
        
        this.calcDateMesh(this.startDate);
        
        this.startDate.setHours(0);
        this.startDate.setMinutes(0);
        this.startDate.setSeconds(0);
        
        //this.initData(calPanel.store);
    },
    
    /**
     * @private calculates mesh of dates for month this.startDate is in
     */
    calcDateMesh: function() {
        var mesh = [];
        var d = Date.parseDate(this.startDate.format('Y-m') + '-01 00:00:00', Date.patterns.ISO8601Long);
        while(d.getDay() != this.startDay) {
            d = d.add(Date.DAY, -1);
        }
        
        while(d.getMonth() != this.startDate.add(Date.MONTH, 1).getMonth()) {
            var meshRow = [];
            for (var i=0; i<7; i++) {
                meshRow.push(d.add(Date.DAY, i).clone());
            }
            d = d.add(Date.DAY, 7);
            mesh.push(meshRow);
        }

        this.dateMesh = mesh;
    },
    
    /**
     * @private
     * @param {Ext.data.Store} ds
     */
    initData : function(ds){
        if(this.ds){
            this.ds.un("beforeload", this.onBeforeLoad, this);
            this.ds.un("load", this.onLoad, this);
            this.ds.un("datachanged", this.onDataChange, this);
            this.ds.un("add", this.onAdd, this);
            this.ds.un("remove", this.onRemove, this);
            this.ds.un("update", this.onUpdate, this);
            this.ds.un("clear", this.onClear, this);
        }
        if(ds){
            ds.on("beforeload", this.onBeforeLoad, this);
            ds.on("load", this.onLoad, this);
            ds.on("datachanged", this.onDataChange, this);
            ds.on("add", this.onAdd, this);
            ds.on("remove", this.onRemove, this);
            ds.on("update", this.onUpdate, this);
            ds.on("clear", this.onClear, this);
        }
        this.ds = ds;
    },
    
    updatePeriode: function(period) {
        var dayHeaders = Ext.DomQuery.select('div[class=cal-monthview-dayheader-inner]');
        for(var i=0; i<dayHeaders.length; i++) {
            dayHeaders[i].innerHTML = i;
        }
    },
    
    render: function() {
        var m = [
             '<table class="cal-monthview-inner" cellspacing="0"><thead><tr>',
             "<th><span>", this.calWeekString, "</span></th>"
         ];
        for(var i = 0; i < 7; i++){
            var d = this.startDay+i;
            if(d > 6){
                d = d-7;
            }
            m.push("<th><span>", this.dayNames[d], "</span></th>");
        }
        m[m.length] = "</tr></thead><tbody><tr><td class='cal-monthview-wk'></td>";
        for(var i = 0; i < 42; i++) {
            if(i % 7 == 0 && i != 0){
                m[m.length] = "</tr><tr><td class='cal-monthview-wk'></td>";
            }
            m[m.length] = 
                '<td>' +
                    '<div class="cal-monthview-dayheader">' +
                        '<div class="cal-monthview-dayheader-inner"></div>' +
                    '</div>' +
                    '<div class="cal-monthview-daybody"></div>' +
                '</td>';
        }
        m.push('</tr></tbody></table></td></tr>');
                
        var el = this.calPanel.body.dom;
        el.className = "cal-monthview";
        el.innerHTML = m.join("");

        //container.dom.insertBefore(el, position);
        //this.calPanel.body
    },
    
    afterRender: function() {
        this.initElements();
        this.updatePeriode();
    },
    
    layout: function() {
        if(!this.mainBody){
            return; // not rendered
        }
        
        var g = this.calPanel;
        var c = g.body;
        var csize = c.getSize(true);
        var vw = csize.width;
        
        this.el.setSize(csize.width, csize.height);
        
        var hdCels = this.mainHd.dom.firstChild.childNodes;
        
        
        Ext.fly(hdCels[0]).setWidth(50);
        for (var i=1; i<hdCels.length; i++) {
            Ext.fly(hdCels[i]).setWidth((vw-50)/7);
        }
    },
    
    initElements: function() {
        var E = Ext.Element;

        var el = this.calPanel.body.dom.firstChild;
        var cs = el.childNodes;

        this.el = new E(el);
        
        this.mainHd = new E(this.el.dom.firstChild);
        this.mainBody = new E(this.el.dom.lastChild);
    },
    
    /**
     * inits all tempaltes of this view
     */
    initTemplates: function() {
        var ts = this.templates || {};
    
        ts.master = new Ext.XTemplate(
        );
        
        for(var k in ts){
            var t = ts[k];
            if(t && typeof t.compile == 'function' && !t.compiled){
                t.disableFormats = true;
                t.compile();
            }
        }

        this.templates = ts;
    }
    
});