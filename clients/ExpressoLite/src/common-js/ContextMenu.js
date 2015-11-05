/*!
 * Expresso Lite
 * Dynamic popup menu widget, which is shown on hovering.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2014-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/UrlStack'
],
function($, App, UrlStack) {
App.LoadCss('common-js/ContextMenu.css');
return function(options) {
    var userOpts = $.extend({
        $btn: null // jQuery object for the target button
    }, options);

    var THIS = this;
    var $btn = userOpts.$btn;
    var $ul = $(document.createElement('ul'))
        .addClass('ContextMenu_ul');

    function _IsShown() {
        return $ul.parent().length !== 0;
    }

    function _HidePopup() {
        var defer = $.Deferred();
        if (_IsShown()) {
            UrlStack.pop('#popup');
            $ul.css({ width:'', height:'' }); // revert to natural dimensions, if changed
            $ul.detach();
            $btn.removeClass('ContextMenu_hoverBtn');

            if ($('.ContextMenu_darkCover').length) { // shown on phone only
                $('.ContextMenu_darkCover').fadeOut(200, function() {
                    $('.ContextMenu_darkCover').remove();
                    defer.resolve();
                });
            } else {
                defer.resolve();
            }
        } else {
            defer.resolve();
        }
        return defer.promise();
    }

    function _ApplyIndentation(text, nIndent) {
        var prefix = '';
        if (nIndent !== undefined && nIndent !== null) { // text indentation
            for (var i = 0; i < nIndent; ++i) {
                prefix += '&nbsp; ';
            }
        }
        return prefix+text;
    }

    THIS.purge = function() {
        $ul.empty();
        return THIS;
    };

    THIS.addOption = function(text, callback, indent) {
        if (indent === undefined) indent = 0;
        var $li = $(document.createElement('li'))
            .addClass('ContextMenu_liOption')
            .data('callback', callback)
            .html(_ApplyIndentation(text, indent))
            .appendTo($ul);
        return THIS;
    };

    THIS.addHeader = function(text, indent) {
        if (indent === undefined) indent = 0;
        var $li = $(document.createElement('li'))
            .addClass('ContextMenu_liHeader')
            .html(_ApplyIndentation(text, indent))
            .appendTo($ul);
        return THIS;
    };

    $btn.add($ul).on('mouseenter.ContextMenu', function() {
        var szPage = { cx:$(window).width(), cy:$(window).height() };
        if (App.IsPhone()) {
            return; // we're in phone, no mouseover event
        }

        var posBtn = { x:$btn.offset().left, y:$btn.offset().top };
        var szBtn = { cx:$btn.outerWidth(), cy:$btn.outerHeight() };
        $ul.appendTo(document.body)
            .css({ width:'', height:'' }); // revert to natural dimensions, if changed
        var szUl = { cx:$ul.outerWidth(), cy:$ul.outerHeight() };
        var posUl = { };

        if (posBtn.y + szBtn.cy + szUl.cy > szPage.cy) { // popup goes beyond page height; shrink, scrollbar will appear
            szUl.cy = szPage.cy - posBtn.y - szBtn.cy - 8; // gap for prettiness
            szUl.cx += 20; // scrollbar room
        }

        posUl.x = (posBtn.x + szUl.cx + 8 > szPage.cx) ?
            (szPage.cx - szUl.cx - 8) : // popup goes beyond page width, pull it back
            posBtn.x;
        posUl.y = posBtn.y + szBtn.cy;

        $ul.css({ left:posUl.x+'px', top:posUl.y+'px', width:szUl.cx+'px', height:szUl.cy+'px' });
        $btn.addClass('ContextMenu_hoverBtn');
    });

    $btn.add($ul).on('mouseleave.ContextMenu', function() {
        if (!App.IsPhone()) {
            _HidePopup();
        }
    });

    $btn.on('click.ContextMenu', function(ev) {
        ev.stopImmediatePropagation();
        var szPage = { cx:$(window).width(), cy:$(window).height() };
        if (!App.IsPhone()) {
            return; // we're in desktop, no click event
        }

        $(document.createElement('div'))
            .addClass('ContextMenu_darkCover')
            .appendTo(document.body)
            .one('click.ContextMenu', _HidePopup);

        $ul.appendTo(document.body)
            .css({ width:'', height:'' }); // revert to natural dimensions, if changed
        var szUl = { cx:$ul.outerWidth(), cy:$ul.outerHeight() };
        var posUl = { };

        if (szUl.cy > szPage.cy) { // popup taller than page, make it fit with scrollbar
            szUl.cy = szPage.cy - 12; // gap for prettiness
            posUl.y = 6;
        } else {
            posUl.y = (szPage.cy - szUl.cy) / 2;
        }
        posUl.x = (szPage.cx / 2) - (szUl.cx / 2);
        szUl.cx += 4; // looks better

        $ul.css({
            left: posUl.x+'px',
            top: posUl.y+'px',
            width: szUl.cx+'px',
            height: szUl.cy+'px',
            display: 'none',
            'box-shadow': 'none' // fadeIn renders better
        }).fadeIn(200, function() {
            $ul.css('box-shadow', '');
        });

        $btn.addClass('ContextMenu_hoverBtn');
        UrlStack.push('#popup', _HidePopup);
    });

    $ul.on('click.ContextMenu', '.ContextMenu_liOption', function(ev) {
        ev.stopImmediatePropagation();
        var cb = $(this).data('callback');
        _HidePopup().done(function() {
            if (cb !== null && cb !== undefined) {
                cb(); // invoke user callback
            }
        });
    });
};
});
