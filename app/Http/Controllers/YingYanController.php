<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/19
 * Time: 下午12:21
 */

namespace App\Http\Controllers;


use App\Models\CallService\Order;
use App\Models\CallService\OrderDriver;
use App\Models\CallService\OrderLocation;
use App\Services\YingYanService;
use Illuminate\Http\Request;

class YingYanController extends Controller {

    public function __construct(YingYanService $yingYanService)
    {
        $this->yingYanService = $yingYanService;
    }

    /**
     * 添加账户
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function entityAdd (Request $request)
    {
        $entity_name = $request->input('entity_name');
        $entity_desc = $request->input('entity_desc');
        $city = $request->input('city'); // 扩展字段 城市
        $district = $request->input('district'); // 扩展字段 区县
        if (!$entity_name || !$entity_desc) {
            return $this->json(1, [], '参数错误');
        }
        $result = $this->yingYanService->addEntity($entity_name, $entity_desc, $city, $district);
        return $this->json(0, $result, 'success');
    }

    /**
     * 通过订单ID获得该订单鹰眼纠偏后的里程
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistanceByOrderId(Request $request)
    {
        $orderId = $request->input('order_id');
        if (!$orderId || !is_numeric($orderId)) {
            return $this->json(1, [], '参数错误');
        }

        $orderInfo = Order::where('id', $orderId)->first();
        if (empty($orderInfo) || !$orderInfo->driverId) {
            return $this->json(2, [], '该订单没有司机接单');
        }
        $orderLocationInfo = OrderLocation::where('orderId', $orderId)->first();
        if (empty($orderLocationInfo)) {
            return $this->json(3, [], '该订单在数据没有记录');
        }
        $pointsStr = $orderLocationInfo->points;
        $pointsArray = json_decode($pointsStr, true);
        if (empty($pointsArray)) {
            return $this->json(4, [], '该订单没有获取到有效坐标点');
        }
        $entityName = 'call_order_driver_id_' . $orderInfo->driverId;
        $entityDesc = '即时用车司机ID_' . $orderInfo->driverId;
        \Log::info('sssssssssss' . $entityDesc);
        // 先为该司机注册一下鹰眼
        $registerRes = $this->yingYanService->addEntity($entityName, $entityDesc);
        $registerStatus = array_get($registerRes, 'status');
        $registerMessage = array_get($registerRes, 'message');
        if ($registerStatus != 0 && $registerStatus != 3005) { // status=0为注册成功，status=3005 为此entity_name已存在
            return $this->json(5, [], '该订单司机注册鹰眼失败:' . $registerMessage);
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
                \Log::info('sssss'. count($postPoints));
                if ($sendPointsResStatus != 0) {
                    return $this->json(6, [], '上传坐标点失败:' . $sendPointsResMessage);
                }
                $postPoints = [];
            }
        }
        \Log::info('dddddd' . count($postPoints));
        // 最后的不足100个的点上传
        $sendPointsRes = $this->yingYanService->addPoints($postPoints);
        $sendPointsResStatus = array_get($sendPointsRes, 'status');
        $sendPointsResMessage = array_get($sendPointsRes, 'message');
        if ($sendPointsResStatus != 0) {
            return $this->json(7, [], '上传坐标点失败:' . $sendPointsResMessage);
        }

        // 查询该订单的纠偏里程
        $distanceRes = $this->yingYanService->getDistance($startTime, $endTime, $entityName);
        \Log::info('sssss',[$distanceRes]);
        $distanceResStatus = array_get($distanceRes, 'status');
        $distanceResMesg = array_get($distanceRes, 'message');
        $distanceResDis = array_get($distanceRes, 'distance');
        if ($distanceResStatus != 0) {
            return $this->json(7, [], '查询订单纠偏里程失败：' . $distanceResMesg);
        }
        return $this->json(0, ['distance' => $distanceResDis], 'success');
    }

    /**
     * 添加单个坐标点
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPoint(Request $request)
    {
        $entityName = $request->input('entity_name');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $locTime = $request->input('loc_time');
        $coordTypeInput = $request->input('coord_type_input');
        if (!$entityName || !$latitude || !$longitude || !$locTime || !$coordTypeInput){
            return $this->json(1, [], '参数错误');
        }

        $data = [];
        return $this->json(0, $data, 'success');
    }

    /**
     * 获取导航里程
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistance(Request $request)
    {
        $data = [];
        return $this->json(0, $data, 'success');
    }
}