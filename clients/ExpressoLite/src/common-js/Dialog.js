/*!
 * Expresso Lite
 * A resizable and draggable modal/modeless popup DIV, for desktop and phones.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/UrlStack'
],
function($, App, UrlStack) {
App.LoadCss('common-js/Dialog.css');
var Dialog = function(options) {
    var userOpts = $.extend({
        $elem: null, // jQuery object for the target DIV
        caption: 'Modeless popup',
        width: 400,
        height: 400,
        minWidth: 200,
        minHeight: 100,
        modal: true,
        resizable: true,
        minimizable: true,
        marginMaximized: 8,
        marginLeftMaximized: 360
    }, options);

    var THIS          = this;
    var onUserCloseCB = null; // user callbacks
    var onCloseCB     = null;
    var onResizeCB    = null;
    var $targetDiv    = userOpts.$elem;
    var $tpl          = null;
    var stackId       = 0; // in phones, popups can be stacked
    var cyTitle       = 0; // height of titlebar; never changes
    var prevMinPos    = { x:0, y:0, cx:0, cy:0 }; // position/size before minimize
    var prevMaxPos    = { x:0, y:0, cx:0, cy:0 }; // before maximize

    function _SetEvents() {
        $tpl.find('.Dialog_bar').on('mousedown', function(ev) {
            ev.stopImmediatePropagation();

            if ($tpl.find('.Dialog_content').is(':visible')) {
                var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
                var pop = { x:$tpl.offset().left, y:$tpl.offset().top,
                    cx:$tpl.outerWidth(), cy:$tpl.outerHeight() };
                var offset = { x:ev.clientX - pop.x, y:ev.clientY - pop.y };
                $('div').addClass('Dialog_unselectable');

                $(document).on('mousemove.Dialog', function(ev) {
                    ev.stopImmediatePropagation();
                    var newPos = { x:ev.clientX - offset.x, y:ev.clientY - offset.y };
                    var destCss = { };
                    if (newPos.x >= 0 && newPos.x + pop.cx <= wnd.cx) destCss.left = newPos.x+'px';
                    if (newPos.y >= 0 && newPos.y + pop.cy <= wnd.cy) destCss.top = newPos.y+'px';
                    $tpl.css(destCss);
                });

                $(document).on('mouseup.Dialog', function(ev) {
                    $('div').removeClass('Dialog_unselectable');
                    $(document).off('.Dialog');
                });
            } else {
                THIS.toggleMinimize();
            }
        });

        $tpl.find('.Dialog_bar').on('dblclick', function(ev) {
            ev.stopImmediatePropagation();
            var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
            if (!App.IsPhone()) { // on phones doubleclick does nothing
                if ($tpl.find('.Dialog_content').is(':visible')) { // not minimized
                    var isMaximized = ($tpl.offset().left === userOpts.marginLeftMaximized) &&
                        ($tpl.offset().top === userOpts.marginMaximized) &&
                        ($tpl.outerWidth() === wnd.cx - userOpts.marginLeftMaximized - userOpts.marginMaximized) &&
                        ($tpl.outerHeight() === wnd.cy - userOpts.marginMaximized * 2);

                    if (!isMaximized) {
                        prevMaxPos = { // keep current pos
                            x: $tpl.offset().left,
                            y: $tpl.offset().top,
                            cx: $tpl.outerWidth(),
                            cy: $tpl.outerHeight()
                        };
                        $tpl.css({
                            left: userOpts.marginLeftMaximized+'px',
                            top: userOpts.marginMaximized+'px',
                            width: (wnd.cx - userOpts.marginLeftMaximized - userOpts.marginMaximized)+'px',
                            height: (wnd.cy - userOpts.marginMaximized * 2)+'px'
                        });
                    } else {
                        $tpl.css({ // restore previous pos
                            left: prevMaxPos.x+'px',
                            top: prevMaxPos.y+'px',
                            width: prevMaxPos.cx+'px',
                            height: prevMaxPos.cy+'px'
                        });
                    }

                    if (onResizeCB !== null) {
                        onResizeCB(); // invoke user callback
                    }
                } else { // if minimized, simply restore; never happens because mousedown comes first
                    THIS.toggleMinimize();
                }
            }
        });

        $tpl.find('.Dialog_resz').on('mousedown', function(ev) {
            ev.stopImmediatePropagation();
            var wnd = { cx:$(window).outerWidth(), cy:$(window).outerHeight() };
            var pop = { x:$tpl.offset().left, y:$tpl.offset().top,
                cx:$tpl.outerWidth(), cy:$tpl.outerHeight() };
            var orig = { x:ev.clientX, y:ev.clientY };
            $('div').addClass('Dialog_unselectable');

            $(document).on('mousemove.Dialog', function(ev) {
                ev.stopImmediatePropagation();
                var newSz = { cx:pop.cx + ev.clientX - orig.x, cy:pop.cy + ev.clientY - orig.y };
                var destCss = { };
                if (pop.x + newSz.cx < wnd.cx) {
                    destCss.width = newSz.cx+'px';
                }
                if (pop.y + newSz.cy < wnd.cy) {
                    destCss.height = newSz.cy+'px';
                }
                $tpl.css(destCss);
                if (onResizeCB !== null) {
                    onResizeCB();
                }
            });

            $(document).on('mouseup.Dialog', function(ev) {
                $('div').removeClass('Dialog_unselectable');
                $(document).off('.Dialog');
            });
        });

        $tpl.find('.Dialog_minCage input').on('click', function(ev) {
            ev.stopImmediatePropagation();
            THIS.toggleMinimize();
        });

        $tpl.find('.Dialog_backBtn input,.Dialog_closeCage input').on('click', function(ev) {
            ev.stopImmediatePropagation();
            if (onUserCloseCB !== null) {
                onUserCloseCB(); // invoke user callback, user must call close() himself
            }
        });
    }

    THIS.show = function() {
        var defer = $.Deferred();
        $tpl = $('#Dialog_template .Dialog_box').clone();
        $tpl.find('.Dialog_title').html(userOpts.caption);

        var szCss = App.IsPhone() ?
            { width:'100%', height:'100%' } : // on phones, go fullscreen
            { width:userOpts.width+'px',
                height:Math.max(userOpts.height, userOpts.minHeight)+'px' }; // on desktop, user chooses size
        $tpl.css(szCss).appendTo(document.body);
        $tpl.find('.Dialog_content').append($targetDiv);

        if (App.IsPhone()) {
            $tpl.find('.Dialog_minCage,.Dialog_closeCage,.Dialog_resz').hide();
            ++stackId;
            UrlStack.push('#Dialog'+stackId, function() {
                $tpl.find('.Dialog_closeCage input:first').trigger('click');
            });

            $tpl.offset({ left:$(window).outerWidth() }) // slide from right
                .animate({ left:'0px' }, 300, function() { defer.resolve(); });
        } else {
            $tpl.css({
                'min-width': userOpts.minWidth+'px',
                'min-height': userOpts.minHeight+'px',
                left: ($(window).outerWidth() / 2 - $tpl.outerWidth() / 2)+'px', // center screen
                top: Math.max(0, $(window).outerHeight() / 2 - $tpl.outerHeight() / 2)+'px'
            });
            $tpl.find('.Dialog_backBtn').hide();
            if (!userOpts.resizable) {
                $tpl.find('.Dialog_resz').hide();
            }
            if (!userOpts.minimizable) {
                $tpl.find('.Dialog_minCage').hide();
            }

            var yOff = $tpl.offset().top;
            $tpl.offset({ top:-$tpl.outerHeight() }) // slide from above
                .animate({ top:yOff+'px' }, 200, function() {
                    if (userOpts.modal) {
                        $('#Dialog_template .Dialog_coverAllScreen')
                            .clone().insertBefore($tpl);
                    }
                    defer.resolve();
                });
        }

        cyTitle = $tpl.find('.Dialog_bar').outerHeight();
        _SetEvents();
        return defer.promise();
    };

    THIS.getContentArea = function() {
        return { cx:$tpl.outerWidth(), cy:$tpl.outerHeight() - cyTitle };
    };

    THIS.isOpen = function() {
        return $tpl !== null;
    };

    THIS.close = function() {
        var defer = $.Deferred();
        if (userOpts.modal) {
            $('.Dialog_coverAllScreen:last').remove();
        }
        var animMove = App.IsPhone() || THIS.isMinimized() ?
            { left: $(window).outerWidth()+'px' } : // slide to right
            { top: $(window).outerHeight()+'px' }; // slide to bottom
        $tpl.animate(animMove, 200, function() {
            $targetDiv.detach(); // element is up to user
            $tpl.remove();
            $tpl = null;
            UrlStack.pop('#Dialog'+stackId);
            --stackId;
            if (onCloseCB !== null) {
                onCloseCB(); // invoke user callback
            }
            defer.resolve();
        });
        return defer.promise();
    };

    THIS.isMinimized = function() {
        return !$tpl.find('.Dialog_content').is(':visible');
    };

    THIS.toggleMinimize = function() {
        var willMin = !THIS.isMinimized();
        $tpl.find('.Dialog_content,.Dialog_resz').toggle();
        $tpl.find('.Dialog_closeCage').toggle(!willMin);
        $tpl.find('.Dialog_minCage input').val(willMin ? '¯' : '_');
        if (willMin) {
            prevMinPos = { // keep current pos
                x: $tpl.offset().left,
                y: $tpl.offset().top,
                cx: $tpl.outerWidth(),
                cy: $tpl.outerHeight()
            };
            $tpl.css({
                width: userOpts.minWidth+'px',
                left: ($(window).outerWidth() - userOpts.minWidth - 18)+'px',
                top: ($(window).outerHeight() - cyTitle)+'px'
            });
        } else {
            $tpl.css({ // restore previous pos
                left: prevMinPos.x+'px',
                top: prevMinPos.y+'px',
                width: prevMinPos.cx+'px',
                height: prevMinPos.cy+'px'
            });
        }
    };

    THIS.setCaption = function(text) {
        $tpl.find('.Dialog_title').empty().append(text);
        return THIS;
    };

    THIS.removeCloseButton = function() {
        $tpl.find('.Dialog_closeCage').remove();
        return THIS;
    };

    THIS.onUserClose = function(callback) {
        onUserCloseCB = callback; // triggered only when the user clicks the close button
        return THIS;
    };

    THIS.onClose = function(callback) {
        onCloseCB = callback; // onClose()
        return THIS;
    };

    THIS.onResize = function(callback) {
        onResizeCB = callback; // onResize()
        return THIS;
    };
};

Dialog.Load = function() {
    // Static method, since this class can be instantied ad-hoc.
    return $('#Dialog_template').length ?
        $.Deferred().resolve().promise() :
        App.LoadTemplate('../common-js/Dialog.html');
};

return Dialog;
});
