/*
 * @author Pontifex
 * @version 1.0
 *
 * @class Ext.ux.Wizard
 */
Ext.ns('Ext.ux');

/**
 * Ext.ux.Wizard Extension Class
 * 
 * @namespace   Ext.ux
 * @class       Ext.ux.Wizard
 * @extends     Ext.Panel
 * @constructor
 * @param {Object} config Configuration options
 */
Ext.ux.Wizard = function(config) {
 
    var _config = Ext.apply({
        
        layout: 'card',
        activeItem: 0,
        bodyStyle: 'paddingTop:15px',
        defaults: {
                // applied to each contained panel
                border: false
            },
        
        buttons  : [
            {text:'Previous', handler: this.movePrevious, scope: this, disabled:true},
            {text:'Next', handler: this.moveNext, scope: this},
            {text:'Finish', handler: this.finishHanlder, scope: this, disabled:true},
            {text:'Cancel', handler: this.hideHanlder, scope: this}
        ]
    }, config||{});
    
    this.currentItem = 0;
    this.template = new Ext.Template('Step {current} of {count}');
    this.mandatorySteps = _config.mandatorySteps;

    Ext.ux.Wizard.superclass.constructor.call(this, _config);
    
    this.addEvents('leave', 'activate', 'finish', 'cancel');
    this.on('render', function(){
        this.footer.addClass('x-panel-footer-wizard');
        this.footer.insertFirst({html: '<div class="x-panel-footer-wizard-status">&nbsp;</div>'});
        this.setStatus();
        return true;
    });
 
}; // end of Ext.ux.Wizard constructor
 
// extend
Ext.extend(Ext.ux.Wizard, Ext.Panel, {
    
    getCurrentStep: function() {
        return this.currentItem + 1;
    },
    getStepCount: function() {
        return this.items.items.length;
    },
    setCurrentStep: function(step) {
        this.move(step-1);
    },
     
    /**
     * @private
     */
    beforeMove: function(currentItem, nextItem, forward) {
        return this.fireEvent('leave', currentItem, nextItem, forward);
    },
    
    /**
     * @private
     */
    setStatus: function() {
        var BUTTON_PREVIOUS = 0;
        var BUTTON_NEXT = 1;
        var BUTTON_FINISH = 2;
        var BUTTON_CANCEL = 3;

        var isFirstItem = (this.getCurrentStep() == 1);
        var isLastItem = (this.getCurrentStep() == this.getStepCount());
        var minimunSteps = isNaN(parseInt(this.mandatorySteps)) ?
                           this.getStepCount() :
                           Math.min(Math.max(parseInt(this.mandatorySteps), 1), this.getStepCount());

        this.buttons[BUTTON_PREVIOUS].setDisabled(isFirstItem);
        this.buttons[BUTTON_NEXT].setDisabled(isLastItem);
        this.buttons[BUTTON_FINISH].setDisabled(!(isLastItem || (minimunSteps < this.getCurrentStep())));
                
        this.footer.first('div div', true).firstChild.innerHTML = this.template.applyTemplate({'current': this.getCurrentStep(), 'count': this.getStepCount()});
    },
    /**
     * @private
     */
    move: function(item) {
        if(item >= 0 && item < this.items.items.length) {
            
            if(this.beforeMove(this.layout.activeItem, this.items.items[item], item > this.currentItem)) {
                this.layout.setActiveItem(item);
                this.currentItem = item;
    
                this.setStatus();                
                this.fireEvent('activate', this.layout.activeItem);
            }
        }
    },
    
    /**
     * @private
     */
    moveNext:function(btn,e){
        this.move(this.currentItem+1);
    },
    
    /**
     * @private
     */
    movePrevious:function(btn,e){
        this.move(this.currentItem-1);
    },
    
    /**
     * @private
     */
    hideHanlder :function(){
        if(this.fireEvent('cancel')) {
            this.hide();
        }
    },
    
    /**
     * @private
     */
    finishHanlder:function(){
        if(this.fireEvent('finish')) {
            this.hide();
        }
    }
});