<?

/**
 *
 * Demonstrates how to create an anonymous session.
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

require_once('../ktwsapi.inc.php');

$ktapi = new KTWSAPI(KTWebService_WSDL);

$response = $ktapi->start_anonymous_session();
if (PEAR::isError($response))
{
	print $response->getMessage();
	exit;
}

// do something

$root=$ktapi->get_root_folder();

// when done, logout


$ktapi->logout();
 
?>