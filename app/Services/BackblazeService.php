<?php

namespace App\Services;

use obregonco\B2\Client;
use obregonco\B2\Access\Capabilities;
use GuzzleHttp\Exception\ClientException;
class BackblazeService {
    public $retryDelay = 1;
    protected $client;

    public function __construct() {

        $this->client = new Client(env('BACKBLAZE_ACCOUNT_ID'), [

            'keyId' => env('BACKBLAZE_KEY_ID'),

            'applicationKey' => env('BACKBLAZE_APPLICATION_KEY'),
        ]);
    }
    public function upload(string $fileName, string $contents) {

        try {

            $this->client->upload([

                'BucketName' => env('BACKBLAZE_BUCKET_NAME'),
    
                'FileName' => $fileName,
    
                'Body' => $contents,
            ]);
        } catch (ClientException $e) {

            if ($e->getCode() == 401) {

                $this->refreshToken();

                return $this->upload($fileName, $contents);

            }

            elseif (in_array($e->getCode(), [408, 429, 503])) {

                sleep($this->retryDelay);

                $this->retryDelay *= 2;

                return $this->upload($fileName, $contents);
            }

            else return false;
        }
    }

    public function refreshToken() {
        
        $name = $_ENV['APP_NAME'];

        $key = $this->client->createKey($name, new Capabilities([

            Capabilities::DELETE_BUCKETS,

            Capabilities::LIST_ALL_BUCKET_NAMES,

            Capabilities::READ_BUCKETS
        ]));
        
        $keyId = $key->getKeyId();
        
        $applicationKey = $key->getApplicationKey();

        $_ENV['BACKBLAZE_KEY_ID'] = $keyId;

        $_ENV['BACKBLAZE_APPLICATION_KEY'] = $applicationKey;
    }
}
