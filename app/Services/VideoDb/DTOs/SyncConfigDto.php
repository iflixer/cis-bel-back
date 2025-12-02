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
    public $sortDirection = 'created';
    public $maxRecords = null;

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
        $dto->sortDirection = $data['sort_direction'] ?? 'created';
        $dto->maxRecords = isset($data['max_records']) ? (int) $data['max_records'] : null;

        return $dto;
    }

    public function toArray()
    {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
            'vdb_id' => $this->vdbId,
            'extra_params' => $this->extraParams,
            'force_import' => $this->forceImport,
            'enrich_flags' => $this->enrichFlags,
            'use_jobs' => $this->useJobs,
            'sort_direction' => $this->sortDirection,
            'max_records' => $this->maxRecords,
        ];
    }
}
