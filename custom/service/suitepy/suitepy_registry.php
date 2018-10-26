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

require_once 'service/v4_1/registry.php';

class RegistrySuitePY extends registry_v4_1
{
    protected function registerFunction()
    {
        parent::registerFunction();
        $this->serviceClass->registerFunction(
            'get_pdf_template',
            array(
                'session' => 'xsd:string',
                'template_id' => 'xsd:string',
                'bean_module' => 'xsd:string',
                'bean_id' => 'xsd:string',
            ),
            array(
                'return' => 'tns:pdf_template',
            )
        );
    }

    protected function registerTypes()
    {
        parent::registerTypes();
        $this->serviceClass->registerType(
            'pdf_template',
            'complexType',
            'array',
            '',
            'SOAP-ENC:Array',
            array(),
            array(
                array(
                    'ref'=>'SOAP-ENC:arrayType',
                    'wsdl:arrayType'=>'xsd:string[]'
                )
            ),
            'xsd:string'
        );
    }
}
