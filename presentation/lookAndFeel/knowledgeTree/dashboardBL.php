<?php

// main library routines and defaults
require_once("../../../config/dmsDefaults.php");
require_once("$default->fileSystemRoot/lib/subscriptions/SubscriptionManager.inc");
require_once("$default->fileSystemRoot/lib/web/WebDocument.inc");
require_once("$default->fileSystemRoot/lib/visualpatterns/PatternCustom.inc");
require_once("$default->fileSystemRoot/lib/links/link.inc");
require_once("$default->uiDirectory/dashboardUI.inc");

/**
 * $Id$
 *  
 * Main dashboard page -- This page is presented to the user after login.
 * It contains a high level overview of the users subscriptions, checked out 
 * document, pending approval routing documents, etc. 
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @version $Revision$
 * @author Michael Joseph <michael@jamwarehouse.com>, Jam Warehouse (Pty) Ltd, South Africa
 * @package presentation
 */

// -------------------------------
// page start
// -------------------------------

/**
 * Retrieves the collaboration documents that the current user has pending
 *
 * @param integer the user to retrieve pending collaboration documents for
 */
function getPendingCollaborationDocuments($iUserID) {
    // TODO: move this to a more logical class/file
    global $default;
    $sQuery = "SELECT document_id FROM $default->owl_folders_user_roles_table WHERE active=1 AND user_id=" . $_SESSION["userID"];
    $aDocumentList = array();
    $sql = $default->db;
    if ($sql->query($sQuery)) {
        while ($sql->next_record()) {
            $aDocumentList[] = & Document::get($sql->f("document_id"));
        }
    }
    return $aDocumentList;
}

/**
 * Retrieves the web documents that the current user has pending
 *
 * @param integer the user to retrieve pending web documents for
 */
function getPendingWebDocuments($iUserID) {
    // TODO: move this to a more logical class/file
    global $default;
    $sQuery = "SELECT wd.id FROM web_documents wd " . 
              "INNER JOIN web_sites ws ON wd.web_site_id = ws.id " .
              "WHERE ws.web_master_id=$iUserID AND wd.status_id=1";
    $aDocumentList = array();
    $sql = $default->db;
    if ($sql->query($sQuery)) {
        while ($sql->next_record()) {
            $aDocumentList[] = & WebDocument::get($sql->f("id"));
        }
    }
    return $aDocumentList;
}

if (checkSession()) {
    // include the page template (with navbar)
    require_once("$default->fileSystemRoot/presentation/webpageTemplate.inc");
    
    // instantiate my content pattern    
    $oContent = new PatternCustom();
    
    // retrieve collaboration pending documents for this user
    $aPendingDocumentList = getPendingCollaborationDocuments($_SESSION["userID"]);
    // retrieve checked out documents for this user                         
    $aCheckedOutDocumentList = Document::getList("checked_out_user_id=" . $_SESSION["userID"]);

    // retrieve subscription alerts for this user
    $aSubscriptionAlertList = SubscriptionManager::listSubscriptionAlerts($_SESSION["userID"]);
    
    // retrieve quicklinks
    $aQuickLinks = Link::getList("ORDER BY rank");
    
    // retrieve pending web documents
    $aPendingWebDocuments = getPendingWebDocuments($_SESSION["userID"]);
    
    // generate the html
    $oContent->setHtml(renderPage($aPendingDocumentList, $aCheckedOutDocumentList, $aSubscriptionAlertList, $aQuickLinks, $aPendingWebDocuments));
    
    // display
    $main->setCentralPayload($oContent);
    $main->render();
}
?>

