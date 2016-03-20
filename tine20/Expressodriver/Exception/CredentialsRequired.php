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
 */

/**
 * CredentialsNeeded exception
 *
 * @package     Expressodriver
 * @subpackage  Exception
 */
class Expressodriver_Exception_CredentialsRequired extends Expressodriver_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Credentials required';

    /**
     * @see SPL Exception
     */
    protected $message = 'Your credentials for Expressodriver are required';

    /**
     * @see SPL Exception
    */
    protected $code = 904;

    /**
     * adapter name where credentials are required
     *
     * @var string
     */
    protected $adapterName = '';

    /**
     * set adapter name
     *
     * @param string $adapterName
     */
    public function setAdapterName($adapterName)
    {
        $this->adapterName = $adapterName;
    }

    /**
     * returns adapter name info as array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'adaptername' => $this->adapterName
        );
    }
}
