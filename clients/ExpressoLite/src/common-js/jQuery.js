/*!
 * Expresso Lite
 * Loads jQuery and adds support for $.velocity() calls.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

require.config({
    paths: {
        jquery: 'common-js/external/jquery.min',
        velocity: 'common-js/external/velocity.min'
    },
    shim: {
        velocity: { deps:['jquery'] }
    }
});

define(['jquery', 'velocity'], function($, Velocity) {
    return $; // $().animate() calls can now be replaced with $().velocity()
});
