/*!
 * Expresso Lite
 * Left menu of addressbook module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App'
],
function($, App) {

    App.LoadCss('addressbook/WidgetCatalogMenu.css');

    return function(options) {
        var userOpts = $.extend({
            $parentContainer: null
        }, options);

        var THIS = this;
        var $ul = $(document.createElement('ul')).attr('id', 'WidgetCatalogMenu_list').appendTo(options.$parentContainer);
        var $selectedLi = null;

        function selectLi($li) {
            if ($selectedLi != null) {
                $selectedLi.removeClass('selected');
            }
            $selectedLi = $li;
            $selectedLi.addClass('selected');
        }

        THIS.addOption = function (label, callback) {
            var $li = $(document.createElement('li'));

            if ($selectedLi == null) {
                selectLi($li);
            }
            $li.html(label);
            $li.data('callback', callback);

            $li.on('click', function () {
                var $this = $(this);
                if (!$this.hasClass('selected')) {
                    selectLi($this);
                }
                $this.data('callback')(); //invokes callback associated to <li>
            });

            $li.appendTo($ul);
            return THIS;
        }
    }
});
