<?php
declare(strict_types=1);

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porth <info@mobger.de>
 *
 *  All rights reserved
 *
 *  This script is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/***************************************************************
 * Extension Manager/Repository config file for ext: "timer"
 *
 * Auto generated by Extension Builder 2019-11-23
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Framework for timer',
    'description' => 'The extension allow the usage of periodical timer for files, content-elements, pages and own content-elements. It can be extended by own timing-definitions i.e. fullmoontimer. The extension provide a viewhelper and a sheduler for the controllking of timemanagement. It contain a simple modell for event-presentation.',
    'category' => 'frontend',
    'author' => 'Dr. Dieter Porth',
    'author_email' => 'info@mobger.de',
    'state' => 'beta',
    'version' => '12.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'php' => '8.1.0-8.2.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
