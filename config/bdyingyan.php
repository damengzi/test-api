<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/20
 * Time: 下午2:05
 */

return [
    'yingyan_host' => 'http://yingyan.baidu.com',
    'entity_add_url' => '/api/v3/entity/add', // 添加鹰眼客户端
    'add_points_url' => '/api/v3/track/addpoints', // 鹰眼批量上传坐标点
    'add_point_url' => '/api/v3/track/addpoint', // 鹰眼添加单个坐标点
    'get_distance_url' => '/api/v3/track/getdistance', // 获取纠偏距离
    'get_distance_track_url' => '/api/v3/track/gettrack', // 获取纠偏距离

    'ak' => 'SEesyY7PmuYhT9HkiDEobZ0hd7d2IIc9', // 测试ak 个人未验证（单日调用量100000）

    'service_id' => '162002', // 创建鹰眼服务ID （线上162000）

];