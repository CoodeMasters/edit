<?php

namespace App\Services;

use App\Traits\FileManagerTrait;

class SectionService
{
    use FileManagerTrait;

    public function getProcessedData(object $request): array
    {


        return [
            'state_id' => $request['state_id'],
            'name' => $request['title'],
        
        ];
    }



}
