<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/20
 * Time: 下午3:31
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRedressLog extends Model {

    protected $table = 'order_redress_log';
    protected $connection = 'call_service';

    const CREATED_AT = 'createdAt';

    //订单丢点补偿系数
    const SPACING = 100;    //  间距差
    const REDRESS_SPACING = 0.3;    //  补偿系数(丢失点距离/总距离 >30%时需要进行补偿计算)
    const MAX_REDRESS_NUM = 20;    //  最大补偿系数为Y

    protected $fillable	= 	[
        'orderId',
        'eagleTotalDistance',
        'redressDistance',
        'distance',
        'money',
        'redressMoney',
        'createdAt',
    ];
}