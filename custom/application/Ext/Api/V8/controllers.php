<?php
require 'custom/application/Ext/Api/V8/Controller/GetNoteAttachments.php';
require 'custom/application/Ext/Api/V8/Controller/GetPdfTemplate.php';

use Api\V8\Controller;
use Slim\Container;
return [
	Controller\GetNoteAttachments::class => function(Container $container) {
		return new Controller\GetNoteAttachments();
	},
	Controller\GetPdfTemplate::class => function(Container $container) {
		return new Controller\GetPdfTemplate();
	}
];
