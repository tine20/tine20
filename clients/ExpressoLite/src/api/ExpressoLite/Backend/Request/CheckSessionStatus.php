<?php
/**
 * Expresso Lite
 * Handler for EchoParams calls.
 * It checks if the current tine session is logged in
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class CheckSessionStatus extends LiteRequest
{

    /**
     * @var STATUS_INACTIVE Constant that indicates that
     *      there is NOT a currently estabilished session
     */
    const STATUS_INACTIVE = 'inactive';

    /**
     * @var STATUS_ACTIVE Constant that indicates that there is
     *     a currently estabilished session
     */
    const STATUS_ACTIVE = 'active';

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        if (!$this->tineSession->isLoggedIn() || 
            !$this->tineSession->tineIsAuthenticated()) {
            // User logged off explicitly OR
            // Lite think its logged in, but Tine has no authentication info
            $status = self::STATUS_INACTIVE; 
        } else {
            //Lite and Tine agree they are both logged in 
            $status = self::STATUS_ACTIVE;
        }

        return (object) array(
            'status' => $status
        );
    }

    /**
     * Allows this request to be executed even without a previously
     * estabilished TineSession.
     *
     * @return true.
     */
    public function allowAccessWithoutSession()
    {
        return true;
    }
}
