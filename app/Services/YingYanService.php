<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/20
 * Time: 下午2:18
 */

namespace App\Services;


use Config;

class YingYanService {

    /**
     * 添加鹰眼账号
     * @param $entity_name string 账号名称
     * @param $entity_desc string 账号描述
     * @param $city string 所属城市
     * @param $district string 所属区县
     * @return array
     */
    public function addEntity($entityName, $entityDesc, $cityName = '', $district = '')
    {
        $uri = Config::get('bdyingyan.yingyan_host') . Config::get('bdyingyan.entity_add_url');
        $serviceId = Config::get('bdyingyan.service_id');
        $ak = Config::get('bdyingyan.ak');
        $data = [
            'ak' => $ak,
            'service_id' => $serviceId,
            'entity_name' => $entityName,
            'entity_desc' => $entityDesc,
            'city_name' => $cityName,
            'district' => $district
        ];
        $result = CommonService::curlRequest($uri, 'post', $data);

        return json_decode($result, true);
    }

    /**
     * 鹰眼批量上传坐标点
     * @param $pointList array 坐标点数据
     * @return mixed
     */
    public function addPoints($pointList)
    {
        $uri = Config::get('bdyingyan.yingyan_host') . Config::get('bdyingyan.add_points_url');
        $serviceId = Config::get('bdyingyan.service_id');
        $ak = Config::get('bdyingyan.ak');
        $data = [
            'ak' => $ak,
            'service_id' => $serviceId,
            'point_list' => json_encode($pointList)
        ];
        $result = CommonService::curlRequest($uri, 'post', $data);

        return json_decode($result, true);
    }

    /**
     * 查询鹰眼纠偏里程
     * @param $startTime string  开始时间戳
     * @param $endTime string 结束时间戳
     * @param $entityName  string 鹰眼注册账户名
     * @return array
     */
    public function getDistance($startTime, $endTime, $entityName)
    {
        $uri = Config::get('bdyingyan.yingyan_host') . Config::get('bdyingyan.get_distance_track_url');
        $serviceId = Config::get('bdyingyan.service_id');
        $ak = Config::get('bdyingyan.ak');
        /**
         * 纠偏选项取值:
         * 1.去噪，示例：
         * need_denoise =0：不去噪
         * need_denoise =1：去噪
         * 2.绑路，示例：
         * need_mapmatch=0：不绑路
         * need_mapmatch=1：绑路
         * 3. 定位精度过滤，用于过滤掉定位精度较差的轨迹点，示例：
         * radius_threshold=0：不过滤
         * radius_threshold=100：过滤掉定位精度 Radius 大于100的点
         * 说明：当取值=0时，则不过滤；当取值大于0的整数时，则过滤掉radius大于设定值的轨迹点。
         * 例如：若只需保留 GPS 定位点，则建议设为：20；若需保留 GPS 和 Wi-Fi 定位点，去除基站定位点，则建议设为：100
         * 4.交通方式，鹰眼将根据不同交通工具选择不同的纠偏策略，目前支持：自动（即鹰眼自动识别的交通方式）、驾车、骑行和步行，示例：
         * transport_mode=auto
         * transport_mode=driving
         * transport_mode=riding
         * transport_mode=walking
         *
         * 里程补偿方式：
         * 默认值：no_supplement，不补充
         * 在里程计算时，两个轨迹点定位时间间隔5分钟以上，被认为是中断。中断轨迹提供以下5种里程补偿方式。
         * no_supplement：不补充，中断两点间距离不记入里程。
         * straight：使用直线距离补充
         * driving：使用最短驾车路线距离补充
         * riding：使用最短骑行路线距离补充
         * walking：使用最短步行路线距离补充
         */
        $data = [
            'ak' => $ak,
            'service_id' => $serviceId,
            'entity_name' => $entityName,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_processed' => 1, // 是否返回纠偏后里程
            'process_option' => "need_denoise=1,need_mapmatch=1,radius_threshold=20,transport_mode=driving", // 纠偏选项
            'supplement_mode' => 'driving', // 里程补偿方式
        ];
        $url = CommonService::buildUrl($uri, $data);
        \Log::info('ddddddurl: ' . $url);
        $result = CommonService::curlRequest($url, 'get');

        return json_decode($result, true);
    }
}