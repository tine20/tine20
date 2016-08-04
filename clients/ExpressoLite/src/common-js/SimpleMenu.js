/*!
 * Expresso Lite
 * Simple menu for left menu section of Layout.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App'
],
function($, App) {
App.loadCss('common-js/SimpleMenu.css');
return function(options) {
    var userOpts = $.extend({
        $parentContainer: null
    }, options);

    var THIS = this;
    var $ul = $(document.createElement('ul'))
        .addClass('SimpleMenu_list')
        .appendTo(options.$parentContainer);

    THIS.addOption = function(label, identifier, callback) {
        var $li = $(document.createElement('li'));
        $li.append( $(document.createElement('span')).html(label) );
        $li.data('identifier', identifier);
        $li.data('callback', callback);
        $li.appendTo($ul);
        return THIS;
    };

    THIS.clearSelection = function() {
        $ul.find('.SimpleMenu_selected').removeClass('SimpleMenu_selected');
        return THIS;
    };

    THIS.selectOption = function(identifier) {
        $ul.find('li').each(function(idx, li) {
            if ($(li).data('identifier') === identifier) {
                _SelectLi($(li));
                return false;
            }
        });
        return THIS;
    };

    THIS.selectFirstOption = function() {
        _SelectLi( $ul.find('li:first') );
        return THIS;
    };

    THIS.getSelectedIdentifier = function() {
        return $ul.find('.SimpleMenu_selected').data('identifier');
    };

    userOpts.$parentContainer.on('click', '.SimpleMenu_list li', function() {
        THIS.clearSelection();
        _SelectLi($(this));
    });

    function _SelectLi($li) {
        THIS.clearSelection();
        $li.addClass('SimpleMenu_selected')
            .data('callback')(); // invoke associated user callback
    };
};
});
