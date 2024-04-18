<?php

namespace App\Services;

use GuzzleHttp\Exception\ClientException;
use obregonco\B2\Client;
use Illuminate\Support\Facades\Log;
use obregonco\B2\Access\Capabilities;

class BackblazeService {
    public $retryDelay = 1;
    protected $client = null;

    protected $refreshKeys = false;

    public function getClient() {

       if ($this->client === null) {

            Log::info('Create new client');

            try {

                $this->client = new Client(env('BACKBLAZE_ACCOUNT_ID'), [

                    'keyId' => env('BACKBLAZE_KEY_ID'),

                    'applicationKey' => env('BACKBLAZE_APPLICATION_KEY'),
                ]);

        } catch (ClientException $e) {

            if ($e->hasResponse()) {

                $response = $e->getResponse();

                $statusCode = $response->getStatusCode();
        
                if ($statusCode == 401) {
                    // Логируем и повторяем запрос

                    Log::info('Client api keys are outdated');

                    $this->client = new Client(env('BACKBLAZE_ACCOUNT_ID'), [

                        'keyId' => env('BACKBLAZE_ACCOUNT_ID'),

                        'applicationKey' => env('BACKBLAZE_MASTER_KEY'),
                    ]);
                    // Обновляем ключи, чтобы в следующий раз не возникло такой ошибки

                    Log::info('Call refresh token');

                    $this->refreshToken();

                    return $this->getClient();
                }

            } else {

                error_log('Request failed without a response: ' . $e->getMessage());

                return response()->json(['error' => 'No Response Received'], 503);
            }
        }
    }
       Log::info('Client was returned');
       return $this->client;
   }
   // Загружаем файл в хранилище
   public function uploadFile(string $fileName, string $contents) {

        try {
            Log::info('Try to load file');
            $this->getClient()->upload([

                'BucketName' => env('BACKBLAZE_BUCKET_NAME'),

                'FileName' => $fileName,

                'Body' => $contents,
            ]);

        } catch (ClientException $e) {
            // Если возникнет какая-либо из перечисленных ошибок, попробуем через "'экспоненциальное время"
            if (in_array($e->getCode(), [408, 429, 503])) {

                sleep($this->retryDelay);

                $this->retryDelay *= 2;

                return $this->uploadFile($fileName, $contents);
            }

            else return false;

        } catch (\Exception $e) {
            // Обработка других видов ошибок
            Log::error($e->getMessage());
            // Возвращаем ответ с кодом 500
            http_response_code(500);

            echo "Internal Server Error";

            exit;
        }
    }

    public function refreshToken() {
        
        $name = $_ENV['APP_NAME'];

        $key = $this->getClient()->createKey($name, new Capabilities([

            Capabilities::DELETE_BUCKETS,

            Capabilities::LIST_ALL_BUCKET_NAMES,

            Capabilities::READ_BUCKETS
        ]));

        $keyId = $key->getKeyId();
        
        $applicationKey = $key->getApplicationKey();

        $this->refreshKeysInEnv($keyId, $applicationKey);
    }
    // Обновляем переменные в .env
    // Поскольку обновление ключа будет происходить очень редко(если пользователь случайно удалить его), то мы можем себе позволить такой грубый способ
    // Использование иных способ кажется неэффективным
    private function refreshKeysInEnv($keyId, $applicationKey) {

        $envPath = 'D:\OSPanel\domains\s3-pdf-uploader\.env';

        $envContent = file_get_contents($envPath);

        $envContent = preg_replace("/^BACKBLAZE_KEY_ID=.*/m", "BACKBLAZE_KEY_ID=$keyId", $envContent);

        $envContent = preg_replace("/^BACKBLAZE_APPLICATION_KEY=.*/m", "BACKBLAZE_APPLICATION_KEY=$applicationKey", $envContent);

        file_put_contents($envPath, $envContent);
    }
}
