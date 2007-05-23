<?php
/**
 * $Id$
 *
 * The contents of this file are subject to the KnowledgeTree Public
 * License Version 1.1 ("License"); You may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.knowledgetree.com/KPL
 * 
 * Software distributed under the License is distributed on an "AS IS"
 * basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 * 
 * The Original Code is: KnowledgeTree Open Source
 * 
 * The Initial Developer of the Original Code is The Jam Warehouse Software
 * (Pty) Ltd, trading as KnowledgeTree.
 * Portions created by The Jam Warehouse Software (Pty) Ltd are Copyright
 * (C) 2007 The Jam Warehouse Software (Pty) Ltd;
 * All Rights Reserved.
 *
 */

require_once(KT_LIB_DIR . "/plugins/plugin.inc.php");
require_once(KT_LIB_DIR . "/plugins/pluginregistry.inc.php");
require_once('PDFGeneratorAction.php');

 class PDFGeneratorPlugin extends KTPlugin
 {
	var $autoRegister = true;
 	var $sNamespace = 'ktstandard.pdf.plugin';
 	
 	function PDFGeneratorPlugin($sFilename = null) {
        $res = parent::KTPlugin($sFilename);
        $this->sFriendlyName = _kt('PDF Generator Plugin');
        return $res;
    }
    
    function setup() {
        $this->registerAction('documentaction', 'PDFGeneratorAction', 'ktstandard.pdf.generate', $sFilename = null);
    }
 }
$oPluginRegistry =& KTPluginRegistry::getSingleton();
$oPluginRegistry->registerPlugin('PDFGeneratorPlugin', 'ktstandard.pdf.plugin', __FILE__);
?>
