<?php
/**
 * Expresso Lite Accessible
 * Utility class that provides mail message facilities that are
 * shared by several accessible request handlers.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Core;

class MessageIds
{
    /**
     * Receive the parameters and creates a string comma separated of message ids
     *
     * @param object $params Contains 'check_' attributes each representing a message id
     * @return string Comma separated of message ids
     */
    public static function paramsToString($params)
    {
        $objVarToArray = get_object_vars($params);
        foreach($objVarToArray as $key => $value) {
            if (!preg_match('/check_/', $key)) {
                unset($objVarToArray[$key]);
            }
        }

        return implode(',', $objVarToArray);
    }

    /**
     * Gets the number of messages id
     *
     * @param string $messageIds Not empty and has one or more message ids(comma separated)
     * @return int A value indicating the number of message ids
     */
    public static function messageCount($messageIds)
    {
        return $messageIds === '' ? 0 : count(explode(',', $messageIds));
    }
}
