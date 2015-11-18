/*!
 * Expresso Lite
 * Widget that an index of letters on the right side of the
 * phone screen
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
    App.LoadCss('addressbook/WidgetLetterIndex.css');

    return function(options) {
        var userOpts = $.extend({
            $parentContainer: null,
            reducedCharsThreshold: 500
        }, options);

        var THIS = this;

        var onLetterSelectCB = null;

        var chars = [
             '#', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q',
            'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

        var reducedChars = [
            '#', 'A', 'B', '-', 'G', 'H', '-', 'M',
            '-', 'R', 'S', '-', 'X', 'Y', 'Z'];

        var displayMode; //will be set during regenerateHtmlTable();

        var $table =
            $(document.createElement('table'))
            .attr('id', 'letterIndexTable')
            .appendTo(userOpts.$parentContainer);

        var $highlightedLetterDiv =
            $(document.createElement('div'))
            .attr('id', 'highlightedLetterDiv')
            .hide()
            .appendTo(userOpts.$parentContainer);

        var tableHeight;

        var selectedChar = '';

        function regenerateHtmlTable() {
            var displayedChars;
            if ($(window).height() < userOpts.reducedCharsThreshold) {
                displayMode = 'reduced';
                displayedChars = reducedChars;
            } else {
                displayMode = 'full';
                displayedChars = chars;
            }

            var tableHtml = '';
            for (var i=0; i< displayedChars.length; i++) {
                tableHtml += '<tr><td>' + displayedChars[i] + '</td></tr>';
            }
            return $table.html(tableHtml);
        }

        function highlightIndex(touchY) {
            var relativePos = touchY - 50;

            // keep relativePos within table bounds
            if (relativePos < 0) {
                relativePos = 0
            } else if (relativePos >= tableHeight - 1) {
                relativePos = tableHeight -1;
            }

            var letterIndexHeight = tableHeight / chars.length; //height of a single letter index (non highlighted)
            var currIndex = Math.floor(relativePos / letterIndexHeight); //index of the letter index currently selected
            var newChar = chars[currIndex];

            if (newChar != selectedChar) {
                $highlightedLetterDiv.css('top',
                        50 + //page header offset
                        currIndex * letterIndexHeight +  // y of current index
                        ((letterIndexHeight - 32) / 2)); // adjustments to center index with highlighted div
                                                         // (32 is highlighted div height)
                $highlightedLetterDiv.html(newChar);
                $highlightedLetterDiv.show();

                onLetterSelectCB(newChar);

                selectedChar = newChar;
            }
        }

        THIS.onLetterSelect = function(callback) {
            onLetterSelectCB = callback;
        }

        $table.on('touchstart', function (e) {
           e.originalEvent.preventDefault();
           // if you don't do this, touchmove won't work on android
           // http://uihacker.blogspot.tw/2011/01/android-touchmove-event-bug.html

           tableHeight = $table.outerHeight();
           //calculate this beforehand to reduce processing

           highlightIndex(e.originalEvent.touches[0].clientY);

        });

        $table.on('touchmove', function (e) {
            highlightIndex(e.originalEvent.touches[0].clientY);
        });

        $table.on('touchend', function (e) {
            $highlightedLetterDiv.hide();
            selectedChar = '';
        });

        (function constructor() {
            regenerateHtmlTable();

            $(window).resize(function(){
                if (App.IsPhone()) {
                    if (($(window).height() < userOpts.reducedCharsThreshold && displayMode === 'full') ||
                        ($(window).height() >= userOpts.reducedCharsThreshold && displayMode === 'reduced')) {
                        regenerateHtmlTable();
                    }
                }
            });
        })();
    }
});
