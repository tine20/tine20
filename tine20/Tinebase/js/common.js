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
     *
     * @param {String} part
     */
    getUrl: function(part) {
        var pathname = window.location.pathname.replace('index.php', ''),
            hostname = window.location.host,
            protocol = window.location.protocol,
            url;

        switch (part) {
            case 'path':
                url = pathname;
                break;
            case 'full':
            default:
                url = protocol + '//' + hostname + pathname;
                break;
        }

        return url;

    },

    /**
     * reload client
     *
     * @param {Object} options
     *      {Boolean} keepRegistry
     *      {Boolean} clearCache
     *      {Boolean} redirectAlways
     *      {String} redirectUrl
     */
    reload: function(options) {
        options = options || {};

        if (! options.keepRegistry) {
            Tine.Tinebase.tineInit.isReloading = true;
            Tine.Tinebase.tineInit.clearRegistry();
        }

        if (! options.redirectAlways && options.redirectUrl && options.redirectUrl != '') {
            // redirect only after logout (redirectAlways == false) - we can't wait for the browser here...
            // @todo how can we move that to the server?
            // @see https://github.com/tine20/tine20/issues/6236
            window.setTimeout(function () {
                window.location = options.redirectUrl;
            }, 500);
        } else {
            // give browser some time to clear registry
            window.setTimeout(function () {
                window.location.reload(!!options.clearCache);
            }, 500);
        }
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
    dateTimeRenderer: function ($_iso8601, metadata) {
        if (metadata) {
            metadata.css = 'tine-gird-cell-datetime';
        }

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
    dateRenderer: function (date, metadata) {
        if (metadata) {
            metadata.css = 'tine-gird-cell-date';
        }

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
            format = '0' + Tine.Tinebase.registry.get('thousandSeparator') + '000' + Tine.Tinebase.registry.get('decimalSeparator') + '00';
        }
        return Ext.util.Format.number(v, format);
    },
    
    /**
     * Renders a float or integer as percent
     * 
     * @param {Number} v The number to format.
     * @see Ext.util.Format.number
     * 
     * @return {String} The formatted number.
     */
    percentRenderer: function(v, type) {
        if (! Ext.isNumber(v)) {
            v = 0;
        }
        
        v = Ext.util.Format.number(v, (type == 'float' ? '0.00' : '0'));
        
        if (type == 'float') {
            var decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator');
            if (decimalSeparator == ',') {
                v = v.replace(/\./, ',');
            }
        }
        
        return v + ' %';
    },
    
    /**
     * Returns localised time string
     * 
     * @param {mixed} date
     * @see Ext.util.Format.date
     * @return {String} localised time
     */
    timeRenderer: function (date, metadata) {
        if (metadata) {
            metadata.css = 'tine-gird-cell-time';
        }

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
        if (isNaN(parseInt(value, 10))) {
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
        var decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator'),
            suffix = ['Bytes', 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
            divisor = useDecimalValues ? 1000 : 1024;
            
        if (forceUnit) {
            var i = suffix.indexOf(forceUnit);
            i = (i == -1) ? 0 : i;
        } else {
            for (var i=0,j; i<suffix.length; i++) {
                if (value < Math.pow(divisor, i)) break;
            }
        }
        value = ((i<=1) ? value : Ext.util.Format.round(value/(Math.pow(divisor, Math.max(1, i-1))), decimals)) + ' ' + suffix[i];

        return String(value).replace('.', decimalSeparator);
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
                if (tags[i] && tags[i].name) {
                    var qtipText = Tine.Tinebase.common.doubleEncode(tags[i].name);
                    if (tags[i].description) {
                        qtipText += ' | ' + Tine.Tinebase.common.doubleEncode(tags[i].description);
                    }
                    if (tags[i].occurrence) {
                        qtipText += ' (' + i18n._('Usage:&#160;') + tags[i].occurrence + ')';
                    }
                    result += '<div ext:qtip="' + qtipText + '" class="tb-grid-tags" style="background-color:' + (tags[i].color ? tags[i].color : '#fff') + ';">&#160;</div>';
                }
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
                    '<tpl if="type == \'personal\' ">&nbsp;<i>(' + i18n._('personal') + ')</i></tpl>',
                    '</i>&nbsp;[{occurrence}]',
                    '<tpl if="description != null && description.length &gt; 1"><hr>{[this.encode(values.description)]}</tpl>" >',
                    
                    '&nbsp;{[this.encode(values.name)]}',
                    '<tpl if="type == \'personal\' ">&nbsp;<i>(' + i18n._('personal') + ')</i></tpl>',
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
        
        var result =  i18n._('No Information');
        
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
        
        var result =  i18n._('No Information');
        
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
     * Returns rendered relations
     *
     * @param {mixed} container
     * @return {String}
     *
     * TODO use/invent renderer registry to show more information on relations
     */
    relationsRenderer: function(relations, metaData) {
        // _('No Access') - we need this string in other apps if relation is not shown e.g. record_removed_reason
        var result = '';
        if (relations) {
            for (var i = 0; i < relations.length; i += 1) {
                if (relations[i]) {
                    var qtipText = Tine.Tinebase.common.doubleEncode(relations[i].type);
                    result += '<div ext:qtip="' + qtipText + '" class="tb-grid-tags" style="background-color:white"' + ';">&#160;</div>';
                }
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
            s = String.format(i18n.ngettext('{0} minute', '{0} minutes', i), i);
            Hs = String.format(i18n.ngettext('{0} hour', '{0} hours', H), H);
            //var ds = String.format(i18n.ngettext('{0} workday', '{0} workdays', d), d);
            
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
        
        var secondResult = String.format(i18n.ngettext('{0} second', '{0} seconds', s), s);
        
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
        var _ = window.lodash,
            type, iconCls, displayName, email;
        
        if (accountObject.accountDisplayName) {
            type = _.get(record, 'data.account_type', 'user');
            displayName = accountObject.accountDisplayName;

            // need to create a "dummy" app to call featureEnabled()
            // TODO: should be improved
            var tinebaseApp = new Tine.Tinebase.Application({
                appName: 'Tinebase'
            });
            if (tinebaseApp.featureEnabled('featureShowAccountEmail') && accountObject.accountEmailAddress) {
                // add email address if available
                email = accountObject.accountEmailAddress;
                displayName += ' (' + email + ')';
            }
        } else if (accountObject.name && ! _.get(record, 'data.account_type')) {
            type = 'group';
            displayName = accountObject.name;
        } else if (record && record.data.name) {
            type = record.data.type;
            displayName = record.data.name;

        // so odd, - new records, picked via pickerGridPanel
        } else if (record && record.data.account_name) {
            type = record.data.account_type;
            displayName = _.get(record, 'data.account_name.name', record.data.account_name);
        }
        
        if (displayName == 'Anyone') {
            displayName = i18n._(displayName);
            type = 'group';
        }
        
        iconCls = 'tine-grid-row-action-icon renderer renderer_account' + Ext.util.Format.capitalize(type) + 'Icon';
        return '<div class="' + iconCls  + '">&#160;</div>' + Ext.util.Format.htmlEncode(displayName || '');
    },
    
    /**
     * Returns account type icon
     * 
     * @return String
     */
    accountTypeRenderer: function (type) {
        var iconCls = 'tine-grid-row-action-icon ' + (type === 'user' ? 'renderer_accountUserIcon' : 'renderer_accountGroupIcon');
        
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
     * i18n renderer
     *
     * NOTE: needs to be bound to i18n object!
     *
     * renderer: Tine.Tinebase.common.i18nRenderer.createDelegate(this.app.i18n)
     * @param original
     */
    i18nRenderer: function(original) {
        return this._hidden(original);
    },

    /**
     * color renderer
     *
     * @param color
     */
    colorRenderer: function(color) {
        // normalize
        color = String(color).replace('#', '');

        return '<div style="background-color: #' + Ext.util.Format.htmlEncode(color) + '">&#160;</div>';
    },

    /**
     * foreign record renderer
     *
     * @param record
     * @param metadata
     *
     * TODO use title fn? allow to configure displayed field(s)?
     */
    foreignRecordRenderer: function(record, metaData) {
        return record && record.name ? record.name : '';
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

        if (! (Tine && Tine[application] && Tine[application].registry && Tine[application].registry.get('rights'))) {
            if (Tine.Tinebase.tineInit.isReloading) {
                Tine.log.info('Tine 2.0 is reloading ...');
            } else if (! Tine.Tinebase.appMgr) {
                console.error('Tine.Tinebase.appMgr not yet available');
            } else if (Tine.Tinebase.appMgr.get(application)) {
                console.error('Tine.' + application + '.rights is not available, initialisation Error! Reloading app.');
                // reload registry/mainscreen - registry problem?
                Tine.Tinebase.common.reload({});
            }
            return false;
        }
        var userRights = Tine[application].registry.get('rights'),
            allAppRights = Tine[application].registry.get('allrights');

        if (allAppRights && right === 'view' && allAppRights.indexOf('view') < 0) {
            // switch to run as app has no view right
            right = 'run';
        }

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
     * simple html to text conversion
     *
     * @param html
     * @returns {String}
     */
    html2text: function(html) {
        text = html.replace(/\n/g, ' ')
            .replace(/(<br[^>]*>)/g, '\n--br')
            .replace(/(<li[^>]*>)/g, '\n * ')
            .replace(/<(blockquote|div|dl|dt|dd|form|h1|h2|h3|h4|h5|h6|hr|p|pre|table|tr|td|li|section|header|footer)[^>]*>(?!\s*\<\/\1\>)/g, '\n--block')
            .replace(/<style(.+?)\/style>/g, '')
            .replace(/<(.+?)>/g, '')
            .replace(/&nbsp;/g, ' ')
            .replace(/--block(\n--block)+/g, '--block')
            .replace(/--block\n--br/g, '')
            .replace(/(--block|--br)/g, '');

        return Ext.util.Format.htmlDecode(text);
    },

    /**
     * linkify text
     *
     * @param {String} text
     * @param {Ext.Element|Function} cb
     */
    linkifyText: function(text, cb, scope) {
        require.ensure(["linkifyjs", "linkifyjs/html"], function() {
            var linkify = require('linkifyjs');
            var linkifyHtml = require('linkifyjs/html');
            var linkifyed = linkifyHtml(text);

            if (Ext.isFunction(cb)) {
                cb.call(scope || window, linkifyed);
            } else {
                cb.update(linkifyed);
            }
        }, 'Tinebase/js/linkify');
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
    },

    /**
     * Confirm application restart
     *
     * @param Boolean closewindow
     */
    confirmApplicationRestart: function (closewindow) {
        Ext.Msg.confirm(i18n._('Confirm'), i18n._('Restart application to apply new configuration?'), function (btn) {
            if (btn == 'yes') {
                // reload mainscreen to make sure registry gets updated
                Tine.Tinebase.common.reload();
                if (closewindow) {
                    window.close();
                }
            }
        }, this);
    },

    /**
     * Math.trunc polyfill
     *
     * https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Math/trunc
     *
     * @param x
     * @return {*}
     */
    trunc: function (x) {
        if (isNaN(x)) {
            return NaN;
        }
        if (x > 0) {
            return Math.floor(x);
        }
        return Math.ceil(x);
    },

    /**
     * check valid email domain (if email domain is set in config)
     *
     * @param {String} email
     * @return {Boolean}
     */
    checkEmailDomain: function(email) {
        Tine.log.debug('Tine.Tinebase.common.checkEmailDomain - email: ' + email);

        if (! Tine.Tinebase.registry.get('primarydomain') || ! email) {
            Tine.log.debug('Tine.Tinebase.common.checkEmailDomain - no primarydomain config found or no mail given');
            return true;
        }

        var allowedDomains = [Tine.Tinebase.registry.get('primarydomain')],
            emailDomain = email.split('@')[1];

        if (Ext.isString(Tine.Tinebase.registry.get('secondarydomains'))) {
            allowedDomains = allowedDomains.concat(Tine.Tinebase.registry.get('secondarydomains').split(','));
        }

        if (Ext.isString(Tine.Tinebase.registry.get('additionaldomains'))) {
            allowedDomains = allowedDomains.concat(Tine.Tinebase.registry.get('additionaldomains').split(','));
        }

        Tine.log.debug('Tine.Tinebase.common.checkEmailDomain - allowedDomains:');
        Tine.log.debug(allowedDomains);

        return (allowedDomains.indexOf(emailDomain) !== -1);
    },

    getMimeIconCls: function(mimeType) {
        return 'mime-content-type-' + mimeType.replace(/\/.*$/, '') +
            ' mime-suffix-' + (mimeType.match(/\+/) ? mimeType.replace(/^.*\+/, '') : 'none') +
            ' mime-type-' + mimeType
                .replace(/\//g, '-slash-')
                .replace(/\./g, '-dot-')
                .replace(/\+/g, '-plus-');
    }
};

/*
var s = '<blockquote class="felamimail-body-blockquote"><div>Hello,</div><div><br></div><div>...</div></blockquote>';
if (Tine.Tinebase.common.html2text(s) != "\nHello,\n\n...") console.error('ignore empty div: "' + Tine.Tinebase.common.html2text(s) + '"');

var s = '<font face="tahoma, arial, helvetica, sans-serif" style="font-size: 11px; font-family: tahoma, arial, helvetica, sans-serif;"><span style="font-size: 11px;">​<font color="#808080">Dipl.-Phys. Cornelius Weiss</font></span></font><div style="font-size: 11px; font-family: tahoma, arial, helvetica, sans-serif;"><font face="tahoma, arial, helvetica, sans-serif" color="#808080"><span style="font-size: 11px;">Team Leader Software Engineering</span></font></div>';
if (Tine.Tinebase.common.html2text(s) != "​Dipl.-Phys. Cornelius Weiss\nTeam Leader Software Engineering") console.error('cope with styled div tag: '  + Tine.Tinebase.common.html2text(s));

var s = '<div><div><span><font><br></font></span></div></div>';
if (Tine.Tinebase.common.html2text(s) != "\n") console.error('cope with nested blocks: "' + Tine.Tinebase.common.html2text(s) + '"');
*/
