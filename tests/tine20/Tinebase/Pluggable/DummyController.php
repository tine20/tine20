<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Pluggable
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * 
 */
/**
 * Mock for Controller
  */
class Tinebase_Pluggable_DummyController extends Tinebase_Controller_Abstract
{
    
    /**
     * Instance of Controller Object.
     */
    public static function getInstance()
    {
        return null;
    }
}
