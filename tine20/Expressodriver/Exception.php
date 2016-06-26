<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * Expressodriver exception
 *
 * @package     Expressodriver
 * @subpackage  Exception
 */
class Expressodriver_Exception extends Tinebase_Exception
{
    /**
     * the name of the application, this exception belongs to
     *
     * @var string
     */
    protected $_appName = 'Expressodriver';
}
