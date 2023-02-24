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

 $manifest = array(
    'name' => 'SuitePY Service',
    'description' => 'Custom SuiteCRM WebService for SuitePY.',
    'type' => 'module',
    'is_uninstallable' => 'Yes',
    'acceptable_sugar_versions' => array(
        'regex_matches' => array('6\.5\.*'),
    ),
    'acceptable_sugar_flavors' => array('CE'),
    'author' => 'BTACTIC SCCL, Marc Sánchez',
    'version' => '0.1',
    'published_date' => '2018-07-03',
);

$installdefs = array(
    'id'=> 'suitepy_service',
    'copy' => array(
        array(
            'from'=> '<basepath>/custom/service/suitepy',
            'to'=>'custom/service/suitepy'
        ),
    )
);
