/*!
 * Expresso Lite
 * Utiliy widget that can execute any method in any JS component
 * of the application.
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
App.LoadCss('debugger/WidgetComponentTest.css');

return function(options) {
    var userOpts = $.extend({
        $parentContainer: null
    }, options);

    var THIS = this;

    var componentIndex = {
        App: App
    }

    /* This function uses regex to retrieve the name of the params used by a javascript function
     * More details on how it works in:
     * http://stackoverflow.com/questions/1007981/how-to-get-function-parameter-names-values-dynamically-from-javascript
     */
    function getParamNames(func) {
        var STRIP_COMMENTS = /((\/\/.*$)|(\/\*[\s\S]*?\*\/))/mg;
        var ARGUMENT_NAMES = /([^\s,]+)/g;

        var fnStr = func.toString().replace(STRIP_COMMENTS, '');
        var result = fnStr.slice(fnStr.indexOf('(')+1, fnStr.indexOf(')')).match(ARGUMENT_NAMES);
        if(result === null) {
            result = [];
        }
        return result;
    }

    function parseParameterValue(parVal) {
        if (parVal === 'null') {
            return null;
        } else if (parVal === 'undefined') {
            return undefined;
        } else if (parVal.lastIndexOf('{', 0) === 0) { //starts with {
            return JSON.parse(parVal);
        } else {
            return parVal;
        }
    }

    function formatResultValue(resVal) {
        if (resVal === undefined) {
            return 'undefined';
        } else if (resVal === null) {
            return 'null';
        } else {
            return JSON.stringify(resVal, null, 4);
        }
    }

    function initComponentSelect() {
        App.Post('getJSComponentList')
        .done(function(result) {
            for (var i=0; i < result.components.length; ++i) {
                var compName = result.components[i];
                $('#componentSelect').append('<option value="' + compName + '">' + compName + '</option>');
            }
        });
    }

    function initEventListeners() {
        $('#componentSelect').on('change', function() {
            $('#functionSelect')
            .empty()
            .append('<option value="">-- Selecione --</option>');

            var compName = $(this).val();
            var component = componentIndex[compName];

            function populateFunctionSelect(comp) {
                for (var funcName in comp) {
                    $('#functionSelect').append('<option value="' + funcName +'">' + funcName +'</option>');
                }
            }

            if (component === undefined) {
                require([compName], function(component) {
                    componentIndex[compName] = component;
                    populateFunctionSelect(component);
                });
            } else {
                populateFunctionSelect(component);
            }
        });

        $('#functionSelect').on('change', function() {
            var component = componentIndex[$('#componentSelect').val()];
            var func = component[$(this).val()];
            var params = getParamNames(func);
            var $paramTemplate = $('#WidgetComponentTest_templates .WidgetComponentTest_parameterField');

            $('#parameters_div').empty();

            if (params.length == 0) {
                $('#parameters_div').append('No parameters');
            } else {
                for (var i=0; i < params.length; i++) {
                    var $paramDiv = $paramTemplate.clone();
                    $paramDiv.find('.WidgetComponentTest_parameterValue').attr('id', 'par' + i);
                    $paramDiv.find('.WidgetComponentTest_parameterLabel').text(params[i]);
                    $('#parameters_div').append($paramDiv);
                }
            }
        });

        $('#btnExecute').on('click', function() {
            $('#resultArea').text('');

            if ($('#componentSelect').val() == '' ||
                $('#functionSelect').val() == '') {
                alert('Selecione um componente e uma função para executar um teste');
                return;
            }

            var component = componentIndex[$('#componentSelect').val()];
            var func = component[$('#functionSelect').val()];

            var parArray = [];
            for (var i=0; $('#par' + i).length > 0; i++) {
                var parVal = $('#par' + i).val();
                parArray.push(parseParameterValue(parVal));
            }
            var result = func.apply(component, parArray);

            if (result === null || result === undefined ||
                (result.done === undefined && result.fail === undefined)) {
                $('#resultArea').text(formatResultValue(result));
            } else {
                result.done(function() {
                    $('#resultArea').text('Result: ');
                }).fail(function() {
                    $('#resultArea').text('Error: ');
                }).always(function (value) {
                    $('#resultArea').append(formatResultValue(value));
                });
            }
        });
    }

    THIS.Hide = function () {
        userOpts.$parentContainer.hide();
    };

    THIS.Show = function () {
        userOpts.$parentContainer.show();
    };

    THIS.Refresh = function () {
    };

    THIS.GetTitle = function () {
        return 'Component Test';
    };

    THIS.Load = function () {
        var defer = $.Deferred();
        App.LoadTemplate('WidgetComponentTest.html')
        .done(function () {
            userOpts.$parentContainer.append($('#WidgetComponentTest_div'));
            initEventListeners();
            initComponentSelect();
            THIS.Refresh();
            defer.resolve();
        }).fail(function () {
            defer.reject();
        });
    }
}
});

