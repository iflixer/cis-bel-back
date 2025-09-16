<?php

namespace App\Services;
use AsyncAws\S3\S3Client;
use AsyncAws\Core\Exception\Http\ClientException;
use AsyncAws\S3\Result\PutObjectOutput;

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
    public function uploadFileToStorage(string $filename, string $contentType, $data): PutObjectOutput   
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


    public function getFileFromStorage(string $filename): array
    {
        $key = ltrim($filename, '/');

        try {
            $res = $this->r2client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                // опционально: 'Range' => 'bytes=0-1048575', // если нужен частичный скач
            ]);

            // тело (строкой). Можно также получить поток: $res->getBody();
            $body = $res->getBody()->getContentAsString();

            return [
                'ok'            => true,
                'key'           => $key,
                'body'          => $body,
                'content_type'  => $res->getContentType(),
                'content_length'=> $res->getContentLength(),
                'etag'          => $res->getETag(),
                'last_modified' => $res->getLastModified(), // \DateTimeImmutable|null
            ];
        } catch (ClientException $e) {
            // например, нет такого ключа (404) или нет доступа
            return [
                'ok'      => false,
                'key'     => $key,
                'status'  => $e->getCode(),   // 404, 403 и т.п.
                'error'   => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            return [
                'ok'    => false,
                'key'   => $key,
                'status'=> 0,
                'error' => $e->getMessage(),
            ];
        }
    }

}
