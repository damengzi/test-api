<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Config;
use DB;
use Log;

class OrderLocation extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'order_location';
    protected $connection = 'call_service';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

    protected $casts = [
                          	'orderId' => "int",
                            'orderBeginLocation' => "string",
                            'orderBeginAddress' => "string",
                            'orderBeginLongitude' => "double",
                            'orderBeginLatitude' => "double",
                            'orderEndLocation' => "string",
                            'orderEndAddress' => "string",
                            'orderEndLongitude' => "double",
                            'orderEndLatitude' => "double",
                            'realBeginLocation' => "string",
                            'realBeginLongitude' => "double",
                            'realBeginLatitude' => "double",
                            'realEndLocation' => "string",
                            'realEndLongitude' => "double",
                            'realEndLatitude' => "double",
                            'callLongitude' => "double",
                            'callLatitude' => "double",
                            'grabLongitude' => "double",
                            'grabLatitude' => "double",
                            'arriveLongitude' => "double",
                            'arriveLatitude' => "double",
                        ];


    //重定义更新字段
    const UPDATED_AT = 'updatedAt';
    const CREATED_AT = 'createdAt';

    protected $primaryKey = 'orderId';

    protected $fillable = [
                            'orderId',
                            'orderBeginLocation',
                            'orderBeginAddress',
                            'orderBeginLongitude',
                            'orderBeginLatitude',
                            'orderEndLocation',
                            'orderEndAddress',
                            'orderEndLongitude',
                            'orderEndLatitude',
                            'realBeginLocation',
                            'realBeginLongitude',
                            'realBeginLatitude',
                            'realEndLocation',
                            'realEndLongitude',
                            'realEndLatitude',
                            'realBeginTime',
                            'realEndTime',
                            'points',
                            'callLongitude',
                            'callLatitude',
                            'grabLongitude',
                            'grabLatitude',
                            'arriveLongitude',
                            'arriveLatitude',
                            'createdAt',
                            'updatedAt'
                            ];


    /**
     * 获取半径xx米以内的历史叫车点，按该位置下车次数倒叙排序
     * @param array $squarePointArr
     * @param int $limit
     * @param int $userId
     * @return array $historyData
     */
    public static function getHistoryCallLocation($squarePointArr, $limit, $userId)
    {
        $historyData = self::select(DB::raw("count(orderBeginLocation) as total,min(orderBeginLongitude) as longitude, min(orderBeginLatitude) as latitude, orderBeginLocation as location, max(orderBeginAddress) as detail"))
                            ->leftJoin('order', 'order.id', '=', 'order_location.orderId')
                            ->whereBetween('orderBeginLatitude', [$squarePointArr['right_lat'], $squarePointArr['left_lat']])
                            ->whereBetween('orderBeginLongitude', [ $squarePointArr['left_lng'], $squarePointArr['right_lng']])
                            ->groupBy('orderBeginLocation')
                            ->where('order.userId',$userId)
                            ->orderBy('total', 'desc')
                            ->limit($limit)
                            ->get();

        return $historyData->toArray();
    }

    /**
     * 叫车位置及周边半径200米半径范围内上车的历史下车位置，按照查询次数排序
     * @param $squarePointArr
     * @param $limit
     * @param  $userId
     * @return mixed $historyData
     * select count(ol.`orderEndLocation`) as total, ol.`orderEndLocation` from `order_location` as ol left join `order` as o on ol.`orderId` = o.`id` where (ol.`orderBeginLatitude` BETWEEN 39.678691197041 AND 40.578012802959 ) AND (ol.`orderBeginLongitude` BETWEEN 116.06784650356 AND 117.24404349644) and o.userId =175 group by ol.`orderEndLocation`  order by total desc  limit 20;
     */
    public static function getHistoryGetOffLoc($squarePointArr, $limit, $userId)
    {
        $historyData = self::select(DB::raw("count(`orderEndLocation`) as total, min(orderEndLongitude) as longitude, min(orderEndLatitude) as latitude, orderEndLocation as location, max(orderEndAddress) as detail"))
            ->leftJoin('order', 'order.id', '=', 'order_location.orderId')
            ->whereBetween('orderBeginLatitude', [$squarePointArr['right_lat'], $squarePointArr['left_lat']])
            ->whereBetween('orderBeginLongitude', [ $squarePointArr['left_lng'], $squarePointArr['right_lng']])
            ->where('order.userId',$userId)
            ->groupBy('orderEndLocation')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get();

        return $historyData->toArray();
    }


}
