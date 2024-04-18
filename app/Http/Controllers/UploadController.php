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

        if (!$request->hasFile('pdf')) {

            return response()->json(['message' => 'No file provided'], 400);
        }
    
        $file = $request->file('pdf');

        $fileName = $file->getClientOriginalName();
    
        if ($file->getClientMimeType() != 'application/pdf') {

            return response()->json(['message' => 'MIME-тип файла не соответствует pdf файлу'], 422);
        }
    
        if ($file->getSize() > env('BACKBLAZE_FILE_MAXSIZE')) {

            return response()->json(['message' => 'Размер файла слишком большой'], 422);
        }
    
        $contents = file_get_contents($file->getRealPath());
        
        $this->backblazeService->upload($fileName, $contents);
        
        return response()->json(['message' => 'File uploaded successfully']);
    }  
}
