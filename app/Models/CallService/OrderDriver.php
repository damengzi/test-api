<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/20
 * Time: 下午3:31
 */

namespace App\Models\CallService;

use Illuminate\Database\Eloquent\Model;

class OrderDriver extends Model {

    protected $table = 'order_driver';
    protected $connection = 'call_service';
    const UPDATED_AT = 'updatedAt';
    const CREATED_AT = 'createdAt';

    protected $fillable	= 	[
        'orderId',
        'driverId',
        'driverNetType',
        'driverAppVersion',
        'driverMobileType',
        'driverMobileSystem',
        'driverSystemVersion',
        'createdAt',
        'updatedAt'
    ];
}