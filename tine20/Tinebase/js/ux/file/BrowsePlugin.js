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

/**
 * @TODO: 
 *         
 *         proxy mouse events
 */
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
        this.component = cmp;
        if(cmp.handler) this.handler = cmp.handler;
        this.scope = cmp.scope || window;
        cmp.handler = null;
        cmp.scope = null;
        
        cmp.on('afterrender', this.onRender, this);
        cmp.on('destroy', this.onDestroy, this);
    },
    
    /**
    * hide floatingLayer when the mouse leaves the layer
    */
    hideLayerIf: function(e) {
        if (! e.within(this.floatingLayer, false, true)) {
            this.hideLayer();
        }
    },
    
    hideLayer: function() {
        Ext.getDoc().un('mousemove', this.hideLayerIf, this);
        this.floatingLayer.hide();
        this.layerVisisble = false;
    },
    
    /**
    * show floatingLayer and start to check whether the mouse is in it
    */
    showLayer: function() {
        if (! this.layerVisisble) {
            this.syncFloatingLayer();
            this.floatingLayer.show();
            
            this.component.mon(Ext.getDoc(), {
                scope: this,
                mousemove: this.hideLayerIf,
                buffer: 250
            });
            
            this.layerVisisble = true;
        }
    },
    
    /**
    * drag and drop
    */
    imageDragleave: function(e) {
        e.stopPropagation();
        e.preventDefault();
        this.component.el.applyStyles(' background-color: transparent');
        this.hideLayer();
    },
    
    imageDragover: function(e) {
        e.stopPropagation();
        e.preventDefault();
        this.component.el.applyStyles(' background-color: #ebf0f5');
    },
    
    imageDrop: function(e) {
        e.stopPropagation();
        e.preventDefault();
        this.component.el.applyStyles(' background-color: transparent');
        this.onBrowseButtonClick();
        var dt = e.browserEvent.dataTransfer;
        var files = dt.files;
        this.onInputFileChange(e, null, null, files);
    },
    
    /**
    * returns z-index
    * If the component has no z-index: 100
    * If the component has a z-index: this z-index + 100
    */
    getNextZindex: function() {
        var zElement = this.component.getEl().up('div[style*=z-index]');
        var zIndex = zElement === null ? 0 : parseInt(zElement.getStyle('z-index'));
        
        return zIndex + 100;
    },
    
    /**
     * @TODO proxy mouse events from layer to cmp
     */
    onRender: function() {
        if (this.enableFileDialog) {
            this.floatingLayer = new Ext.Layer({constrain: false, shadow: false, shim: false, zindex: this.getNextZindex()});
            this.component.on('enable', this.onEnable, this);
            this.component.on('disable', this.onDisable, this);
            
            this.component.mon(this.component.getEl(), {
                scope: this,
                'mouseover': this.showLayer
            });
            this.floatingLayer.applyStyles('overflow: hidden; position: relative; background-image:url('+Ext.BLANK_IMAGE_URL+'); background-repeat:repeat;');
            this.floatingLayer.on('mousemove', function(e) {
                var xy = e.getXY();
                this.input_file.setXY([xy[0] - this.input_file.getWidth()/2, xy[1] - 5]);
            }, this, {buffer: 20});
            
            if (this.component.handleMouseEvents) {
                this.floatingLayer.on('mouseover', this.component.onMouseOver || Ext.emptyFn, this.component);
                this.floatingLayer.on('mousedown', this.component.onMouseDown || Ext.emptyFn, this.component);
                this.floatingLayer.on('contextmenu', this.component.onContextMenu || Ext.emptyFn, this.component);
            }
            
            this.createInputFile();
        }
        if (this.enableFileDrop && !Ext.isIE) {
            this.dropEl = this.component.el;
            this.dropEl.on('dragleave', this.imageDragleave, this);
            this.dropEl.on('dragover', this.imageDragover, this);
            this.dropEl.on('drop', this.imageDrop, this);
        }
    },
    
    /**
    * clean up
    */
    onDestroy: function() {
        this.floatingLayer.remove();
        this.floatingLayer = null;
        this.input_file = null;
        this.dropEl.un('dragleave', this.imageDragleave, this);
        this.dropEl.un('dragover', this.imageDragover, this);
        this.dropEl.un('drop', this.imageDrop, this);
        this.dropEl = null;
    },
    
    onEnable: function() {
        this.setDisabled(false);
    },
    
    onDisable: function() {
        this.setDisabled(true);
    },
    
    setDisabled: function(disabled) {
        this.input_file.dom.disabled = disabled;
    },
    
    syncFloatingLayer: function() {
        this.floatingLayer.setBox(this.component.el.getBox());
    },
    
    createInputFile: function() {
        this.input_file = this.floatingLayer.createChild(Ext.apply({
            tag: 'input',
            type: 'file',
            size: 1,
            name: this.inputFileName || Ext.id(this.component.el),
            style: "position: absolute; display: block; border: none; cursor: pointer;"
        }, this.multiple ? {multiple: true} : {}));
        this.input_file.dom.disabled = this.component.disabled;
        
        this.input_file.setOpacity(0.0);
        
        Ext.fly(this.input_file).on('click', function(e) {
            this.onBrowseButtonClick();
        }, this);
        
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
        
        if (this.enableFileDialog && !no_create) {
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
