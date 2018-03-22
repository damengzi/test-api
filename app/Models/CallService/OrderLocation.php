<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/20
 * Time: 下午2:44
 */

namespace App\Models\CallService;


use Illuminate\Database\Eloquent\Model;

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

}