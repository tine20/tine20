/*
 * Tine 2.0
 * 
 * @license     New BSD License
 * @author      loeppky - based on the work done by MaximGB in Ext.ux.UploadDialog (http://extjs.com/forum/showthread.php?t=21558)
 *
 */
Ext.ns('Ext.ux.file');

/**
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.BrowsePlugin
 * @param       {Object} config Configuration options
 */
Ext.ux.file.BrowsePlugin = function(config) {
    Ext.apply(this, config);
};

Ext.ux.file.BrowsePlugin.prototype = {
    /**
     * @cfg {Boolean} multiple
     * allow multiple files to be selected (HTML 5 only)
     */
    multiple: false,
    /**
     * @cfg {Ext.Element} dropEl 
     * element used as drop target if enableFileDrop is enabled
     */    
    dropEl: null,
    /**
     * @cfg {Boolean} enableFileDrop
     * @see http://www.w3.org/TR/2008/WD-html5-20080610/editing.html
     * 
     * enable drops from OS (defaults to true)
     */
    enableFileDrop: true,
    /**
     * @cfg {Boolean} enableFileDialog
     * @see http://www.w3.org/TR/2008/WD-html5-20080610/editing.html
     * 
     * enable file dialog on click(defaults to true)
     */
    enableFileDialog: true,
    /**
     * @cfg {String} inputFileName
     * Name to use for the hidden input file DOM element.  Deaults to "file".
     */
    inputFileName: 'file',
    /**
     * @cfg {Function} onBrowseButtonClick
     */
    onBrowseButtonClick: Ext.emptyFn,
    /**
     * @property inputFileEl
     * @type Ext.Element
     * Element for the hiden file input.
     * @private
     */
    input_file: null,
    /**
     * @cfg handler
     * @type Function
     * The handler originally defined for the Ext.Button during construction using the "handler" config option.
     * We need to null out the "handler" property so that it is only called when a file is selected.
     * @private
     */
    handler: null,
    /**
     * @cfg scope
     * @type Object
     * The scope originally defined for the Ext.Button during construction using the "scope" config option.
     * While the "scope" property doesn't need to be nulled, to be consistent with handler, we do.
     * @private
     */
    scope: null,
    
    currentTreeNodeUI: null,
    
    /*
     * Protected Ext.Button overrides
     */
    /**
     * @see Ext.Button.initComponent
     */
    init: function(cmp){
        if(cmp.handler) this.handler = cmp.handler;
        this.scope = cmp.scope || window;
        cmp.handler = null;
        cmp.scope = null;
        
        this.component = cmp;
        
        cmp.on('afterrender', this.onRender, this);
        cmp.on('show', this.syncWrap, this);
        cmp.on('resize', this.syncWrap, this);
        cmp.on('afterlayout', this.syncWrap, this);
        
        // chain fns
        if (typeof cmp.setDisabled == 'function') {
            cmp.setDisabled = cmp.setDisabled.createSequence(function(disabled) {
                if (this.input_file) {
                    this.input_file.dom.disabled = disabled;
                }
            }, this);
        }
        
        if (typeof cmp.enable == 'function') {
            cmp.enable = cmp.enable.createSequence(function() {
                if (this.input_file) {
                    this.input_file.dom.disabled = false;
                }
            }, this);
        }
        
        if (typeof cmp.disable == 'function') {
            cmp.disable = cmp.disable.createSequence(function() {
                if (this.input_file) {
                    this.input_file.dom.disabled = true;
                }
            }, this);
        }
        
        if (typeof cmp.destroy == 'function') {
            cmp.destroy = cmp.destroy.createSequence(function() {
                var input_file = this.detachInputFile(true);
                if (input_file) {
                    input_file.remove();
                }
                input_file = null;
            }, this);
        }
        
    },
    
    /**
     * @see Ext.Button.onRender
     */
    onRender: function() {
       
    	this.button_container = this.buttonCt || this.component.el.child('tbody') || this.component.el;
        this.button_container.position('relative');
        this.wrap = this.component.el.wrap({cls:'tbody'});

        // NOTE: wrap a button is complex, its doLayout moves the wrap
        if (this.component.ownerCt && this.component.btnEl/* && this.component.ownerCt.el.hasClass('x-toolbar')*/) {
            this.component.ownerCt.on('afterlayout', function() {
                if (this.wrap.first() !== this.component.el) {
                    this.wrap.insertBefore(this.component.el);
                    this.wrap.insertFirst(this.component.el);
                }
                this.syncWrap();
            }, this);
            
            this.component.ownerCt.on('show', this.syncWrap, this);
            this.component.ownerCt.on('resize', this.syncWrap, this);
        }
        
        if (this.enableFileDialog) this.createInputFile();
      
        if (this.enableFileDrop && !Ext.isIE) {
            if (! this.dropEl) {
                if (this.dropElSelector) {
                    this.dropEl = this.wrap.up(this.dropElSelector);
                } else {
                    this.dropEl = this.button_container;
                }
            }
            
            this.dropEl.on('dragleave', function(e) {
            	e.stopPropagation();
            	e.preventDefault();

            	this.createMouseEvent(e, 'mouseout');
            }, this);

            // @see http://dev.w3.org/html5/spec/Overview.html#the-dragevent-and-datatransfer-interfaces
            this.dropEl.on('dragover', function(e) {

                e.stopPropagation();
                e.preventDefault();
                                         
                this.createMouseEvent(e, 'mouseover');
                
            }, this);
            
            this.dropEl.on('drop', function(e, target) {
               
                e.stopPropagation();
                e.preventDefault();
                
                // a bit hackish, I know...
                this.onBrowseButtonClick();
                
                var dt = e.browserEvent.dataTransfer;
                var files = dt.files;
                
                this.onInputFileChange(e, null, null, files);
            }, this);
        }
    },
    
    syncWrap: function() {
        if (this.button_container) {
            var button_size = this.button_container.getSize();
            this.wrap.setSize(button_size);
        }
    },
    
    createInputFile: function() {
        this.input_file = this.wrap.createChild(Ext.apply({
            tag: 'input',
            type: 'file',
            size: 1,
            name: this.inputFileName || Ext.id(this.component.el),
            style: "position: absolute; display: block; border: none; cursor: pointer;"
        }, this.multiple ? {multiple: true} : {}));

        this.input_file.dom.disabled = this.component.disabled;
        
        var button_box = this.button_container.getBox();
        
        this.wrap.setBox(button_box);

        this.wrap.applyStyles('overflow: hidden; position: relative;');
        
        // ALL but IE
        this.wrap.on('mousemove', function(e) {
            var xy = e.getXY();
            this.input_file.setXY([xy[0] - this.input_file.getWidth()/2, xy[1] - 5]);
            
            // if split button
            if(this.component.btnEl) {
                var buttonEl = Ext.get(this.wrap.dom);
                var buttonX = buttonEl.getX(),
                    buttonWidth = buttonEl.getWidth(),
                    mouseX = xy[0];
                
                if(mouseX - buttonX > this.component.btnEl.dom.clientWidth) {
                    this.input_file.dom.style.zIndex = -1;
                }
                else {
                    this.input_file.dom.style.zIndex = '';
                }
            }
            
        }, this, {buffer: 20});
        
        
        // IE
        this.button_container.on('mousemove', function(e) {
            var xy = e.getXY();
            this.input_file.setXY([xy[0] - this.input_file.getWidth()/2, xy[1] - 5]);
            
            // if split button
            if(this.component.btnEl) {
                var buttonEl = Ext.get(this.button_container.dom);
                var buttonX = buttonEl.getX(),
                    buttonWidth = buttonEl.getWidth(),
                    mouseX = xy[0];
                
                if(mouseX - buttonX > this.component.btnEl.dom.clientWidth) {
                    this.input_file.dom.style.zIndex = -1;
                }
                else {
                    this.input_file.dom.style.zIndex = '';
                }
            }
        }, this, {buffer: 30});
        
        this.input_file.setOpacity(0.0);

        Ext.fly(this.input_file).on('click', function(e) {
            this.onBrowseButtonClick();
        }, this);
        
        // FIX mouseover / out
        if (! this.supressOverFix && Ext.isFunction(this.component.onMouseOut)) {
            this.component.onMouseOut = this.component.onMouseOut.createInterceptor(function(e) {
                if (this.isMouseOver) {
                    return false;
                }
                this.isMouseOver = false;
            }, this);
            
            this.wrap.on('mouseover', function(e) {
                this.isMouseOver = true;
                if (this.component.el.hasClass('x-btn') && !this.component.disabled) {
                    this.component.el.addClass('x-btn-over');
                }
            }, this);
    
            this.wrap.on('mouseout', function(e) {
                this.isMouseOver = false;
                if (this.component.el.hasClass('x-btn') && !this.component.disabled) {
                    this.component.el.removeClass('x-btn-over');
                }
            }, this);
        }
        
        if (this.component.handleMouseEvents) {
            this.wrap.on('mouseover', this.component.onMouseOver || Ext.emptyFn, this.component);
            this.wrap.on('mousedown', this.component.onMouseDown || Ext.emptyFn, this.component);
            this.wrap.on('contextmenu', this.component.onContextMenu || Ext.emptyFn, this.component);
        }
        
        if(this.component.tooltip){
            if(typeof this.component.tooltip == 'object'){
                Ext.QuickTips.register(Ext.apply({target: this.input_file}, this.component.tooltip));
            } 
            else {
                this.input_file.dom[this.component.tooltipType] = this.component.tooltip;
            }
        }
        
        this.input_file.on('change', this.onInputFileChange, this);
        this.input_file.on('click', function(e) { e.stopPropagation(); });
    },
    
    /**
     * Handler when inputFileEl changes value (i.e. a new file is selected).
     * @param {FileList} files when input comes from drop...
     * @private
     */
    onInputFileChange: function(e, target, options, files){
        if (window.FileList) { // HTML5 FileList support
            this.files = files ? files : this.input_file.dom.files;
        } else {
            this.files = [{
                name : this.input_file.getValue().split(/[\/\\]/).pop()
            }];
            this.files[0].type = this.getFileCls();
        }
        
        if (this.handler) {
            this.handler.call(this.scope, this, e);
        }
    },
    
    /**
     * Detaches the input file associated with this BrowseButton so that it can be used for other purposed (e.g. uplaoding).
     * The returned input file has all listeners and tooltips applied to it by this class removed.
     * @param {Boolean} whether to create a new input file element for this BrowseButton after detaching.
     * True will prevent creation.  Defaults to false.
     * @return {Ext.Element} the detached input file element.
     */
    detachInputFile : function(no_create) {
        var result = this.input_file;
        
        no_create = no_create || false;
        
        if (this.input_file) {
            if (typeof this.component.tooltip == 'object') {
                Ext.QuickTips.unregister(this.input_file);
            }
            else {
                this.input_file.dom[this.component.tooltipType] = null;
            }
            this.input_file.removeAllListeners();
        }
        this.input_file = null;
        
        if (!no_create) {
            this.createInputFile();
        }
        return result;
    },
    
    getFileList: function() {
        return this.files;
    },
    
    /**
     * @return {Ext.Element} the input file element
     */
    getInputFile: function(){
        return this.input_file;
    },
    /**
     * get file name
     * @return {String}
     */
    getFileName:function() {
        var file = this.getFileList()[0];
        return file.name ? file.name : file.fileName;
    },
    
    /**
     * returns file class based on name extension
     * @return {String} class to use for file type icon
     */
    getFileCls: function() {

        var fparts = this.getFileName().split('.');
        if(fparts.length === 1) {
            return '';
        }
        else {
            return fparts.pop().toLowerCase();
        }
    },
    
    isImage: function() {
        var cls = this.getFileCls();
        return (cls == 'jpg' || cls == 'gif' || cls == 'png' || cls == 'jpeg');
    },
    
    createMouseEvent: function(e, mouseEventType) {
    	
    	if(document.createEvent)  {
            var evObj = document.createEvent('MouseEvents');
            evObj.initMouseEvent(mouseEventType, true, true, window, e.browserEvent.detail, e.browserEvent.screenX, e.browserEvent.screenY
                    , e.browserEvent.clientX, e.browserEvent.clientY, e.browserEvent.ctrlKey, e.browserEvent.altKey
                    , e.browserEvent.shiftKey, e.browserEvent.metaKey, e.browserEvent.button, e.browserEvent.relatedTarget);
            e.target.dispatchEvent(evObj);
        }    
        // TODO: IE problem on drag over tree nodes (not the whole row tracks onmouseout)
        else if(document.createEventObject) {
            var evObj = document.createEventObject();
            evObj.detail = e.browserEvent.detail;
            evObj.screenX = e.browserEvent.screenX;
            evObj.screenY = e.browserEvent.screenY;
            evObj.clientX = e.browserEvent.clientX;
            evObj.clientY = e.browserEvent.clientY;
            evObj.ctrlKey = e.browserEvent.ctrlKey;
            evObj.altKey = e.browserEvent.altKey;
            evObj.shiftKey = e.browserEvent.shiftKey;
            evObj.metaKey = e.browserEvent.metaKey;
            evObj.button = e.browserEvent.button;
            evObj.relatedTarget = e.browserEvent.relatedTarget;
            e.getTarget('div').fireEvent('on' + mouseEventType ,evObj);
        }
    	
    }
};

Ext.preg('ux.browseplugin', Ext.ux.file.BrowsePlugin);
