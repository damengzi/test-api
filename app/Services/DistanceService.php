<?php

namespace App\Services;
use Illuminate\Support\Facades\Redis;
use Config;
use Log;
use App\Models\OrderRedressLog;
use Mockery\Exception;

class DistanceService
{
    //获取两个经纬度之间的距离
    public static function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367000; //approximate radius of earth in meters

        /*
        Convert these degrees to radians
        to work with the formula
        */

        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;

        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;

        /*
        Using the
        Haversine formula

        http://en.wikipedia.org/wiki/Haversine_formula

        calculate the distance
        */

        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }



    //$speed. 单位米每小时
    /*
       {
       'lo':124.123456,
       'la':142.234234,
       't':14043434344
       }
   */
    static public function calLowSpeedTime($points, $speed)
    {
        $speed = $speed / 3600;

        $lastPoint = null;
        $lowSpeedTime = 0;
        foreach ($points as $point) {
            if (empty($lastPoint)) {
                $lastPoint = $point;
                continue;
            }

            //计算低速时长
            $tmpspeed = self::getDistance($lastPoint['la'], $lastPoint['lo'], $point['la'], $point['lo']);
            if ($tmpspeed < $speed * ($point['t'] - $lastPoint['t'])) {
                $lowSpeedTime = $lowSpeedTime + $point['t'] - $lastPoint['t'];
            }
            $lastPoint = $point;
        }

        return $lowSpeedTime;
    }


    //获取一串点在某个时间段内的总行程
    /*
       {
       'lo':124.123456,
       'la':142.234234,
       't':14043434344
       }
   */
    static public function getInstanceOfTimeSection($points, $beginTime, $endTime)
    {
        $lastPoint = null;
        $distance = 0;
        foreach ($points as $point) {
            if (empty($lastPoint)) {
                $lastPoint = $point;
                continue;
            }

            if ($point['t'] > $beginTime && $point['t'] <= $endTime) {
                $distance += self::getDistance($lastPoint['la'], $lastPoint['lo'], $point['la'], $point['lo']);
            }

            $lastPoint = $point;
        }

        return $distance;
    }

    static public function getDistanceOfPoints($points)
    {
        $lastPoint = null;
        $distance = 0;
        foreach ($points as $point) {
            if (empty($lastPoint)) {
                $lastPoint = $point;
                continue;
            }

            $distance += self::getDistance($lastPoint['la'], $lastPoint['lo'], $point['la'], $point['lo']);

            $lastPoint = $point;
        }

        return $distance;
    }


    //删除路线中的错误点
    static public function delErrorPoints($points)
    {
        //删除规则
        $sucPoints = [];
        foreach ($points as $point) {
            //首个点直接ok
            if (empty($sucPoints)) {
                $sucPoints[] = $point;
                continue;
            }

            //距离上个点小于150km／h 也就是 42m/s添加到列表中
            $lastPoint = $sucPoints[count($sucPoints) - 1];
            $distance = self::getDistance($lastPoint['la'], $lastPoint['lo'], $point['la'], $point['lo']);
            $t = $point['t'] - $lastPoint['t'];

            //小于42m/s
            if ($distance < 42 * $t) {
                $sucPoints[] = $point;
            }
        }

        return $sucPoints;
    }

    /**
     * @desc 获取订单的丢单距离
     * @param  $order
     */
    public static function getRedressDistance($order)
    {
        //记录丢点的经纬度数组
        $points = json_decode($order['points'], true);
        //测试起点、终点、每个点之间的距离差距
        //实际起点和points点第一个距离
        $redressPoint = [];
        $spacing = DistanceService::getDistance($order['realBeginLatitude'], $order['realBeginLongitude'], $points[0]['la'], $points[0]['lo']);
        if ($spacing > OrderRedressLog::SPACING) {
            $redressPoint[] = ['start' => ['la' => $order['realBeginLatitude'], 'lo' => $order['realBeginLongitude']], 'end' => ['la' => $points[0]['la'], 'lo' => $points[0]['lo']]];
        }

        $len = count($points);
        for ($i = 1; $i < $len; $i++) {
            $spacing = DistanceService::getDistance($points[$i]['la'], $points[$i]['lo'], $points[$i - 1]['la'], $points[$i - 1]['lo']);
            if ($spacing > OrderRedressLog::SPACING) {
                $redressPoint[] = ['start' => ['la' => $points[$i]['la'], 'lo' => $points[$i]['lo']], 'end' => ['la' => $points[$i - 1]['la'], 'lo' => $points[$i - 1]['lo']]];
            }
        }

        $spacing = DistanceService::getDistance($order['realEndLatitude'], $order['realEndLongitude'], $points[$len - 1]['la'], $points[$len - 1]['lo']);
        if ($spacing > OrderRedressLog::SPACING) {
            $redressPoint[] = ['start' => ['la' => $order['realEndLatitude'], 'lo' => $order['realEndLongitude']], 'end' => ['la' => $points[$len - 1]['la'], 'lo' => $points[$len - 1]['lo']]];
        }

        \Log::info("过滤后的点", ['data' => $redressPoint]);

        if (empty($redressPoint)) {
            return false;
        }

        \Log::info("超过范围的所有点的经纬度", ['points' => $redressPoint]);

        //获取点之间的轨迹距离
        foreach ($redressPoint as $item) {
            $distance[] = self::getBaiDuDistance($item);//调用百度
            if ($distance === false) {
                $distance[] = self::getGaoDeDistance($item);
                if ($distance === false) {
                    $distance[] = self::getDistance($item['start']['la'], $item['start']['lo'], $item['end']['la'], $item['end']['lo']);
                }
            }
        }

        \Log::info("丢失距离为：", ['data' => $distance]);

        $rtDistance = array_sum($distance);
        return $rtDistance;
    }

    public static function getBaiDuDistance($item)
    {
        $bdAk = Config::get('common.bdAk');
        $destination = $item['end']['la'] . ',' . $item['end']['lo'];
        $origin = $item['start']['la'] . ',' . $item['start']['lo'];
        try {
            $url = 'http://api.map.baidu.com/direction/v2/driving?origin=' . $origin . '&destination=' . $destination . '&ak=' . $bdAk;
            $distance = CommonService::curlRequest($url, 'get', [], 1);
            if (!$distance) {//请求接口未响应
                \Log::info("百度地图请求超时：", ['url' => $url, 'result' => $distance]);
                //接口请求超时，直接返回数据距离和时间都为0
                $distance = ['status' => 0, 'info' => 'amap api timed out'];
            }
        } catch (Exception $e) {//调用接口出错
            Log::info("百度地图调用失败:", ['url' => $url]);

            $distance = ['status' => 0, 'distance' => $distance];
        }

        $distance = json_decode($distance, true);
        \Log::info('百度地图返回数据', ['info' => $distance]);
        $status = array_get($distance, 'status', '');
        $rtDistance = 0;
        //判断接口调用成功与否 status 0 成功 1：服务内部错误 2：参数无效 7：无返回结果
        if ($status != 0) {
            Log::info("amap api failed:", ['url' => $url, 'result' => $distance]);
            return false;
        }

        $rtDistance = $distance['result']['routes'][0]['distance'];

        return $rtDistance;
    }

    public static function getGaoDeDistance($item)
    {
        $gdKey = Config::get("common.gdKey");
        $destination = $item['end']['la'] . ',' . $item['end']['lo'];
        $origin = $item['start']['la'] . ',' . $item['start']['lo'];
        $url = "http://restapi.amap.com/v3/distance?origins=$origin&destination=$destination&key=$gdKey";

        try {
            $distance = CommonService::curlRequest($url, 'get', [], 1);
            if (!$distance) {
                \Log::info("高德地图请求超时：", ['url' => $url, 'result' => $distance]);
                //接口请求超时，直接返回数据距离和时间都为0
                $distance = ['status' => 0, 'info' => 'amap api timed out'];
            }
        } catch (Exception $e) {
            Log::info("高德地图调用失败:", ['url' => $url]);

            $distance = ['status' => 0, 'distance' => $distance];
        }
        $distance = json_decode($distance, true);
        $status = array_get($distance, 'status', '');
        $rtDistance = 0;
        //判断接口调用成功与否 status 0 成功 1：服务内部错误 2：参数无效 7：无返回结果
        if ($status != 1) {
            Log::info("amap api failed:", ['url' => $url, 'result' => $distance]);
            return false;
        }
        $rtDistance = $distance['results']['result']['distance'];
        return $rtDistance;
    }

}




