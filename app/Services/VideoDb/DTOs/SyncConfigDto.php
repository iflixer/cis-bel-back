<?php

namespace App\Services\VideoDb\DTOs;

class SyncConfigDto
{
    public $limit;
    public $offset;
    public $vdbId;
    public $extraParams;
    public $forceImport;
    public $enrichFlags;
    public $useJobs;

    public static function fromArray(array $data)
    {
        $dto = new self();
        $dto->limit = $data['limit'] ?? 100;
        $dto->offset = $data['offset'] ?? 0;
        $dto->vdbId = $data['vdb_id'] ?? null;
        $dto->extraParams = $data['extra_params'] ?? '';
        $dto->forceImport = $data['force_import'] ?? false;
        $dto->enrichFlags = $data['enrich_flags'] ?? [];
        $dto->useJobs = $data['use_jobs'] ?? false;

        return $dto;
    }
}
