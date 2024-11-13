<?php
namespace Api\V8\Controller;

use Slim\Http\Response;
use Slim\Http\Request;
require_once 'custom/application/Ext/Api/V8/Controller/utils/base64_pdf_generator.php';
class GetPdfTemplate extends BaseController {
    public function getPdfTemplate(Request $request, Response $response, array $args) {
        try {
            $bean_module = $args['bean_module'];
            $bean_id = $args['bean_id'];
            $template_id = $args['template_id'];
            $bean = \BeanFactory::getBean($bean_module, $bean_id);
            $pdf_template = \Base64PDFGenerator::get_base64_pdf($template_id, $bean);
            $filename = empty($bean->name) ? 'document' : $bean->name;
            $result_message = array(
                'filename' => str_replace(" ", "_", $filename) . '.pdf',
                'file' => $pdf_template,
                'error' => '',
            );
            return $this->generateResponse($response, $result_message, 200);

        } catch (\Exception $e) {
            return $this->generateErrorResponse($response, $e, 400);
        }

    }
}
