<?php
/**
 * Expresso Lite
 * Handler for downloadAttachment calls. This class has the special
 * characteristic of generating output directly.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\TineTunnel\TineJsonRpc;
use ExpressoLite\TineTunnel\Request;

class DownloadAttachment extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        // This method will directly output the binary stream to client.
        // Intended to be called on a "_blank" window.
        $fileName = $this->param('fileName');
        $messageId = $this->param('messageId');
        $partId = $this->param('partId');

        $mimeType = $this->isParamSet('forceDownload') ? null : $this->getMimeType($fileName);
        if ($mimeType != null) {
            //WITHOUT 'attachment' in header, will be opened in browser
            header("Content-Disposition: filename=\"$fileName\"");
            header('Content-Type: ' . $mimeType);
        } else {
            //WITH 'attachment' in header, will be downloaded
            header("Content-Disposition: attachment; filename=\"$fileName\"");
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: binary');
        }

        $req = new Request();
        $req->setUrl($this->tineSession->getTineUrl() . '?method=Tinebase.uploadTempFile');
        $req->setCookieHandler($this->tineSession); //tineSession has the necessary cookies
        $req->setBinaryOutput(true); // directly output binary stream to client
        $req->setPostFields('requestType=HTTP&method=Expressomail.downloadAttachment&' .
            "messageId=$messageId&partId=$partId&getAsJson=false");
        $req->setHeaders(array(
            'Connection: keep-alive',
            'DNT: 1',
            'User-Agent: ' . TineJsonRpc::DEFAULT_USERAGENT,
            'Pragma: no-cache',
            'Cache-Control: no-cache'
        ));
        $req->send(Request::POST);

        // We set our Request with binaryOutput = true. Because of this,
        // it has already outputed all the expected response. So, we return
        // a null here.
        return null;
    }

    /**
     * Finds the file extension.
     *
     * @param $fileName
     * @return The file extension.
     */
    private function getExtension($fileName)
    {
        $dotPos = strrpos($fileName, '.');
        return ($dotPos === false) ? '' : substr($fileName, $dotPos);
    }

    /**
     * Returns the mimetype associated with a file based on file extension.
     *
     * @param $fileName
     * @return The associated mimetype.
     */
    private function getMimeType($fileName)
    {
        $mimeTypes = array(
            '.txt' => 'text/plain',
            '.pdf' => 'application/pdf',
            '.png' => 'image/png',
            '.jpe' => 'image/jpeg',
            '.jpeg' => 'image/jpeg',
            '.jpg' => 'image/jpeg',
            '.gif' => 'image/gif',
            '.bmp' => 'image/bmp',
            '.ico' => 'image/vnd.microsoft.icon',
            '.tiff' => 'image/tiff',
            '.tif' => 'image/tiff',
            '.svg' => 'image/svg+xml',
            '.svgz' => 'image/svg+xml'
        ); // these file extensions will be opened in browser, not downloaded


        $ext = $this->getExtension($fileName);

        if (isset($mimeTypes[$ext])) {
            return $mimeTypes[$ext];
        } else {
            return null;
        }
    }
}
