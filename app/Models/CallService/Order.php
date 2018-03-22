<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/22
 * Time: 上午11:06
 */

namespace App\Models\CallService;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'order';
    protected $connection = 'call_service';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */

    protected $casts = [
        'id' => "int",
        'orderNo' => "string",
        'status' => "int",
        'cityId' => "int",
        'cityName' => 'string',
        'userId' => "int",
        'driverId' => "int",
        'carType' => "string",
        'orderStar' => "int"
    ];


    //重定义更新字段
    const UPDATED_AT = 'updatedAt';
    const CREATED_AT = 'createdAt';

    //订单状态定义
    const ORDER_STATUS_USER_CANCLE_PAYED = -4;    //  -4:用户有则取消-已支付
    const ORDER_STATUS_USER_CANCLE_BEPAY = -3;    //  -3:用户有则取消-待支付
    const ORDER_STATUS_USER_CANCLE_NOPAY = -2;    //  -2:用户有责取消-无需支付
    const ORDER_STATUS_USER_CANCLE_NIL = -1;    //  -1:用户无责取消
    const ORDER_STATUS_CREATED = 0;     //  0:已创建-发单中
    const ORDER_STATUS_ACCEPT = 1;     //  1:已接单
    const ORDER_STATUS_ARRIVE = 101;   //  101:已到达
    const ORDER_STATUS_RECEIVE_USER = 2;     //  2:已接客-服务中
    const ORDER_STATUS_FINISH_SERVICE = 3;     //  3:服务完成-待上传待费用确认
    const ORDER_STATUS_CONFIRM_SERVICE = 4;     //  4:服务完成-司机确认费用-待用户支付
    const ORDER_STATUS_PAYED = 5;     //  5:用户支付完成
    const ORDER_STATUS_PLAT_REDRESS = 6;     //  6:平台补偿
    const ORDER_STATUS_NOT_APPRAISED = 201;   //  201 待评价

    protected $fillable = [
        'id',
        'orderNo',
        'status',
        'cityId',
        'cityName',
        'userId',
        'driverId',
        'carType',
        'carTypeDesc',
        'orderStar',
        'orderStarDesc',
        'vendorCompanyPer',
        'vendorDriverPer',
        'createdAt',
        'updatedAt'
    ];


}