<?php

namespace Api\V8\Controller;

use Slim\Http\Response;
use Slim\Http\Request;

class GetNoteAttachments extends BaseController
{
    public function getNoteAttachments(Request $request, Response $response, array $args)
    {
        try {
            $id = $args['id'];
            $noteBean = \BeanFactory::getBean('Notes', $id);
            $attachmentPath = 'upload/' . $id; // Assuming the attachment file is in the upload folder

            if (file_exists($attachmentPath)) {
                $attachmentContent = file_get_contents($attachmentPath);
                $base64Data = base64_encode($attachmentContent);
                $result_message = array(
                    'note_attachment' => array(
                        'id' => $noteBean->id,
                        'filename' => $noteBean->filename,
                        'file' => $base64Data
                    )
                );

                return $this->generateResponse($response, $result_message, 200);
            } else {
                return $this->generateErrorResponse($response, new \Exception('Attachment not found'), 404);
            }
        } catch (\Exception $e) {
            return $this->generateErrorResponse($response, $e, 400);
        }
    }
}
