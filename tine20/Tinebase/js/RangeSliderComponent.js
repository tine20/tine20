/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

/**
 * File picker dialog
 *
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.RangeSliderComponent
 * @extends     Ext.Component
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.Tinebase.RangeSliderComponent = Ext.extend(Ext.BoxComponent, {
    minWidth: 100,
    height: 25,

    slider: null,
    sliderResizer: null,
    sliderLabel: null,
    sliderBorder: null,

    maxRange: 23.99,

    currentStart: 0,
    currentEnd: 23.99,

    cls: 'rangeslider-component',

    name: null,

    /**
     * @private
     */
    initComponent: function () {
        this.supr().initComponent.apply(this, arguments);
        this.on('afterrender', this.onAfterRender);
    },

    /**
     * We have an element called sliderBorder which can be styled.
     *
     * The sliderWrap is used to calculate positions of the slider within this element, therefor you shouldn't apply
     * box model affecting styles to the slider itself or the the sliderWrap.
     *
     * The slider is a simple div which is managed by Ext.Resizable and contains a span which can be used as a label.
     *
     * @todo: maybe this can be done better
     *
     * @private
     */
    initSlider: function () {
        this.sliderWrap = new Ext.Element(document.createElement('div'));

        this.sliderBorder = new Ext.Element(document.createElement('div'));
        this.sliderBorder.addClass('rangeslider-component-sliderBorder');

        this.slider = new Ext.Element(document.createElement('div'));
        this.slider.addClass('rangeslider-component-slider');

        this.sliderLabel = new Ext.Element(document.createElement('span'));
        this.sliderLabel.setHeight(this.height);
        this.sliderLabel.addClass('rangeslider-component-slider-label');

        this.slider.appendChild(this.sliderLabel);
        this.sliderWrap.appendChild(this.slider);

        this.sliderBorder.appendChild(this.sliderWrap);

        this.getEl().appendChild(this.sliderBorder);

        this.sliderResizer = new Ext.Resizable(this.slider, {
            handles: 'e w',
            pinned: true,
            dynamic: true
        });
    },

    /**
     * @private
     */
    onAfterRender: function () {
        this.initSlider();
        this.setSliderWidth(this.currentStart, this.currentEnd);
    },

    /**
     * @private
     *
     * In case you won't use this class for a hour, min slider, you should override this function and update the label yourself
     */
    updateLabel: function () {
        this.sliderLabel.setWidth(this.sliderResizer.getEl().getWidth());

        if (this.sliderLabel.getWidth() > this.slider.getWidth()) {
            this.sliderLabel.hide();
        } else {
            this.sliderLabel.show();
        }

        var hoursStart = this.trunc(this.currentStart);
        var minStart = this.trunc(Math.round((this.currentStart % 1) * 100) * 0.60);

        var hoursEnd = this.trunc(this.currentEnd);
        var minEnd = this.trunc((this.currentEnd % 1) * 100 * 0.60);

        var startDate = new Date();
        startDate.setHours(hoursStart);
        startDate.setMinutes(minStart);

        var endDate = new Date();
        endDate.setHours(hoursEnd);
        endDate.setMinutes(minEnd);

        var pattern = 'H:i';

        this.sliderLabel.update(startDate.format(pattern) + ' - ' + endDate.format(pattern));
    },

    /**
     * @private
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
     * @private
     */
    getCropFactor: function () {
        return this.sliderWrap.getWidth(true) / this.maxRange;
    },

    /**
     * @private
     */
    onSliderResize: function () {
        var el = this.sliderResizer.getEl();
        var offsetLeft = el.getOffsetsTo(this.sliderWrap)[0];
        var offsetWidth = el.dom.offsetWidth;
        var cropFactor = this.getCropFactor();

        this.currentStart = offsetLeft / cropFactor;
        this.currentEnd = (offsetLeft + offsetWidth) / cropFactor;

        var invalidState = false;

        if (this.currentEnd > this.maxRange) {
            this.currentEnd = this.maxRange;

            invalidState = true;
        }

        if (this.currentStart < 0) {
            this.currentStart = 0;

            invalidState = true;
        }

        if (true === invalidState) {
            this.setSliderWidth(this.currentStart, this.currentEnd);
        }

        this.updateLabel();
    },

    /**
     *  @private
     */
    resizeElement: function () {
        var box = Ext.Resizable.prototype.resizeElement.apply(this.sliderResizer);

        this.onSliderResize();

        return box;
    },

    /**
     * Set the slider width
     *
     * @param start 0-23.99
     * @param end 0-23.99
     */
    setSliderWidth: function (start, end) {
        // End is not allowed to be smaller
        if (end < start) {
            return;
        }

        this.currentStart = start;
        this.currentEnd = end;

        var cropFactor = this.getCropFactor();
        var leftOffset = start * cropFactor;
        var width = (end * cropFactor) - (start * cropFactor);

        this.slider.alignTo(this.sliderWrap, 'tl-tl', [leftOffset, 0]);

        this.sliderResizer.resizeElement = this.resizeElement.createDelegate(this);
        this.sliderResizer.on('resize', this.onSliderResize.createDelegate(this));

        this.sliderResizer.resizeTo(width, this.sliderResizer.getEl().getHeight());
    },

    /**
     * Returns current span
     */
    getRange: function () {
        return [this.currentStart, this.currentEnd];
    },

    /**
     * Set slider to a certain position by entering an array with start and end
     */
    setRange: function (range) {
        if (!Ext.isArray(range) || range.length !== 2) {
            return;
        }

        this.setSliderWidth(range[0], range[1]);
    }
});