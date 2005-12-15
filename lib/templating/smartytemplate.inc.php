<?php
/**
 * $Id$
 *
 * Smarty Template implementation
 *
 * Copyright (c) 2005 Jam Warehouse http://www.jamwarehouse.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version $Revision$
 * @author Neil Blakey-Milner, Jam Warehouse (Pty) Ltd, South Africa
 */

require_once(KT_LIB_DIR . "/templating/template.inc.php");
require_once(KT_DIR . "/thirdparty/Smarty/Smarty.class.php");

class KTSmartyTemplate extends KTTemplate {
    function KTSmartyTemplate ($sPath) {
        $this->KTTemplate($sPath);
    }

    function render($aDict = null) {
        $smarty = new Smarty;
        $smarty->compile_dir = "/tmp";
        foreach (array(KT_DIR . '/var/tmp', '/tmp') as $sPath) {
            if (is_writeable($sPath)) {
                $smarty->compile_dir = $sPath;
                break;
            }
        }
        if (is_array($aDict)) {
            $iLen = count($aDict);
            $aKeys = array_keys($aDict);
            for ($i = 0; $i < $iLen; $i++) {
                $sKey = $aKeys[$i];
                $smarty->assign_by_ref($sKey, $aDict[$sKey]);
            }
        }
        if (is_array($this->aDict)) {
            $iLen = count($this->aDict);
            $aKeys = array_keys($this->aDict);
            for ($i = 0; $i < $iLen; $i++) {
                $sKey = $aKeys[$i];
                $smarty->assign_by_ref($sKey, $this->aDict[$sKey]);
            }            
        }
        $KTConfig =& KTConfig::getSingleton();
        
        // needed for a very, very few places.
        $isSSL = $KTConfig->get("KnowledgeTree/sslEnabled");
        $hostname = $KTConfig->get("KnowledgeTree/serverName");
        $absroot = 'http';
        $absroot .= ($isSSL) ? 's://' : '://';
        $absroot .= $hostname;
        $absroot .= $KTConfig->get("KnowledgeTree/rootUrl");
        
        $smarty->assign("config", $KTConfig);
        $smarty->assign("rootUrl", $KTConfig->get("KnowledgeTree/rootUrl"));
        $smarty->assign("absoluteRootUrl", $absroot);
        $smarty->caching = false;
        $smarty->register_function('entity_select', array('KTSmartyTemplate', 'entity_select'));
        $smarty->register_function('boolean_checkbox', array('KTSmartyTemplate', 'boolean_checkbox'));
        $smarty->register_function('entity_checkboxes', array('KTSmartyTemplate', 'entity_checkboxes'));
        $smarty->register_function('entity_radios', array('KTSmartyTemplate', 'entity_radios'));
        $smarty->register_block('i18n', array('KTSmartyTemplate', 'i18n_block'), false);
        return $smarty->fetch($this->sPath);
    }

    function _i18n_get_args($arr, $var) {
        if (!is_array($arr)) {
            $arr = array();
        }
        if (substr($var[0], 0, 4) == "arg_") {
            $arr['#' . substr($var[0], 4) . '#'] = $var[1];
        }
        return $arr;
    }

    function i18n_block($params, $content, &$smarty, &$repeat) {
        if (empty($content)) {
            return;
        }
        if (!empty($params)) {
            $flattened = array_map(null, array_keys($params), array_values($params));
            $replacements = array_reduce($flattened, array('KTSmartyTemplate', '_i18n_get_args'));
        } else {
            $replacements = array();
        }
        $sDomain = KTUtil::arrayGet($params, 'i18n_domain');
        if (empty($sDomain)) {
            $sDomain = $smarty->get_template_vars('i18n_domain');
        }
        if (empty($sDomain)) {
            $sDomain = 'knowledgeTree';
        }
        $oRegistry =& KTi18nRegistry::getSingleton();
        $oi18n = $oRegistry->geti18n($sDomain);
        $content = trim($content);
        $content = $oi18n->gettext($content);
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    function entity_select ($params, &$smarty) {
        require_once $smarty->_get_plugin_filepath('function', 'html_options');

        $entities = KTUtil::arrayGet($params, 'entities', array());
        /*
        if (is_null($entities)) {
            $smarty->trigger_error("assign: missing 'entities' parameter");
            return;
        }
        */

        $method = KTUtil::arrayGet($params, 'method', 'getName');
        $none = KTUtil::arrayGet($params, 'none');

        $params['values'] = array();
        $params['output'] = array();
        if ($none) {
            $params['values'][] = '';
            $params['output'][] = 'None';
        }
        foreach ($entities as $oEntity) {
            $params['values'][] = $oEntity->getId();
            $params['output'][] = call_user_func(array(&$oEntity, $method));
        }
        unset($params['entities']);

        return smarty_function_html_options($params, $smarty);
    }

    function boolean_checkbox ($params, &$smarty) {
        $name = KTUtil::arrayGet($params, 'name');
        if (empty($name)) {
            $smarty->trigger_error("assign: missing 'name' parameter");
            return;
        }
        $bool = KTUtil::arrayGet($params, 'bool');
        if (is_null($bool)) {
            $smarty->trigger_error("assign: missing 'bool' parameter");
            return;
        }
        $value = KTUtil::arrayGet($params, 'value', 1);

        $label = KTUtil::arrayGet($params, 'label');

        if ($bool) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }

        $ret = sprintf('<input type="checkbox" name="%s" value="%s"%s />', $name, $value, $checked);
        if ($label) {
            $ret = sprintf('<label>%s%s</label>', $ret, $label);
        }
        return $ret;
    }

    function entity_checkboxes($params, &$smarty) {
        require_once $smarty->_get_plugin_filepath('function', 'html_checkboxes');

        $entities = KTUtil::arrayGet($params, 'entities');
        if (is_null($entities)) {
            $smarty->trigger_error("assign: missing 'entities' parameter");
            return;
        }

        $idmethod = KTUtil::arrayGet($params, 'idmethod', 'getId');
        $method = KTUtil::arrayGet($params, 'method', 'getName');

        $params['values'] = array();
        $params['output'] = array();
        foreach ($entities as $oEntity) {
            $params['values'][] = call_user_func(array(&$oEntity, $idmethod));
            if ($method != "none") {
                $params['output'][] = call_user_func(array(&$oEntity, $method));
            } else {
                $params['output'][] = null;
            }
        }
        unset($params['entities']);
        unset($params['idmethod']);
        unset($params['method']);

        return smarty_function_html_checkboxes($params, $smarty);
    }

    function entity_radios($params, &$smarty) {
        require_once $smarty->_get_plugin_filepath('function', 'html_radios');

        $entities = KTUtil::arrayGet($params, 'entities');
        if (is_null($entities)) {
            $smarty->trigger_error("assign: missing 'entities' parameter");
            return;
        }

        $idmethod = KTUtil::arrayGet($params, 'idmethod', 'getId');
        $method = KTUtil::arrayGet($params, 'method', 'getName');

        $params['values'] = array();
        $params['output'] = array();
        foreach ($entities as $oEntity) {
            $params['values'][] = call_user_func(array(&$oEntity, $idmethod));
            $params['output'][] = call_user_func(array(&$oEntity, $method));
        }
        unset($params['entities']);
        unset($params['idmethod']);
        unset($params['method']);

        return smarty_function_html_radios($params, $smarty);
    }
}

?>
