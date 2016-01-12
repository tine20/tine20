<?php
/**
 * Expresso Lite
 * Handler for getContactsByFilter calls.
 *
 * @package Backend
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class GetContactCatalogsCategories extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        return (object) array(
            'personal' => (object) array(
                'title' => 'Catálogo Pessoal',
                'containerPath' => '/personal/' . $this->tineSession->getAttribute('Tinebase.accountId'),
                'pageLimit' => 9999, //no limit
                'autoload' => true
            ),
            'corporate' => (object) array(
                'title' => 'Catálogo Corporativo',
                'containerPath' => '/shared',
                'pageLimit' => 50,
                'autoload' => false
            ),
        );
    }
}
