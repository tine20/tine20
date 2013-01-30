/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine, Locale*/

Ext.ns('Tine', 'Tine.Tinebase');

/**
 * static common helpers
 */
Tine.Tinebase.common = {
    
    /**
     * Open browsers native popup
     * 
     * @param {string}     windowName
     * @param {string}     url
     * @param {int}     width
     * @param {int}     height
     */
    openWindow: function (windowName, url, width, height) {
        // M$ IE has its internal location bar in the viewport
        if (Ext.isIE) {
            height = height + 20;
        }
        
        // chrome counts window decoration and location bar to window height
        if (Ext.isChrome) {
            height += 40;
        }
        
        windowName = Ext.isString(windowName) ? windowName.replace(/[^a-zA-Z0-9_]/g, '') : windowName;
        
        var    w, h, x, y, leftPos, topPos, popup;

        if (document.all) {
            w = document.body.clientWidth;
            h = document.body.clientHeight;
            x = window.screenTop;
            y = window.screenLeft;
        } else {
            if (window.innerWidth) {
                w = window.innerWidth;
                h = window.innerHeight;
                x = window.screenX;
                y = window.screenY;
            }
        }
        leftPos = ((w - width) / 2) + y;
        topPos = ((h - height) / 2) + x;
        

        try {
            popup = window.open(url, windowName, 'width=' + width + ',height=' + height + ',top=' + topPos + ',left=' + leftPos +
                ',directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=yes,dependent=no');
            
            return popup;
        }
        catch(e) {
            Tine.log.info('window.open Exception: ');
            Tine.log.info(e);
            
            popup = false;
            
        }
        
        if (! popup) {
            var openCode = "window.open('http://127.0.0.1/tine20/tine20/" + url + "','" + windowName + "','width=" + width + ",height=" + height + ",top=" + topPos + ",left=" + leftPos +
            ",directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=yes,dependent=no')";
        
            var exception = {
                openCode: openCode,
                popup: null
            };
            
            Tine.log.debug('openCode: ' + openCode);
            popup = openCode;
            
//            if(Tine.Tinebase.MainScreen.fireEvent('windowopenexception', exception) !== false){
//                // show message 'your popupblocker ... please click here'
//                // mhh how to make this syncron???
//                
//                // todo: review code in Ext.ux.PopupWindow...
//                popup = window;
//            } else {
//                popup = exception.popup;
//            }
        }
        
        return popup;
        
    },
    
    showDebugConsole: function () {
        if (! Ext.debug) {
            var head = document.getElementsByTagName("head")[0],
                scriptTag = document.createElement("script");
            
            scriptTag.setAttribute("src", 'library/ExtJS/src/debug.js');
            scriptTag.setAttribute("type", "text/javascript");
            head.appendChild(scriptTag);
            
            var scriptEl = Ext.get(scriptTag);
            scriptEl.on('load', function () {
                Ext.log('debug console initialised');
            });
            scriptEl.on('fail', function () {
                Ext.msg.alert('could not activate debug console');
            });
        } else {
            Ext.log('debug console reactivated');
        }
    },
    
    /**
     * Returns localised date and time string
     * 
     * @param {mixed} $_iso8601
     * @see Ext.util.Format.date
     * @return {String} localised date and time
     */
    dateTimeRenderer: function ($_iso8601) {
        var dateObj = $_iso8601 instanceof Date ? $_iso8601 : Date.parseDate($_iso8601, Date.patterns.ISO8601Long);
        
        return Ext.util.Format.date(dateObj, Locale.getTranslationData('Date', 'medium') + ' ' + Locale.getTranslationData('Time', 'medium'));
    },

    /**
     * Returns localised date string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {String} localised date
     */
    dateRenderer: function (date) {
        var dateObj = date instanceof Date ? date : Date.parseDate(date, Date.patterns.ISO8601Long);
        
        return Ext.util.Format.date(dateObj, Locale.getTranslationData('Date', 'medium'));
    },
    
    /**
     * Returns localised number string with two digits if no format is given
     * 
     * @param {Number} v The number to format.
     * @param {String} format The way you would like to format this text.
     * @see Ext.util.Format.number
     * 
     * @return {String} The formatted number.
     */
    floatRenderer: function(v, format) {
        if (! format) {
            // default format by locale and with two decimals
            format = '0' + Tine.Tinebase.registry.get('thousandSeparator') + '000' + Tine.Tinebase.registry.get('decimalSeparator') + '00'
        }
        return Ext.util.Format.number(v, format)
    },
    
    /**
     * Returns localised time string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {String} localised time
     */
    timeRenderer: function (date) {
        var dateObj = date instanceof Date ? date : Date.parseDate(date, Date.patterns.ISO8601Long);
        
        return Ext.util.Format.date(dateObj, Locale.getTranslationData('Time', 'medium'));
    },
    
    /**
     * renders bytes for filesize 
     * @param {Integer} value
     * @param {Object} metadata
     * @param {Tine.Tinebase.data.Record} record
     * @param {Integer} decimals
     * @param {Boolean} useDecimalValues
     * @return {String}
     */
    byteRenderer: function (value, metadata, record, decimals, useDecimalValues) {
        if (record && record.get('type') == 'folder') {
            return '';
        }
        return Tine.Tinebase.common.byteFormatter(value, null, decimals, useDecimalValues);
    },

    /**
     * format byte values
     * @param {String} value
     * @param {Boolean} forceUnit
     * @param {Integer} decimals
     * @param {Boolean} useDecimalValues
     */
    byteFormatter: function(value, forceUnit, decimals, useDecimalValues) {
        value = parseInt(value, 10);
        decimals = Ext.isNumber(decimals) ? decimals : 2;
        var suffix = ['Bytes', 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
            divisor = useDecimalValues ? 1000 : 1024;
            
        if (forceUnit) {
            var i = suffix.indexOf(forceUnit);
            i = (i == -1) ? 0 : i;
        } else {
            for (var i=0,j; i<suffix.length; i++) {
                if (value < Math.pow(divisor, i)) break;
            }
        }
        return ((i<=1) ? value : Ext.util.Format.round(value/(Math.pow(divisor, Math.max(1, i-1))), decimals)) + ' ' + suffix[i];
    },
    
    /**
     * Returns rendered tags for grids
     * 
     * @param {mixed} tags
     * @return {String} tags as colored squares with qtips
     * 
     * TODO add style for tag divs
     */
    tagsRenderer: function (tags) {
        var result = '';
        if (tags) {
            for (var i = 0; i < tags.length; i += 1) {
                var qtipText = Tine.Tinebase.common.doubleEncode(tags[i].name);
                if (tags[i].description) {
                    qtipText += ' | ' + Tine.Tinebase.common.doubleEncode(tags[i].description);
                }
                if(tags[i].occurrence) {
                    qtipText += ' (' + _('Usage:&#160;') + tags[i].occurrence + ')';
                }
                result += '<div ext:qtip="' + qtipText + '" class="tb-grid-tags" style="background-color:' + (tags[i].color ? tags[i].color : '#fff') + ';">&#160;</div>';
            }
        }
        
        return result;
    },
    
    /**
     * render single tag
     * 
     * @param {Tine.Tinebase.Model.Tag} tag
     */
    tagRenderer: function(tag) {
        if (! Tine.Tinebase.common.tagRenderer.tpl) {
            Tine.Tinebase.common.tagRenderer.tpl = new Ext.XTemplate(
                '<div class="tb-grid-tags" style="background-color:{values.color};">&#160;</div>',
                '<div class="x-widget-tag-tagitem-text" ext:qtip="', 
                    '{[this.encode(values.name)]}', 
                    '<tpl if="type == \'personal\' ">&nbsp;<i>(' + _('personal') + ')</i></tpl>',
                    '</i>&nbsp;[{occurrence}]',
                    '<tpl if="description != null && description.length &gt; 1"><hr>{[this.encode(values.description)]}</tpl>" >',
                    
                    '&nbsp;{[this.encode(values.name)]}',
                    '<tpl if="type == \'personal\' ">&nbsp;<i>(' + _('personal') + ')</i></tpl>',
                '</div>',
            {
                encode: function(value) {
                     if (value) {
                        return Tine.Tinebase.common.doubleEncode(value);
                    } else {
                        return '';
                    }
                }
            }).compile();
        }
        
        var result =  _('No Information');
        
        if (tag && Ext.isFunction(tag.beginEdit)) {
            // support records
            tag = tag.data;
        } else if (arguments[2] && Ext.isFunction(arguments[2].beginEdit)) {
            // support grid renderers
            tag = arguments[2].data;
        }
        
        // non objects are treated as ids and -> No Information
        if (Ext.isObject(tag)) {
            result = Tine.Tinebase.common.tagRenderer.tpl.apply(tag);
        }
        
        return result;
    },
    
    /**
     * Returns rendered containers
     * 
     * @TODO show qtip with grants
     * 
     * @param {mixed} container
     * @return {String} 
     */
    containerRenderer: function(container, metaData) {
        // lazy init tempalte
        if (! Tine.Tinebase.common.containerRenderer.tpl) {
            Tine.Tinebase.common.containerRenderer.tpl = new Ext.XTemplate(
                '<div class="x-tree-node-leaf x-unselectable file">',
                    '<img class="x-tree-node-icon" unselectable="on" src="', Ext.BLANK_IMAGE_URL, '">',
                    '<span style="color: {color};">&nbsp;&#9673;&nbsp</span>',
                    '<span> ', '{name}','</span>',
                '</div>'
            ).compile();
        }
        
        var result =  _('No Information');
        
        // support container records
        if (container && Ext.isFunction(container.beginEdit)) {
            container = container.data;
        }
        
        // non objects are treated as ids and -> No Information
        if (Ext.isObject(container)) {
            var name = Ext.isFunction(container.beginEdit) ? container.get('name') : container.name,
                color = Ext.isFunction(container.beginEdit) ? container.get('color') : container.color;
            
            if (name) {
                result = Tine.Tinebase.common.containerRenderer.tpl.apply({
                    name: Ext.util.Format.htmlEncode(name).replace(/ /g,"&nbsp;"),
                    color: color ? color : '#808080'
                });
            } else if (Ext.isObject(metaData)) {
                metaData.css = 'x-form-empty-field';
            }
        }
        
        return result;
    },
    
    /**
     * Returns prettyfied minutes
     * 
     * @param  {Number} minutes
     * @param  {String} format -> {0} will be replaced by Hours, {1} with minutes
     * @param  {String} leadingZeros add leading zeros for given item {i|H}
     * @return {String}
     */
    minutesRenderer: function (minutes, format, leadingZeros) {
        var s,
            i = minutes % 60,
            H = Math.floor(minutes / 60),
            Hs;
        
        if (leadingZeros && (Ext.isString(leadingZeros) || leadingZeros === true)) {
            if (leadingZeros === true || (leadingZeros.match(/i/) && String(i).length === 1)) {
                i = '0' + String(i);
            }
            if (leadingZeros === true || (leadingZeros.match(/H/) && String(H).length === 1)) {
                H = '0' + String(H);
            }
        }
        
        if (! format || ! Ext.isString(format)) {
            s = String.format(Tine.Tinebase.translation.ngettext('{0} minute', '{0} minutes', i), i);
            Hs = String.format(Tine.Tinebase.translation.ngettext('{0} hour', '{0} hours', H), H);
            //var ds = String.format(Tine.Tinebase.translation.ngettext('{0} workday', '{0} workdays', d), d);
            
            if (i === 0) {
                s = Hs;
            } else {
                s = H ? Hs + ', ' + s : s;
            }
            //s = d ? ds + ', ' + s : s;
            
            return s;
        }
        
        return String.format(format, H, i);
    },

    /**
     * Returns prettyfied seconds
     * 
     * @param  {Number} seconds
     * @return {String}
     */
    secondsRenderer: function (seconds) {
        
        var s = seconds % 60,
            m = Math.floor(seconds / 60),
            result = '';
        
        var secondResult = String.format(Tine.Tinebase.translation.ngettext('{0} second', '{0} seconds', s), s);
        
        if (m) {
            result = Tine.Tinebase.common.minutesRenderer(m);
        }
        
        if (s) {
            if (result !== '') {
                result += ', ';
            }
            result += secondResult;
        }
        
        return result;
    },
    
    /**
     * Returns the formated username
     * 
     * @param {object} account object 
     * @return {string} formated user display name
     */
    usernameRenderer: function (accountObject) {
        var result = (accountObject) ? accountObject.accountDisplayName : '';
        
        return Ext.util.Format.htmlEncode(result);
    },
    
    /**
     * Returns a username or groupname with according icon in front
     */
    accountRenderer: function (accountObject, metadata, record, rowIndex, colIndex, store) {
        if (! accountObject) {
            return '';
        }
        var type, iconCls, displayName;
        
        if (accountObject.accountDisplayName) {
            type = 'user';
            displayName = accountObject.accountDisplayName;
        } else if (accountObject.name) {
            type = 'group';
            displayName = accountObject.name;
        } else if (record.data.name) {
            type = record.data.type;
            displayName = record.data.name;
        } else if (record.data.account_name) {
            type = record.data.account_type;
            displayName = record.data.account_name;
        }
        
        iconCls = type === 'user' ? 'renderer renderer_accountUserIcon' : 'renderer renderer_accountGroupIcon';
        
        return '<div class="' + iconCls  + '">&#160;</div>' + Ext.util.Format.htmlEncode(displayName);
    },
    
    /**
     * Returns account type icon
     * 
     * @return String
     */
    accountTypeRenderer: function (type) {
        var iconCls = (type) === 'user' ? 'renderer_accountUserIcon' : 'renderer_accountGroupIcon';
        
        return '<div style="background-position: 0px" class="' + iconCls  + '">&#160;</div>';
    },
    
    /**
     * Returns dropdown hint icon for editor grid columns with comboboxes
     * 
     * @return String
     */
    cellEditorHintRenderer: function (value) {
        return '<div style="position:relative">' + value + '<div class="tine-grid-cell-hint">&#160;</div></div>';
    },

    /**
     * return yes or no in the selected language for a boolean value
     * 
     * @param {string} value
     * @return {string}
     */
    booleanRenderer: function (value) {
        var translationString = String.format("{0}",(value == 1) ? Locale.getTranslationData('Question', 'yes') : Locale.getTranslationData('Question', 'no'));
        
        return translationString.substr(0, translationString.indexOf(':'));
    },
    
    /**
     * sorts account/user objects
     * 
     * @param {Object|String} user_id
     * @return {String}
     */
    accountSortType: function(user_id) {
        if (user_id && user_id.accountDisplayName) {
            return user_id.accountDisplayName;
        } else if (user_id && user_id.n_fileas) {
            return user_id.n_fileas;
        } else if (user_id && user_id.name) {
            return user_id.name;
        } else {
            return user_id;
        }
    },

    /**
     * sorts records
     * 
     * @param {Object} record
     * @return {String}
     */
    recordSortType: function(record) {
        if (record && Ext.isFunction(record.getTitle)) {
            return record.getTitle();
        } else if (record && record.id) {
            return record.id;
        } else {
            return record;
        }
    },
    
    /**
     * check whether given value can be interpreted as true
     * 
     * @param {String|Integer|Boolean} value
     * @return {Boolean}
     */
    isTrue: function (value) {
        return value === 1 || value === '1' || value === true || value === 'true';
    },
    
    /**
     * check whether object is empty (has no property)
     * 
     * @param {Object} obj
     * @return {Boolean}
     */
    isEmptyObject: function (obj) {
        for (var name in obj) {
            if (obj.hasOwnProperty(name)) {
                return false;
            }
        }
        return true;
    },
    
    /**
     * clone function
     * 
     * @param {Object/Array} o Object or array to clone
     * @return {Object/Array} Deep clone of an object or an array
     */
    clone: function (o) {
        if (! o || 'object' !== typeof o) {
            return o;
        }
        
        if ('function' === typeof o.clone) {
            return o.clone();
        }
        
        var c = '[object Array]' === Object.prototype.toString.call(o) ? [] : {},
            p, v;
            
        for (p in o) {
            if (o.hasOwnProperty(p)) {
                v = o[p];
                if (v && 'object' === typeof v) {
                    c[p] = Tine.Tinebase.common.clone(v);
                }
                else {
                    c[p] = v;
                }
            }
        }
        return c;
    },
    
    /**
     * assert that given object is comparable
     * 
     * @param {mixed} o
     * @return {mixed} o
     */
    assertComparable: function(o) {
        // NOTE: Ext estimates Object/Array by a toString operation
        if (Ext.isObject(o) || Ext.isArray(o)) {
            Tine.Tinebase.common.applyComparableToString(o);
        }
        
        return o;
    },
    
    /**
     * apply Ext.encode as toString functino to given object
     * 
     * @param {mixed} o
     */
    applyComparableToString: function(o) {
        o.toString = function() {return Ext.encode(o)};
    },
    
    /**
     * check if user has right to view/manage this application/resource
     * 
     * @param   {String}      right (view, admin, manage)
     * @param   {String}      application
     * @param   {String}      resource (for example roles, accounts, ...)
     * @returns {Boolean} 
     */
    hasRight: function (right, application, resource) {
        var userRights = [];
        
        if (! (Tine && Tine[application] && Tine[application].registry && Tine[application].registry.get('rights'))) {
            if (! Tine.Tinebase.appMgr) {
                console.error('Tine.Tinebase.appMgr not yet available');
            } else if (Tine.Tinebase.appMgr.get(application)) {
                console.error('Tine.' + application + '.rights is not available, initialisation Error!');
            }
            return false;
        }
        userRights = Tine[application].registry.get('rights');
        
        //console.log(userRights);
        var result = false;
        
        for (var i = 0; i < userRights.length; i += 1) {
            if (userRights[i] === 'admin') {
                result = true;
                break;
            }
            
            if (right === 'view' && (userRights[i] === 'view_' + resource || userRights[i] === 'manage_' + resource)) {
                result = true;
                break;
            }
            
            if (right === 'manage' && userRights[i] === 'manage_' + resource) {
                result = true;
                break;
            }
            
            if (right === userRights[i]) {
                result = true;
                break;
            }
        }
    
        return result;
    },
    
    /**
     * returns random integer number
     * @param {Integer} min
     * @param {Integer} max
     * @return {Integer}
     */
    getRandomNumber: function (min, max) {
        if (min > max) {
            return -1;
        }
        if (min === max) {
            return min;
        }
        return min + parseInt(Math.random() * (max - min + 1), 10);
    },
    /**
     * HTML-encodes a string twice
     * @param {String} value
     * @return {String}
     */
    doubleEncode: function(value) {
        return Ext.util.Format.htmlEncode(Ext.util.Format.htmlEncode(value));
    },
    
    /**
     * resolves an appName to applicationInstance or vice versa
     * returns applicationinstance if getInstance is true
     * @param {String/Tine.Tinebase.Application}    app 
     * @param {Boolean}                             getInstance
     */
    resolveApp: function(app, getInstance) {
        if(getInstance) {
            return Ext.isObject(app) ? app : Tinebase.appMgr.get(app);
        }
        return Ext.isObject(app) ? app.name : app;
    },
    
    /**
     * resolves model to modelName or returns recordClass if an application was given
     * @param {String/Tine.Tinebase.data.Record}    model
     * @param {String/Tine.Tinebase.Application}    app
     */
    resolveModel: function(model, app) {
        var modelName = Ext.isObject(model) ? model.getMeta('modelName') : model;
        if(app) {
            var appName = this.resolveApp(app);
            return Tine[appName].Model[modelName];
        }
        return modelName;
    }
};
