Ext.ns('Ext.ux');

/**
 * logging extension (only to console atm.)
 * 
 * NOTE: we extend the console objcect here so the console writer preserves 
 *       line number and files in its logging output!
 *       
 * @namespace   Ext.ux.log
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Ext.ux.log = console || {};

Ext.apply(console, {
    /**
     * @cfg {Number} prio (defaults to 7)
     */
    PRIO: 7,
    
    /**
     * possible log levels
     * @type Object
     */
    priorities: {
        EMERG:    0,  // Emergency: system is unusable
        ALERT:    1,  // Alert: action must be taken immediately
        CRIT:     2,  // Critical: critical conditions
        ERR:      3,  // Error: error conditions
        WARN:     4,  // Warning: warning conditions
        NOTICE:   5,  // Notice: normal but significant condition
        INFO:     6,  // Informational: informational messages
        DEBUG:    7   // Debug: debug messages
    },
    
    // only console writer atm.
    prioLogFnMap : {
        EMERG:    console.error,
        ALERT:    console.error,
        CRIT:     console.error,
        ERR:      console.error,
        WARN:     console.warn,
        NOTICE:   console.info,
        INFO:     console.info,
        DEBUG:    console.log
    },
    
    /**
     * returns current logging priority
     * 
     * @return {}
     */
    getPrio: function() {
        return this.PRIO;
    },
    
    /**
     * set logging priority
     * 
     * @namespace Ext.ux.log
     * @param {Number} prio
     */
    setPrio: function(prio) {
        this.PRIO = prio;
        for (var name in this.priorities) {
            this[name] = this[name.toLowerCase()] = prio >= this.priorities[name] ? this.prioLogFnMap[name] : function(){};
        }
    }
});

// init Ext.ux.log
Ext.onReady(function() {
    Ext.ux.log.setPrio(Ext.LOGLEVEL);
    Ext.ux.log.DEBUG('logger initialized');
});
