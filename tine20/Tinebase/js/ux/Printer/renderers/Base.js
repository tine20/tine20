/**
 * @class Ext.ux.Printer.BaseRenderer
 * @extends Object
 * @author Ed Spencer
 * @author Cornelius Weiß
 * Abstract base renderer class. Don't use this directly, use a subclass instead
 */
Ext.ux.Printer.BaseRenderer = Ext.extend(Object, {
    /**
    * @cfg {String} printStrategy window or iframe
    */
    printStrategy: 'iframe',

    /**
     * @property stylesheetPath
     * @type String
     * The path at which the print stylesheet can be found (defaults to 'stylesheets/print.css')
     */
    stylesheetPath: 'stylesheets/print.css',

    /**
    * @cfg {Boolean} useHtml2Canvas
    */
    useHtml2Canvas: false,

    debug: false,
  
    constructor: function(config) {
        Ext.apply(this, config);

        Ext.ux.Printer.BaseRenderer.superclass.constructor.call(this, config);
    },

    /**
    * template method to intercept when document is ready
    *
    * @param {Document} document
    * @pram {Ext.Component} component
    */
    onBeforePrint: Ext.emptyFn,

    /**
    * Prints the component
    * @param {Ext.Component} component The component to print
    */
    print: function(component) {
        var mask = new Ext.LoadMask(Ext.getBody(), {msg: i18n._("Preparing print, please wait...")});
        mask.show();

        if (this.debug) {
            this.printStrategy = 'window';
        }

        return this[this.printStrategy + 'Print'](component)
        .then(function() {
            mask.hide.defer(200, mask);
        })
        .catch(function(error) {
            mask.hide();
            Ext.Msg.show({
                title: i18n._('Printing Error'),
                msg: error,
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.ERROR
            });
        });
    },
  
    /**
    * Prints the component using the new window strategy
    * @param {Ext.Component} component The component to print
    */
    windowPrint: function(component) {
        var name = component && component.getXType
             ? String.format("print_{0}_{1}", String(component.getXType()).replace(/(\.|-)/g, '_'), component.id.replace(/(\.|-)/g, '_'))
             : "print";

        var win = window.open('', name);

        var me = this;
        return me.generateHTML(component).then(function(html) {
            win.document.write(html);
            win.document.close();

            // gecko looses its document after document.close(). but fortunally waits with printing till css is loaded itself
            me.doPrint(win);
        });
    },

    /**
     * Prints the component using the hidden iframe strategy
     * @param {Ext.Component} component The component to print
     */
    iframePrint: function(component) {
        var me = this;
        return me.generateHTML(component).then(function(html) {
            var id = Ext.id(),
                frame = document.createElement('iframe'),
                style = {
                    position: 'absolute',
                    'background-color': '#FFFFFF',
                    width: '210mm',
                    height: '297mm',
                    top: '-10000px',
                    left: '-10000px'
                };

            if (this.debug) {
                Ext.apply(style, {
                    top: '0px',
                    left: '0px',
                    'z-index': 10000000
                });
            }

            Ext.fly(frame).set({
                id: id,
                name: id,
                style: style
            });

            document.body.appendChild(frame);

            Ext.fly(frame).set({
                src : Ext.SSL_SECURE_URL
            });

            var doc = frame.contentWindow.document || frame.contentDocument || WINDOW.frames[id].document;

            doc.open();
            doc.write(html);
            doc.close();

            // resize to full height as browser might print only the visible area
            var totalHeight = Ext.fly(doc.body).getHeight();
            Ext.fly(frame).setStyle('height', totalHeight+'px');

            return me.doPrintOnStylesheetLoad(frame.contentWindow, component);
        });
    },

    /**
     * check if style is loaded and do print afterwards
     *
     * @param {window} win
     */
    doPrintOnStylesheetLoad: function(win, component) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            var checkcss = function(win, component) {
                var el = win.document.getElementById('csscheck'),
                    comp = el.currentStyle || getComputedStyle(el, null);

                if (comp.display !== "none") {
                    return checkcss.defer(10, me, [win, component]);
                }
                // give some extra time for logo
                _.delay(fulfill, 500);
            };
            checkcss(win, component);
        })
            .then(function() {
                me.onBeforePrint(win.document, component);
            })
            .then(function() {
                return me.doPrint(win);
            });
    },

    doPrint: function(win) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            if (me.useHtml2Canvas) {
                require.ensure(["html2canvas"], function() {
                    var html2canvas = require ("html2canvas");

                    html2canvas(win.document.body, {
                        grabMouse: false,
                        scale: 300/96 // 300 dpi
                    }).then(function (canvas) {
                        var screenshot = canvas.toDataURL();
                        me.useHtml2Canvas = false;
                        win.document.body.innerHTML = '<img style="display: block; width: 100%" />';
                        win.document.body.firstChild.onload = me.doPrint.createDelegate(me, [win]);
                        win.document.body.firstChild.src = screenshot;
                        fulfill();
                    });
                }, 'Tinebase/js/html2canvas');

            } else {
                try {
                    if (!win.document.execCommand('print', false, null)) {
                        win.print();
                    }
                } catch(e) {
                    win.print();
                }
                if (!me.debug) {
                    win.close();
                }
                fulfill();
            }
        });
    },

    /**
    * Generates the HTML Markup which wraps whatever this.generateBody produces
    * @param {Ext.Component} component The component to generate HTML for
    * @return {String} An HTML fragment to be placed inside the print window
    */
    generateHTML: function(component) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            me.prepareData(component).then(function(data) {
                me.generateBody(component, data).then(function(bodyHtml) {
                    fulfill(new Ext.XTemplate(
                        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
                        '<html>',
                        '<head> ',
                        '<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />',
                        '<x-additional-headers />',
                        '<link href="' + me.stylesheetPath + '?' + new Date().getTime() + '" rel="stylesheet" type="text/css" media="screen,print" />',
                        '<title>' + me.getTitle(component) + '</title>',
                        '</head>',
                        '<body>',
                        '<div id="csscheck"></div>',
                        bodyHtml,
                        '</body>',
                        '</html>'
                    // NOTE: we need to append additional headers outside xtemplate as the template.compile has problems e.g. with complex css structures
                    ).apply(data).replace('<x-additional-headers />', me.getAdditionalHeaders()));
                });
            });
        });
    },
  
    /**
    * Returns the HTML that will be placed into the <head> element of th print window.
    * @param {Ext.Component} component The component to render
    * @return {String} The HTML fragment to place inside the print window's <head> element
    */
    getAdditionalHeaders: function(component) {
        return '';
    },
    
    /**
    * Returns the HTML that will be placed into the print window. This should produce HTML to go inside the
    * <body> element only, as <head> is generated in the print function
    * @param {Ext.Component} component The component to render
    * @param data The data extracted from the component
    * @return {String} The HTML fragment to place inside the print window's <body> element
    */
    generateBody: function(component, data) {
        return new Promise(function (fulfill, reject) {
            fulfill('')
        });
    },

    /**
    * Prepares data suitable for use in an XTemplate from the component
    * @param {Ext.Component} component The component to acquire data from
    * @return {Array} An empty array (override this to prepare your own data)
    */
    prepareData: function(component) {
        return new Promise(function (fulfill, reject) {
            fulfill(component);
        });
    },

    /**
    * Returns the title to give to the print window
    * @param {Ext.Component} component The component to be printed
    * @return {String} The window title
    */
    getTitle: function(component) {
        return typeof component.getTitle == 'function' ? component.getTitle() : (component.title || "Printing");
    }
});
