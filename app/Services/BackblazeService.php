<?php

namespace App\Services;

use obregonco\B2\Client;

class BackblazeService {
    public $retryDelay = 1;
    protected $client = null;

    public function getClient() {

       if ($this->client === null) {

           $this->client = new Client(env('BACKBLAZE_ACCOUNT_ID'), [

               'keyId' => env('BACKBLAZE_KEY_ID'),

               'applicationKey' => env('BACKBLAZE_APPLICATION_KEY'),
           ]);
       }

       return $this->client;
   }

  public function upload(string $fileName, string $contents) {

        $this->getClient()->upload([

            'BucketName' => env('BACKBLAZE_BUCKET_NAME'),

            'FileName' => $fileName,

            'Body' => $contents,
        ]);
    }
}
