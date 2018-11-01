<?php

namespace App\Http\Controllers\KeyImg;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Services\File\FileService;
use Mockery\Exception;

class KeyImgController extends Controller
{
    /**
     * 打印并高亮函数
     * @param $target
     * @param bool $bool
     */
    function p($target, $bool = true)
    {
        static $i = 0;
        if ($i == 0) {
            header('content-type:text/html;charset=utf-8');
        }
        echo '<pre>';
        print_r($target);
        $i++;
        if ($bool) {
            exit;
        } else {
            echo '<br />';
        }
    }

    /**
     * 展示图片
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function findImg(Request $request)
    {
        $allFile = FileService::getAll();
        $fileNameAll = array_column($allFile, 'file_name');

        $data = Cache::get('dataImg', []);
        $name = '缓存';
        $json = '';
        if (empty($datoa)) {
            $files = Storage::disk('public')->allFiles('/');
            $data = [];
            foreach ($files as $file) {
                $insertData = [
                    'file_info' => 0,
                    'file_note' => 0,
                ];

                $fileUrl = asset('storage/' . basename($file));
                $fileMd5 = md5_file($fileUrl);
                try {
                    $fileInfo = getimagesize($fileUrl);
                } catch (Exception $e) {
                    continue;
                }

                $insertData['file_path'] = $fileUrl;
                $insertData['file_name'] = basename($file);
                $insertData['file_md5'] = $fileMd5;
                if ($fileInfo === false) {
                    continue;
                }
                $insertData['width'] = $fileInfo[0];
                $insertData['height'] = $fileInfo[1];
                $insertData['file_type'] = $fileInfo['mime'];

                if (strpos($file, '.jpg') !== false) {
                    $imgExif = $this->get_img_info(asset('storage/' . basename($file)));

                    $insertData['add_time'] = date('Y-m-d');

                    $fileInfo = [
                        'imgExif' => $imgExif,
                        'getimagesize' => $fileInfo,
                    ];

                    if (empty($imgExif)) {
                        $insertData['file_info'] = json_encode($fileInfo);
                        //插入
                        FileService::add($insertData);
                        continue;
                    }

                    $insertData['latitude'] = $imgExif['latitude'];
                    $insertData['longitude'] = $imgExif['longitude'];
                    $insertData['address'] = $imgExif['address'];
                    $insertData['province'] = $imgExif['province'];
                    $insertData['city'] = $imgExif['city'];
                    $insertData['district'] = $imgExif['district'];
                    $insertData['township'] = $imgExif['township'];
                    $insertData['senic_spot'] = $imgExif['senic_spot'];
                    $insertData['build_time'] = $imgExif['img_time'];


                    $exif = $imgExif['exif'];
                    $width = empty($exif['COMPUTED']['Width']) ? 320 : $exif['COMPUTED']['Width'];
                    $height = empty($exif['COMPUTED']['Height']) ? 240 : $exif['COMPUTED']['Height'];
                    $key = [
                        'FileName' => '文件名',
                        'Make' => '器材品牌',
                        'Model' => '器材',
                        'ExposureTime' => '快门',
                        'FNumber' => '光圈',
                        'FocalLength' => '焦距',
                        'ISOSpeedRatings' => '感光度',
                    ];
                    /*$in = [
                        '文件名' => $exif['FileName'],
                        '器材品牌' => $exif['Make'],
                        '器材' => $exif['Model'],
                        '快门' => $exif['ExposureTime'],
                        '光圈' => $exif['FNumber'],
                        '焦距' => $exif['FocalLength'],
                        '感光度' => $exif['ISOSpeedRatings']
                    ];*/
                    $in = [];
                    foreach ($key as $name => $value) {
                        $val = '';
                        if (isset($exif['IFD0'][$name])) {
                            $val = $exif['IFD0'][$name];
                        }
                        $in[$value] = $val;
                    }
                    list($width, $height) = self::getViewSize($width, $height, 445);
                    $info = [
                        'url' => $file,
                        'in' => $in,
                        'info' => [
                            $in,
                            $imgExif,
                        ],
                        'view' => [
                            'w' => $width,
                            'h' => $height,
                        ]
                    ];
                    if (isset($exif['GPS'])) {
                        $json .= $imgExif['json'];
                        $data[] = $info;
                    }
                }
                $insertData['file_info'] = json_encode($fileInfo);
                //插入
                FileService::add($insertData);
            }
            Cache::add('dataImg', $data, 100);
            $name = '非缓存';
        }
        die('s');
        return view('keyimg/view', [
            'data' => $data,
            'name' => $name,
        ]);
    }

    /**
     * 获取展示宽高
     * @param $oriw
     * @param $orih
     * @param $wishSize
     * @return array
     */
    public static function getViewSize($oriw, $orih, $wishSize)
    {
        if ($oriw <= $wishSize) {
            return [$oriw, $orih];
        } else {
            return [
                $wishSize,
                $orih / ($oriw / $wishSize)
            ];
        }
    }

    /**
     * 读取相片信息
     * @param $img_url
     * @param $gaode_key
     * @return array|bool
     */
    public function get_img_info($img_url, $gaode_key = 'ddb4718923a922a569a1484c59a47ed7')
    {
        $exif = exif_read_data($img_url, 0, true);
        if ($exif === false) {
            return false;
        } else {
            if (!isset($exif['GPS']) || !isset($exif['GPS']['GPSLatitude']) || !isset($exif['GPS']['GPSLongitude'])) {
                return [];
            }
            $latitude = $exif['GPS']['GPSLatitude'];   //纬度
            $longitude = $exif['GPS']['GPSLongitude']; //经度
            $GPSLatitudeRef = $exif['GPS']['GPSLatitudeRef']; //南半球 S 北半球 N
            $GPSLongitudeRef = $exif['GPS']['GPSLongitudeRef']; //东半球 S 西半球 N
            //计算经纬度信息
            $latitude = self::get_gps($latitude, $GPSLatitudeRef);
            $longitude = self::get_gps($longitude, $GPSLongitudeRef);

            /**使用高德地图提供逆向地理编码接口获取定位信息;
             * 需在高德申请key
             * 高德接口地址:http://lbs.amap.com/api/webservice/guide/api/georegeo
             */

            $url = "http://restapi.amap.com/v3/geocode/regeo?key=$gaode_key&location=$longitude,$latitude&poitype=&radius=10000&extensions=all&batch=false&roadlevel=0";
            $res = file_get_contents($url);
            $res = json_decode($res, true);

            if ($res['status'] == 1) {
                $address = $res['regeocode']['formatted_address'];
                $province = $res['regeocode']['addressComponent']['province'];
                $district = $res['regeocode']['addressComponent']['district'];
                $township = $res['regeocode']['addressComponent']['township'];
                $city = $res['regeocode']['addressComponent']['city'];
                $senic_spot = $res['regeocode']['aois'][0]['name'];
            } else {
                $address = $province = $district = $township = $city = $senic_spot ='';
            }

            //图片拍摄时间
            $time = date("Y-m-d H:i:s", $exif['FILE']['FileDateTime']);
            $time = $exif['IFD0']['DateTime'];

            //图片宽高
            $imgsize = getimagesize($img_url);
            $width = $imgsize[0];
            $height = $imgsize[1];

            $json = "{
                name: '深圳',
                icon: 'https://aos-cdn-image.amap.com/sns/ugc/photo/ddef3ad045b743dfa1464e83cefc35b7.jpg',
                position: [$latitude, $longitude]
            },";

            $data = array(
                'json' => $json,//图片拍摄时间
                'img_time' => $time,//图片拍摄时间
                'latitude' => $latitude,//纬度
                'longitude' => $longitude,//经度
                'address' => $address,//详细地址
                'province' => $province,//省份
                'city' => $city,//城市
                'district' => $district,//区
                'township' => $township,//街道
                'senic_spot' => $senic_spot,//景点名称
                'height' => $height,
                'width' => $width,
                'exif' => $exif
            );
            return $data;
        }
    }

    /**
     * 计算经纬度
     * @param $exifCoord
     * @param $banqiu
     * @return float|int
     */
    public function get_gps($exifCoord, $banqiu)
    {
        $degrees = count($exifCoord) > 0 ? self::gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? self::gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? self::gps2Num($exifCoord[2]) : 0;
        $minutes += 60 * ($degrees - floor($degrees));
        $degrees = floor($degrees);
        $seconds += 60 * ($minutes - floor($minutes));
        $minutes = floor($minutes);
        if ($seconds >= 60) {
            $minutes += floor($seconds / 60.0);
            $seconds -= 60 * floor($seconds / 60.0);
        }
        if ($minutes >= 60) {
            $degrees += floor($minutes / 60.0);
            $minutes -= 60 * floor($minutes / 60.0);
        }
        $lng_lat = $degrees + $minutes / 60 + $seconds / 60 / 60;
        if (strtoupper($banqiu) == 'W' || strtoupper($banqiu) == 'S') {
            //如果是南半球 或者 西半球 乘以-1
            $lng_lat = $lng_lat * -1;
        }
        return $lng_lat;
    }

    /**
     * 取得EXIF的內容 分数 转 小数
     * @param $coordPart
     * @return float|int
     */
    public function gps2Num($coordPart)
    {
        $parts = explode('/', $coordPart);
        if (count($parts) <= 0)
            return 0;
        if (count($parts) == 1)
            return $parts[0];
        return floatval($parts[0]) / floatval($parts[1]);
    }
}
