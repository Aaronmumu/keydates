<?php

namespace App\Models\File;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'file';
    protected $primaryKey = 'file_id';
    public $timestamps = false;

    /**
     * 添加
     * @param array $data
     * @return bool
     */
    public function add(array $data)
    {
        return DB::table($this->table)->insert($data);
    }

    /**
     * 获取全部
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getAll()
    {
        return $this->all();
    }

    /**
     * 查询
     * @param array $condition
     * @param string $type
     * @param int $pageSize
     * @param int $page
     * @param string $orderBy
     * @param string $groupBy
     */
    public static function getByCondition($condition = [], $type = '*', $pageSize = 0, $page = 1, $orderBy = '', $groupBy = '')
    {
        $users = DB::select('select * from users where active = ?', [1]);
        DB::insert('insert into users (id, name) values (?, ?)', [1, '学院君']);
        $affected = DB::update('update users set votes = 100 where name = ?', ['学院君']);
        $deleted = DB::delete('delete from users');
        DB::statement('drop table users');
    }

    /*public function readCountry()//查
    {
        return $this->all();
    }

    public function oneCountry($data, $arr)//单条查询
    {
            return $this->where($data, $arr)->get()->toArray();
    }
    public function delCountry($data)//删
    {
            $country = $this->where($data);
        return $country->delete();
    }
    public function updCountry($data, $list, $arr)//改
    {
            $country = $this->where($data, $list);
        return $country->update($arr);
    }
    public function addCountry($data)//增
    {
            return DB::table('country')->insert($data);
    }*/

}
