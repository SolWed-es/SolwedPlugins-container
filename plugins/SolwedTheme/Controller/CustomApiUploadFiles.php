<?php

namespace FacturaScripts\Plugins\SolwedTheme\Controller;

use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Plugins\SolwedTheme\Lib\UploadedFile;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Plugins\SolwedTheme\Lib\Response;

class CustomApiUploadFiles extends ApiController
{
    /** @var Response */
    protected $response;

    protected function runResource(): void
    {
        // make sure we're using our custom Response, not Symfony's
        $this->response = new Response();

        // fix: Symfony Request uses getMethod(), not method()
        if (!in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status'  => 'error',
                    'message' => 'Method not allowed',
                ]);
            return;
        }

        $uploadedFiles = [];

        $files = $this->request->files->get('files') ?? [];

        foreach ($files as $file) {
            // If Symfony gives you an UploadedFile object, convert to array
            if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $file = [
                    'name'     => $file->getClientOriginalName(),
                    'type'     => $file->getClientMimeType(),
                    'tmp_name' => $file->getPathname(),
                    'error'    => $file->getError(),
                    'size'     => $file->getSize(),
                ];
            }

            // Now wrap into your custom class
            $customFile = new \FacturaScripts\Plugins\SolwedTheme\Lib\UploadedFile($file);

            if ($uploadedFile = $this->uploadFile($customFile)) {
                $uploadedFiles[] = $uploadedFile;
            }
        }


        // return response using your custom Response class
        $this->response->json([
            'files' => $uploadedFiles,
        ]);
    }

    private function uploadFile(UploadedFile $uploadFile): ?AttachedFile
    {
        if (false === $uploadFile->isValid()) {
            return null;
        }

        // exclude php files
        if (in_array($uploadFile->getClientMimeType(), ['application/x-php', 'text/x-php'])) {
            return null;
        }

        // check if the file already exists
        $destiny = FS_FOLDER . '/MyFiles/';
        $destinyName = $uploadFile->getClientOriginalName();
        if (file_exists($destiny . $destinyName)) {
            $destinyName = mt_rand(1, 999999) . '_' . $destinyName;
        }

        // move the file to the MyFiles folder
        if ($uploadFile->move($destiny, $destinyName)) {
            $file = new AttachedFile();
            $file->path = $destinyName;
            if ($file->save()) {
                return $file;
            }
        }

        return null;
    }
}
