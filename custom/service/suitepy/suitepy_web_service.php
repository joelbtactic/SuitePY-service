<?php

/**
 * Suite PY is a simple Python client for SuiteCRM API.
 *
 * Copyright (C) 2017-2018 BTACTIC, SCCL
 * Copyright (C) 2017-2018 Marc Sanchez Fauste
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if(!defined('sugarEntry')) {
    define('sugarEntry', true);
}

require_once 'service/v4_1/SugarWebServiceImplv4_1.php';
require_once 'custom/service/suitepy/utils/base64_pdf_generator.php';

class SuitePYWebService extends SugarWebServiceImplv4_1 {

    /**
     * Retrieve PDF Template for a given module record.
     *
     * @param String $session -- Session ID returned by a previous call to login.
     * @param String $template_id -- Template ID used to generate PDF.
     * @param String $bean_module -- Module name of the bean that will be used to populate PDF.
     * @param String $bean_id -- ID of the bean record.
     * @return Array -- Array String 'filename' -- The name of the file
     *                        Binary 'file' -- The binary content of the file in base64.
     *                        String 'error' -- Error if any
     * @exception 'SoapFault' -- The SOAP error, if any
     */ 
    function get_pdf_template($session, $template_id, $bean_module, $bean_id) {
        $error = new SoapError();
        if (!self::$helperObject->checkSessionAndModuleAccess($session, 'invalid_session', $bean_module, 'read', 'no_access', $error)) {
            $GLOBALS['log']->error('End: SugarWebServiceImpl->get_pdf_template - FAILED on checkSessionAndModuleAccess');
            return;
        }
        try {
            $bean = BeanFactory::getBean($bean_module, $bean_id);
            $pdf_template = Base64PDFGenerator::get_base64_pdf($template_id, $bean);
            $filename = empty($bean->name) ? 'document' : $bean->name;
            return array(
                'filename' => str_replace(" ", "_", $filename) . '.pdf',
                'file' => $pdf_template,
                'error' => '',
            );
        } catch (Exception $e) {
            return array(
                'filename' => '',
                'file' => '',
                'error' => $e->getMessage(),
            );
        }
    }

}

