<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/23
 * Time: 上午9:43
 */

namespace App\Console\Commands;


use App\Models\OrderRedressLog;
use App\Services\YingYanService;
use App\Services\DistanceService;
use App\Models\CallService\Order;
use App\Models\OrderLocation;
use Illuminate\Console\Command;

class OldOrderCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oldOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '历史订单跑鹰眼及价格数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(YingYanService $yingYanService)
    {
        $this->yingYanService = $yingYanService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //获取复合条件的订单
        $orders = Order::where('order.status', 5)
            ->select('order.id', 'ol.points', 'ol.realBeginLatitude', 'ol.realBeginLongitude', 'ol.realEndLatitude', 'ol.realEndLongitude', 'of.totalFee', 'of.totalDistance', 'of.baseDistanceFee', 'opf.preTotalDistance')
            ->leftJoin('order_location as ol', 'ol.orderId', '=', 'order.id')
            ->leftJoin('order_fee as of', 'of.orderId', '=', 'order.id')
            ->leftJoin('order_pre_fee as opf', 'opf.orderId', '=', 'order.id')
            ->where('order.createdAt', '>', date('Y-m-d', strtotime("-1 month")))
            ->where('order.createdAt', '<=', date("Y-m-d H:i:s"))
            ->where('order.id', '>', 0)
//            ->where('order.id', 3304)
            ->limit(100)
            ->get()
            ->toArray();

        foreach ($orders as $order){
            //预估和实际距离相比较，相差不大的直接跳过
            if(abs($order['totalDistance'] - $order['preTotalDistance']) < OrderRedressLog::DIFF_PRE_OR_REAL_DISTANCE){
                continue;
            }

            $orderId = $order['id'];
            //获取鹰眼的全部距离
            $distance = $this->yingYanInterface($orderId);
            echo "鹰眼距离" . $distance . '-----预估距离' .$order['preTotalDistance'] . "\n";
            //如果获取鹰眼距离失败，调用直线距离
            if($distance === false){
                $distance = DistanceService::getDistanceOfPoints(json_decode($order['points'], true));
            }else{
                if(abs($distance - $order['preTotalDistance']) > OrderRedressLog::DIFF_DISTANCE){
                    $distance = $order['preTotalDistance'];
                }else{
                    continue;
                }
            }


            //获取机丢点数据，并获取丢的轨迹距离
            $redressDistance = DistanceService::getRedressDistance($order);
            \Log::info('redressDistance  ' . $redressDistance);
            if($redressDistance === false){
                \Log::info('redressDistance  计算丢失距离失败');
                continue;
            }

            #假定这个比例值为X，丢失距离为L1、总里程为L，最大补偿系数为Y，补偿前费用为S，补偿费用=MIN（车型里程单价*（L1-L*X），S*Y）
            #（L1-L*X）
            if($redressDistance - $distance * OrderRedressLog::REDRESS_SPACING  < 0){
                continue;
            }
            //计算需要补偿的价格
            $price = $order['baseDistanceFee'] * $redressDistance/1000;
            $redressMoney = $price > OrderRedressLog::MAX_REDRESS_NUM ? OrderRedressLog::MAX_REDRESS_NUM : $price;
            $insertOrderRedressLog = [];
            $insertOrderRedressLog['orderId'] = $order['id'];
            $insertOrderRedressLog['eagleTotalDistance'] = $distance;
            $insertOrderRedressLog['redressDistance'] = $redressDistance;
            $insertOrderRedressLog['distance'] = $order['totalDistance'];
            $insertOrderRedressLog['money'] = $order['totalFee'];
            $insertOrderRedressLog['redressMoney'] = $redressMoney;
            $insertOrderRedressLog['totalMoney'] = $redressMoney + $order['totalFee'];

            OrderRedressLog::insert($insertOrderRedressLog);

        }

    }

    public function yingYanInterface($orderId)
    {
        $orderInfo = Order::where('id', $orderId)->first();
        if (empty($orderInfo) || !$orderInfo->driverId) {
            echo "该订单没有司机接单:" . $orderId . "\n";
            return false ;
        }
        $orderLocationInfo = OrderLocation::where('orderId', $orderId)->first();
        if (empty($orderLocationInfo)) {
            echo "该订单在数据没有记录:" . $orderId . "\n";
            return false;
        }
        $pointsStr = $orderLocationInfo->points;
        $pointsArray = json_decode($pointsStr, true);
        if (empty($pointsArray)) {
            echo "该订单没有获取到有效坐标点:" . $orderId . "\n";
            return false;
        }
        $entityName = 'call_order_driver_id_' . $orderInfo->driverId;
        $entityDesc = '即时用车司机ID_' . $orderInfo->driverId;
        \Log::info('entityDesc' . $entityDesc);
        // 先为该司机注册一下鹰眼
        $registerRes = $this->yingYanService->addEntity($entityName, $entityDesc);
        $registerStatus = array_get($registerRes, 'status');
        $registerMessage = array_get($registerRes, 'message');
        $retries = 4;
        while (($registerStatus != 0 && $registerStatus != 3005) && (--$retries >0)) { // status=0为注册成功，status=3005 为此entity_name已存在
            echo '该订单司机注册鹰眼失败:' . $orderId . $registerMessage . " 重试：" . $retries . "\n";
            $registerRes = $this->yingYanService->addEntity($entityName, $entityDesc);
            $registerStatus = array_get($registerRes, 'status');
            $registerMessage = array_get($registerRes, 'message');
        }

        if ($registerStatus != 0 && $registerStatus != 3005) { // status=0为注册成功，status=3005 为此entity_name已存在
            echo '该订单司机注册鹰眼失败:' . $orderId . $registerMessage . "\n";
            return false;
        }
        $pointList = [];
        $startTime = '';
        $endTime = '';
        foreach ($pointsArray as $key => $points) {
            if ($key == 0) {
                $startTime = $points['t'];
            }
            $endTime = $points['t'];
            $pointList[$key]['latitude'] = $points['la'];
            $pointList[$key]['longitude'] = $points['lo'];
            $pointList[$key]['loc_time'] = $points['t'];
            $pointList[$key]['entity_name'] = $entityName;
            $pointList[$key]['coord_type_input'] = 'gcj02';
        }

        $step = 0;
        $postPoints = [];
        // 分批上传坐标点，每批100个点
        foreach ($pointList as $point) {
            $step++;
            $postPoints[] = $point;
            if ($step % 100 == 0) {

                $sendPointsRes = $this->yingYanService->addPoints($postPoints);
                sleep(2);
                $sendPointsResStatus = array_get($sendPointsRes, 'status');
                $sendPointsResMessage = array_get($sendPointsRes, 'message');
                \Log::info('循环上传点数量'. count($postPoints));
                if ($sendPointsResStatus != 0) {
                    echo '上传坐标点失败:' . $orderId . $sendPointsResMessage . "\n";
                    return false;
                }
                $postPoints = [];
            }
        }
        \Log::info('上传点数量' . count($postPoints) . "orderId:" . $orderId);
        // 最后的不足100个的点上传
        $sendPointsRes = $this->yingYanService->addPoints($postPoints);
        $sendPointsResStatus = array_get($sendPointsRes, 'status');
        $sendPointsResMessage = array_get($sendPointsRes, 'message');
        if ($sendPointsResStatus != 0) {
            echo '上传坐标点失败:' . $orderId . $sendPointsResMessage . "\n";
            return false;
        }

        // 查询该订单的纠偏里程
        $distanceRes = $this->yingYanService->getDistance($startTime, $endTime, $entityName);
        \Log::info('sssss',[$distanceRes]);
        $distanceResStatus = array_get($distanceRes, 'status');
        $distanceResMesg = array_get($distanceRes, 'message');
        $distanceResDis = array_get($distanceRes, 'distance');
        if ($distanceResStatus != 0) {
            echo '查询订单纠偏里程失败：' . $orderId . $distanceResMesg . "\n";
            return false;
        }
        echo "订单ID：" . $orderId . "获得鹰眼距离为：" . $distanceResDis . "\n";
        return $distanceResDis;
    }
}