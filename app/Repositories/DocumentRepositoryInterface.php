<?php

namespace App\Repositories;

use App\Models\DocumentFile;
use App\Models\DocumentFolder;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRepositoryInterface
{
    public function getFolderContents(Facility $facility, ?DocumentFolder $folder, array $options = []): array;
    
    public function createFolder(Facility $facility, ?DocumentFolder $parent, string $name, int $userId): DocumentFolder;
    
    public function updateFolder(DocumentFolder $folder, array $data): DocumentFolder;
    
    public function deleteFolder(DocumentFolder $folder): bool;
    
    public function createFile(array $data): DocumentFile;
    
    public function updateFile(DocumentFile $file, array $data): DocumentFile;
    
    public function deleteFile(DocumentFile $file): bool;
    
    public function getAvailableFileTypes(Facility $facility): array;
}