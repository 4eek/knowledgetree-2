<?php

require_once(KT_LIB_DIR . '/dashboard/dashlet.inc.php');
require_once(KT_LIB_DIR . "/templating/templating.inc.php");
require_once(KT_LIB_DIR . "/dashboard/Notification.inc.php");
require_once(KT_LIB_DIR . "/security/Permission.inc");

// ultra simple skeleton for the admin tutorial
class KTBeta1InfoDashlet extends KTBaseDashlet {
    function is_active($oUser) {
        return true;
    }
    
    function render() {
        $oTemplating =& KTTemplating::getSingleton();
        $oTemplate = $oTemplating->loadTemplate("ktcore/dashlets/beta1info");
        $aTemplateData = array(
        );
        return $oTemplate->render($aTemplateData);
    }
}


class KTNotificationDashlet extends KTBaseDashlet {

    var $oUser;
    
    function is_active($oUser) {
        $this->oUser = $oUser;
        
        return true;
    }
    
    function render() {
        
        $notifications = KTNotification::getList(array("user_id = ?", $this->oUser->getId()));
        $num_notifications = count($notifications);
        
        $_MAX_NOTIFICATIONS = 5;
        
        // FIXME in lieu of pagination, we slice.
        if ($num_notifications > $_MAX_NOTIFICATIONS) {
            $notifications = array_slice($notifications, 0, $_MAX_NOTIFICATIONS);
        }
        
        $oTemplating =& KTTemplating::getSingleton();
        $oTemplate = $oTemplating->loadTemplate("ktcore/dashlets/notifications");
        $aTemplateData = array(
            "notifications" => $notifications,
            "notification_count" => $num_notifications,
            "visible_count" => count($notifications),
        );
        return $oTemplate->render($aTemplateData);
    }
}



// replace the old checked-out docs.
class KTCheckoutDashlet extends KTBaseDashlet {

    var $oUser;
    
    function is_active($oUser) {
        $this->oUser = $oUser;
        return true;
    }
    
    function getDocumentLink($oDocument) {
        return generateControllerLink('viewDocument', 'fDocumentId=' . $oDocument->getId());
    }
    
    function render() {
        
        $checked_out_documents = Document::getList(array("checked_out_user_id = ?", $this->oUser->getId()));
        
        $oTemplating =& KTTemplating::getSingleton();
        $oTemplate = $oTemplating->loadTemplate("ktcore/dashlets/checkedout");
        $aTemplateData = array(
            "context" => $this,
            "documents" => $checked_out_documents,
        );
        return $oTemplate->render($aTemplateData);
    }
}


// replace the old checked-out docs.
class KTIndexerStatusDashlet extends KTBaseDashlet {

    function is_active($oUser) {
        if (Permission::userIsSystemAdministrator($oUser)) {
            return true;
        }
        
        return false;
    }
    
    function render() {	
        require_once(KT_LIB_DIR . '/triggers/triggerregistry.inc.php');
        
        $noTransforms = false;
        
        $oKTTriggerRegistry = KTTriggerRegistry::getSingleton();
        $aTriggers = $oKTTriggerRegistry->getTriggers('content', 'transform');
        $aTriggerSet = array();
        if (empty($aTriggers)) {
            $noTransforms = true;
        } else {
            foreach ($aTriggers as $aTrigger) {
                $sTrigger = $aTrigger[0];
                if ($aTrigger[1]) {
                    require_once($aTrigger[1]);
                }
                $oTrigger = new $sTrigger;
                
                $sCommand = KTUtil::findCommand($oTrigger->commandconfig, $oTrigger->command);
                $sFriendly = $oTrigger->getFriendlyCommand();
                
                // check that we do not specify inactivity.
                if ($sFriendly === false) { $sCommand = null; }
                // otherwise check for friendly name.
                else if (!is_null($sFriendly)) { $sCommand = $sFriendly; }
                else if ($sCommand) { $sCommand = sprintf(_('<strong>command:</strong> %s'), $sCommand); }
                
                // only worry about _broken_ triggers.
                if (!empty($sCommand)) { continue; }
                
                $aTypes = (array) $oTrigger->mimetypes;
                $aTypesStr = array();
                foreach ($aTypes as $sTypeName => $v) {
                    //if ($sTypeName != 'application/octet-stream') { // never use application/octet-stream
                        $aTypesStr[KTMime::getFriendlyNameForString($sTypeName)] = 1;
                    //}
                }
                
                $aTriggerSet[] = array('types' => $aTypesStr, 'command' => $sCommand);
            }
        }
        
        $oTemplating =& KTTemplating::getSingleton();
        $oTemplate = $oTemplating->loadTemplate("ktcore/dashlets/indexer_status");
        $aTemplateData = array(
            "context" => $this,
            "no_transforms" => $noTransforms,
            'transforms' => $aTriggerSet,
        );
        return $oTemplate->render($aTemplateData);
    }
}

?>
