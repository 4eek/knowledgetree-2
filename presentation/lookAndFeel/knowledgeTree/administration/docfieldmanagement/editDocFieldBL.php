<?php
/**
* BL information for adding a DocField
*
* @author Mukhtar Dharsey
* @date 5 February 2003
* @package presentation.lookAndFeel.knowledgeTree.
*
*/
require_once("../../../../../config/dmsDefaults.php");

if (checkSession()) {
	require_once("$default->fileSystemRoot/lib/visualpatterns/PatternListBox.inc");
	require_once("$default->fileSystemRoot/lib/visualpatterns/PatternEditableListFromQuery.inc");
	require_once("editDocFieldUI.inc");
	require_once("$default->fileSystemRoot/lib/security/permission.inc");
	require_once("$default->fileSystemRoot/lib/documentmanagement/DocumentField.inc");
	require_once("$default->fileSystemRoot/presentation/webpageTemplate.inc");
	require_once("$default->fileSystemRoot/lib/visualpatterns/PatternCustom.inc");	
	require_once("$default->fileSystemRoot/lib/foldermanagement/Folder.inc");
	require_once("$default->fileSystemRoot/presentation/lookAndFeel/knowledgeTree/foldermanagement/folderUI.inc");
	require_once("$default->fileSystemRoot/presentation/Html.inc");
	
	
	$oPatternCustom = & new PatternCustom();		
	
	 if (isset($fForStore)) {
		$oDocField = DocumentField::get($fDocFieldID);
		$oDocField->setName($fDocFieldName);
		$oDocField->setDataType($fDocFieldDataType);
		
		//check if checkbox checked
		if (isset($fDocFieldIsGeneric)) {
			$oDocField->setIsGeneric(true);
		} else {
			$oDocField->setIsGeneric(false);
		}
		//check if checkbox checked
		if (isset($fDocFieldHasLookup)) {
			$oDocField->setHasLookup(true);
		} else {
			$oDocField->setHasLookup(false);
		}
		if ($oDocField->update()) {
				// if successfull print out success message
				$oPatternCustom->setHtml(getEditPageSuccess());
				
		} else {
				// if fail print out fail message
				$oPatternCustom->setHtml(getEditPageFail());
		}
	} else if (isset($fDocFieldID)){		
		
				
		// post back on DocField select from manual edit page	
		$oPatternCustom->setHtml(getEditPage($fDocFieldID));
		$main->setFormAction($_SERVER["PHP_SELF"] . "?fForStore=1");
		
		
	} else {
		// if nothing happens...just reload edit page
		$oPatternCustom->setHtml(getEditPage(null));
		$main->setFormAction($_SERVER["PHP_SELF"]);
			
	}
	//render the page
	$main->setCentralPayload($oPatternCustom);
    $main->setHasRequiredFields(true);
	$main->render();	
}
?>
