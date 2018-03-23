<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/23
 * Time: 上午9:43
 */

namespace App\Console\Commands;


use App\Services\YingYanService;
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
        \Log::info('上传点数量' . count($postPoints));
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