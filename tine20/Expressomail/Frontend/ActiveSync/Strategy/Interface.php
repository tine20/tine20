<?php
/**
 * Tine 2.0
*
* @package     Expressomail
* @subpackage  Frontend
* @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
* @copyright   Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
* @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
*/
/**
 * ActiveSync Strategy Interface
*
* @package     Expressomail
* @subpackage  Frontend
*/
interface Expressomail_Frontend_ActiveSync_Strategy_Interface
{
    /**
     * @param array $source
     * @param string $inputStream
     * @param bool $saveInSent
     * @param bool $replaceMime
     * @param Expressomail_Frontend_ActiveSync $_frontend
     * @see Syncroton_Data_IDataEmail::forwardEmail()
     */
    public static function forwardEmail($source, $inputStream, $saveInSent, $replaceMime, $frontend);

    /**
     * @param array $source
     * @param string $inputStream
     * @param bool $saveInSent
     * @param bool $replaceMime
     * @param Expressomail_Frontend_ActiveSync $_frontend
     * @see Syncroton_Data_IDataEmail::forwardEmail()
     */
    public static function replyEmail($source, $inputStream, $saveInSent, $replaceMime, $frontend);
}