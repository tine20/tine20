<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @copyright  Copyright (c) 2006-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Gbase');

/**
 * Returns the full URL of the current page, based upon env variables
 * 
 * Env variables used:
 * $_SERVER['HTTPS'] = (on|off|)
 * $_SERVER['HTTP_HOST'] = value of the Host: header
 * $_SERVER['SERVER_PORT'] = port number (only used if not http/80,https/443
 * $_SERVER['REQUEST_URI'] = the URI after the method of the HTTP request
 *
 * @return string Current URL
 */
function getCurrentUrl() 
{
  global $_SERVER;

  /**
   * Filter php_self to avoid a security vulnerability.
   */
  $php_request_uri = htmlentities(substr($_SERVER['REQUEST_URI'], 0, strcspn($_SERVER['REQUEST_URI'], "\n\r")), ENT_QUOTES);

  if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
    $protocol = 'https://';
  } else {
    $protocol = 'http://';
  }
  $host = $_SERVER['HTTP_HOST'];
  if ($_SERVER['SERVER_PORT'] != '' &&
     (($protocol == 'http://' && $_SERVER['SERVER_PORT'] != '80') ||
     ($protocol == 'https://' && $_SERVER['SERVER_PORT'] != '443'))) {
    $port = ':' . $_SERVER['SERVER_PORT'];
  } else {
    $port = '';
  }
  return $protocol . $host . $port . $php_request_uri;
}

/**
 * Returns the AuthSub URL which the user must visit to authenticate requests 
 * from this application.
 *
 * Uses getCurrentUrl() to get the next URL which the user will be redirected
 * to after successfully authenticating with the Google service.
 *
 * @return string AuthSub URL
 */
function getAuthSubUrl() 
{
  $next = getCurrentUrl();
  $scope = 'http://www.google.com/base/feeds/';
  $secure = false;
  $session = true;
  return Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure, 
      $session);
}

/**
 * Outputs a request to the user to login to their Google account, including
 * a link to the AuthSub URL.
 * 
 * Uses getAuthSubUrl() to get the URL which the user must visit to authenticate
 */
function requestUserLogin($linkText) 
{
  $authSubUrl = getAuthSubUrl();
  echo "<a href=\"{$authSubUrl}\">{$linkText}</a>"; 
}

/**
 * Returns a HTTP client object with the appropriate headers for communicating
 * with Google using AuthSub authentication.
 *
 * Uses the $_SESSION['sessionToken'] to store the AuthSub session token after
 * it is obtained.  The single use token supplied in the URL when redirected 
 * after the user succesfully authenticated to Google is retrieved from the 
 * $_GET['token'] variable.
 *
 * @return Zend_Http_Client
 */
function getAuthSubHttpClient() 
{
  global $_SESSION, $_GET;
  if (!isset($_SESSION['sessionToken']) && isset($_GET['token'])) {
    $_SESSION['sessionToken'] = 
        Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']);
  } 
  $client = Zend_Gdata_AuthSub::getHttpClient($_SESSION['sessionToken']);
  return $client;
}

/**
 * Returns a HTTP client object with the appropriate headers for communicating
 * with Google using the ClientLogin credentials supplied.
 *
 * @param string $user The username, in e-mail address format, to authenticate
 * @param string $pass The password for the user specified
 * @return Zend_Http_Client
 */
function getClientLoginHttpClient($user, $pass) 
{
  $service = Zend_Gdata_Gbase::AUTH_SERVICE_NAME;

  $client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service);
  return $client;
}

/**
 * Processes loading of this sample code through a web browser.  Uses AuthSub
 * authentication and outputs a list of a user's base items if succesfully 
 * authenticated.
 *
 * @return void
 */
function processPageLoad() 
{
  global $_SESSION, $_GET;
  if (!isset($_SESSION['sessionToken']) && !isset($_GET['token'])) 
  {
    requestUserLogin('Please login to your Google Account.');
  } 
  else 
  {
    startHTML();
    $client = getAuthSubHttpClient();
    $itemUrl = insertItem($client, false);
    updateItem($client, $itemUrl, false);
    listAllMyItems($client);
    deleteItem($client, $itemUrl, true);
    querySnippetFeed();
    endHTML();
  }
}

/**
 * Writes the HTML prologue for the demo.
 *
 * NOTE: We would normally keep the HTML/CSS markup separate from the business
 *       logic above, but have decided to include it here for simplicity of
 *       having a single-file sample.
 */
function startHTML()
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <title>Google Base API API Demo</title>

    <style type="text/css" media="screen">
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: small;
        }
    
        #header {
            background-color: #9cF;
            -moz-border-radius: 5px;
            -webkit-border-radius: 5px;
            padding-left: 5px;
            height: 2.4em;
        }
        
        #header h1 {
            width: 49%;
            display: inline;
            float: left;
            margin: 0;
            padding: 0;
            font-size: 2em;
        }
        
        .clear {
            clear : both;
        }
        
        h2 {
            background-color: #E5ECF9;
            -moz-border-radius: 5px;
            -webkit-border-radius: 5px;
            margin-top: 1em;
            padding-left: 5px;
        }
        
        .error {
            color: red;
        }
                
        #content {
            width: 600px;
            margin: 0 auto;
            padding: 0;
            text-align: left;
        }
    </style>

</head>

<body>

<div id="header">
    <h1>Google Base API Demo</h1>
</div>

<div id="content">
<?php
}

/**
 * Writes the HTML epilogue for this app and exit.
 *
 * @param boolean $displayBackButton (optional) If true, displays a 
 *          link to go back at the bottom of the page. Defaults to false.
 */
function endHTML($displayBackButton = false)
{
?>
</div>
</body>
</html>
<?php
exit();
}

/** 
 * Inserts a Base item into the Customer Items feed
 *
 * @param Zend_Http_Client $client The authenticated client object
 * @return string The URL of the newly created entry
 */
function insertItem($client, $dryRun = false)
{
  echo "<h2>Insert an item</h2>\n";

  $service = new Zend_Gdata_Gbase($client);
  $newEntry = $service->newItemEntry();

  // Add title
  $title = "PHP Developer Handbook";
  $newEntry->title = $service->newTitle(trim($title));

  // Add some content
  $content = "Essential handbook for PHP developers. This is a test item.";
  $newEntry->content = $service->newContent($content);
  $newEntry->content->type = 'text';

  // Define product type
  $itemType = "Products";
  $newEntry->itemType = $itemType;
  $newEntry->itemType->type = 'text';

  // Add item-specific attributes
  $newEntry->addGbaseAttribute('product_type', 'book', 'text');
  $newEntry->addGbaseAttribute('price', '12.99 USD', 'floatUnit');
  $newEntry->addGbaseAttribute('quantity', '10', 'int');
  $newEntry->addGbaseAttribute('weight', '2.2 lbs', 'numberUnit');
  $newEntry->addGbaseAttribute('condition', 'New', 'text');
  $newEntry->addGbaseAttribute('UPC', 'UPC12345', 'text');
  $newEntry->addGbaseAttribute('ISBN', 'ISBN12345', 'text');
  $newEntry->addGbaseAttribute('author', 'John Doe', 'text');
  $newEntry->addGbaseAttribute('edition', 'First Edition', 'text');
  $newEntry->addGbaseAttribute('pages', '253', 'number');
  $newEntry->addGbaseAttribute('publisher', 'My Press', 'text');
  $newEntry->addGbaseAttribute('year', '2007', 'number');
  $newEntry->addGbaseAttribute('label', 'PHP 4', 'text');
  $newEntry->addGbaseAttribute('label', 'development', 'text');
  
  /**
   * If you'd like to use Google Base-hosted item pages and accept Google Checkout, 
   * simply uncomment the following statement to make Google Checkout as a payment option.
   * Don't include the link attribute. If you do, your listing will direct 
   * buyers to your website instead of a Google Base-hosted page that displays the 
   * Google Checkout Buy button.
   *   Note: Google Checkout can be used for products only:
   *         <g:item_type>Products</g:item_type>
   *         Also, make sure that 'price' and 'quantity' attributes are defined.
   */

  $newEntry->addGbaseAttribute('payment_accepted', 'Google Checkout', 'text');

  /**
   * If you're already using Google Checkout on your website, include 
   * 'Google Checkout' as a payment option by including payment_notes attribute.
   * In this case, you should include the link attribute, as users will be 
   * directed to your Google Checkout-integrated website. 
   */

  //$newEntry->addGbaseAttribute('payment_notes', 'Google Checkout', 'text');

  //$link = 'http://www.mysite.com/products/item123.php';
  //$newEntry->link = $service->newLink($link);
  //$newEntry->link->type = 'text/html';

  // Post the item
  $createdEntry = $service->insertGbaseItem($newEntry, $dryRun);
  $itemUrl = $createdEntry->id->text;
  echo "\t<ol><li><b>" . $createdEntry->title->text . "</b><br />\n";
  echo "\t<span>" . $createdEntry->id->text . "</span><br />\n";
  echo "\t<p>" . $createdEntry->content->text . "</p></li></ol>\n";
  echo "\tSuccessfully inserted an item at " . $itemUrl . "<br /><br />\n";

  return $itemUrl;
}

/** 
 * Outputs an HTML unordered list (ul), with each list item representing a
 * Base item in the authenticated user's Customer Items list.  
 *
 * @param Zend_Http_Client $client The authenticated client object
 * @return void
 */
function listAllMyItems($client) 
{
  echo "<h2>List all my items</h2>\n";

  $service = new Zend_Gdata_Gbase($client);
  $feed = $service->getGbaseItemFeed();

  printEntries($feed);
}

/** 
 * Updates a Base item entry. It demonstrates how to access and 
 * update/remove Base attributes
 *
 * @param Zend_Http_Client $client The authenticated client object
 * @return void
 */
function updateItem($client, $itemUrl, $dryRun = false)
{
  echo "<h2>Update my item</h2>\n";
  
  $service = new Zend_Gdata_Gbase($client);
  if ($entry = $service->getGbaseItemEntry($itemUrl)) {
    echo "\t<ol>\n";

    // Update the title
    $oldTitle = $entry->title->text;
    $newTitle = 'PHP Developer Handbook Second Edition';
    $entry->title = $service->newTitle($newTitle);

    // Find <g:price> attribute and update the price
    $baseAttributeArr = $entry->getGbaseAttribute('price');
    if (is_object($baseAttributeArr[0])) {
      $oldPrice = $baseAttributeArr[0]->text;
      $newPrice = '16.99 USD';
      $baseAttributeArr[0]->text = $newPrice;
    }

    // Find <g:edition> attribute and update the edition
    $baseAttributeArr = $entry->getGbaseAttribute('edition');
    if (is_object($baseAttributeArr[0])) {
      $oldEdition = $baseAttributeArr[0]->text;
      $newEdition = 'Second Edition';
      $baseAttributeArr[0]->text = $newEdition;
    }

    // Find <g:pages> attribute and update the number of pages
    $baseAttributeArr = $entry->getGbaseAttribute('pages');
    if (is_object($baseAttributeArr[0])) {
      $oldPages = $baseAttributeArr[0]->text;
      $newPages = '278';
      $baseAttributeArr[0]->text = $newPages;

      // Update the attribute type from 'number' to 'int'
      if ($baseAttributeArr[0]->type == 'number') {
        $newType = 'int';
        $baseAttributeArr[0]->type = $newType;
      }
    }

    // Remove <g:label> attributes and add new attributes
    $baseAttributeArr = $entry->getGbaseAttribute('label');
    foreach ($baseAttributeArr as $note) {
      $entry->removeGbaseAttribute($note);
    }

    $entry->addGbaseAttribute('note', 'PHP 5', 'text');
    $entry->addGbaseAttribute('note', 'Web Programming', 'text');

    try {
      $entry->save($dryRun);
      echo "\t<li><b>" . $entry->title->text . "</b><br />\n";
      echo "\t<span>" . $entry->id->text . "</span><br />\n";
      echo "\t<p>" . $entry->content->text . "</p></li>\n";
      echo "\tSuccessfully updated entry at " . $entry->id->text . "</li><br />\n";
    } catch (Zend_Gdata_App_Exception $e) {
      echo "<div class='error'>ERROR:</div><br />\n";
      var_dump($e);
      return null;
    }

    echo "\t</ol>\n";
  } else {
    echo "\tNo item exists at " . $itemUrl . "<br />\n";
    return null;
  }
}

/** 
 * Deletes a Base item entry
 *
 * @param Zend_Http_Client $client The authenticated client object
 * @param string $itemUrl The URL of the item to be deleted
 * @return void
 */
function deleteItem($client, $itemUrl, $dryRun = false) 
{
  echo "<h2>Delete an item</h2>\n";

  $service = new Zend_Gdata_Gbase($client);
  if ($entry = $service->getGbaseItemEntry($itemUrl)) {
    try {
      echo "\t<ol><li><b>" . $entry->title->text . "</b><br />\n";
      echo "\t<span>" . $entry->id->text . "</span><br />\n";
      echo "\t<p>" . $entry->content->text . "</p></li></ol>\n";
      $entry->delete($dryRun);
      echo "\tSuccessfully deleted entry at " . $itemUrl . "<br /><br />\n";
    } catch (Zend_Gdata_App_Exception $e) {
      echo "<div class='error'>ERROR:</div><br />\n";
      var_dump($e);
      return null;
    }
  } else {
    echo "\tNo items match.<br />\n";
    return null;
  }
}

/** 
 * Executes a query on the Snippets Feed. No authentication is required
 * since the Snippets Feed is public information.
 *
 * @return void
 */
function querySnippetFeed() 
{
  echo "<h2>Execute a query on the snippets feed</h2>\n";

  $service = new Zend_Gdata_Gbase();
  $query = $service->newSnippetQuery();
  $query->setBq('[title:Programming]');
  $query->setOrderBy('modification_time');
  $query->setSortOrder('descending');
  $query->setMaxResults('5');
  $query->setCategory('jobs');
  $feed = $service->getGbaseSnippetFeed($query);
  
  printEntries($feed);
}

/**
 * Prints each entry in a given feed
 *
 * @param feed $feed CustomerItems or Snippets feed to be printed
 * @return void
 */
function printEntries($feed) 
{
  echo "<ol>";
  foreach ($feed->entries as $entry) {
    echo "\t\t<li><b>" . $entry->title->text . "</b><br />\n";
    echo "\t\t<span>" . $entry->id->text . "</span><br />\n";
    echo "\t\t<p>" . $entry->content->text . "</p></li>\n";
  }
  echo "</ol>";
  echo "<br />\n";
}

/**
 * Main logic for running this sample code via the command line or,
 * for AuthSub functionality only, via a web browser.  The output of
 * many of the functions is in HTML format for demonstration purposes,
 * so you may wish to pipe the output to Tidy when running from the 
 * command-line for clearer results.
 *
 * Run without any arguments to get usage information
 */
if (isset($argc) && $argc >= 2) {
  switch ($argv[1]) {
    case 'listAllMyItems':
      if ($argc == 4) { 
        $client = getClientLoginHttpClient($argv[2], $argv[3]);
        listAllMyItems($client);
      } else {
        echo "Usage: php {$argv[0]} {$argv[1]} " .
             "<username> <password>\n";
      }
      break;
    case 'insertItem':
      if ($argc == 5) { 
        if (strtolower($argv[4]) == 'false') {
          $client = getClientLoginHttpClient($argv[2], $argv[3]);
          insertItem($client, false);
        } elseif (strtolower($argv[4]) == 'true') {
          $client = getClientLoginHttpClient($argv[2], $argv[3]);
          insertItem($client, true);
        } else {
          echo "Possible values for <dryRun> are 'true' and 'false'";
        }
      } else {
        echo "Usage: php {$argv[0]} {$argv[1]} " .
             "<username> <password> <dryRun>\n";
      }
      break;
    case 'updateItem':
      if ($argc == 6) {
        if (strtolower($argv[5]) == 'false') {
          $client = getClientLoginHttpClient($argv[2], $argv[3]);
          updateItem($client, $argv[4], false);
        } elseif (strtolower($argv[5]) == 'true') {
          $client = getClientLoginHttpClient($argv[2], $argv[3]);
          updateItem($client, $argv[4], true);
        } else {
          echo "Possible values for <dryRun> are 'true' and 'false'";
        }
      } else {
        echo "Usage: php {$argv[0]} {$argv[1]} " . 
             "<username> <password> <itemUrl> <dryRun>\n";
      }
      break;
    case 'deleteItem':
      if ($argc == 6) {
        if (strtolower($argv[5]) == 'false') {
          $client = getClientLoginHttpClient($argv[2], $argv[3]);
          deleteItem($client, $argv[4], false);
        } elseif (strtolower($argv[5]) == 'true') {
          $client = getClientLoginHttpClient($argv[2], $argv[3]);
          deleteItem($client, $argv[4], true);
        } else {
          echo "Possible values for <dryRun> are 'true' and 'false'";
        }
      } else {
        echo "Usage: php {$argv[0]} {$argv[1]} " . 
             "<username> <password> <itemUrl> <dryRun>\n";
      }
      break;
    case 'querySnippetFeed':
      if ($argc == 2) { 
        querySnippetFeed();
      } else {
        echo "Usage: php {$argv[0]} {$argv[1]} ";
      }
      break;
  } 
} else if (!isset($_SERVER["HTTP_HOST"]))  {
  // running from command line, but action left unspecified
  echo "Usage: php {$argv[0]} <action> [<username>] [<password>] " .
      "[<arg1> <arg2> ...]\n\n";
  echo "Possible action values include:\n" .
       "listAllMyItems\n" . 
       "insertItem\n" . 
       "updateItem\n" .
       "deleteItem\n" .
       "querySnippetFeed\n";
} else {
  // running through web server - demonstrate AuthSub
  processPageLoad();
}

?>
