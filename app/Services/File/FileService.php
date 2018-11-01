<?php

namespace App\Services\File;

use App\Services\Service;
use App\Models\File\File;

class FileService extends Service
{
    /**
     * 添加文件
     * @param array $data
     * @return bool
     */
    public static function add(array $data)
    {
        return (new File())->add($data);
    }

    public static function getAll()
    {
        return (new File())->getAll()->toArray();
    }
}
