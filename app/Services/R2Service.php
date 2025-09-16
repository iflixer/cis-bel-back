<?php

namespace App\Services;
use AsyncAws\S3\S3Client;
use Throwable;

class R2Service
{
    private S3Client $r2client;

    private $bucket;

    public function __construct()
	{
        $this->bucket = 'tpfx-sng';
         $this->r2client = new S3Client([
            'endpoint'          => config('r2.endpoint'),
            'region'            => 'auto',
            'accessKeyId'       => config('r2.key_id'),
            'accessKeySecret'   => config('r2.token'),
            'pathStyleEndpoint' => true,                   // важное для R2 (URL вида /{bucket}/{key})
            // 'retries'        => 3,
            //'debug'          => true,
        ]);
		// $this->r2client = new S3Client([
        //     'version' => 'latest',
        //     'region'  => 'auto',
        //     'endpoint' => 'https://e259c83419213e1913b0a406cb9b1173.r2.cloudflarestorage.com',
        //     'credentials' => [
        //         'key'    => '13a526df08c4f484e46a776affc4e61a',
        //         'secret' => config('r2.token'),
        //     ],
        // ]);
	}

    /**
     * Загрузить файл в R2 по внешнему URL.
     */
    public function uploadFileToStorage(string $filename, string $contentType, $data)
    {
        try {
            $result = $this->r2client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => ltrim($filename, '/'),
                'Body'        => $data,           
                'ACL'         => 'public-read',     
                'ContentType' => $contentType,     
                // 'CacheControl'=> 'public, max-age=31536000',
            ]);
            return $result; // AsyncAws\Result
        } catch (Throwable $e) {
            throw new \RuntimeException('R2 upload error: '.$e->getMessage(), 0, $e);
        } finally {
        }
    }


}
