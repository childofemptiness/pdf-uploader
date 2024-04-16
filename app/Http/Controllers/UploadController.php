<?php

namespace App\Http\Controllers;

use App\Services\BackblazeService;
use Illuminate\Http\Request;

class UploadController extends Controller {
    protected $backblazeService;
    public function __construct(BackblazeService $backblazeService) {

        $this->backblazeService = $backblazeService;
    }

    public function upload(Request $request) {

        if ($request->hasFile('pdf')) {

            $file = $request->file('pdf');

            $fileName = $file->getClientOriginalName();

            $contents = file_get_contents($file->getRealPath());

            if ($file->getClientMimeType() == 'application/pdf') {

                if ($file->getSize() <= env('BACKBLAZE_FILE_MAXSIZE')) {

                    $this->backblazeService->upload($fileName, $contents);
                }

                else return response()->json(['message' => 'Размер файла слишком большой'], 422);

            }

            else return response()->json(['message' => 'MIME-тип файла не соответствует pdf файлу'], 422);
        }

        return response()->json(['message' => 'No file provided'], 400);
    }
}
