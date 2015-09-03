<?php

/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 *
 */

/**
 * interface for storage capabilities
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
interface Expressodriver_Backend_Storage_Capabilities
{

    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities();
}
