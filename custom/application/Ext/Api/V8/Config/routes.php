<?php
$app->get('/getNoteAttach/{id}', 'Api\V8\Controller\GetNoteAttachments:getNoteAttachments');
$app->get('/getPdfTemplate/{bean_module}/{bean_id}/{template_id}', 'Api\V8\Controller\GetPdfTemplate:getPdfTemplate');
