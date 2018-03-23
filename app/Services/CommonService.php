<?php
/**
 * Created by PhpStorm.
 * User: mafangchao
 * Date: 2018/3/20
 * Time: 下午2:24
 */

namespace  App\Services;
use Log;

class CommonService {

    /**
     * curl工具方法
     * @param $url 请求地址
     * @param string $requestType 请求方式 post 或 get
     * @param array $data post 请求数据
     * @param int $timeout 请求超时
     * @return mixed
     */
    public static function curlRequest($url, $requestType = "get", $data = array(), $timeout = 300)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        if (strtolower($requestType) == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!$result = curl_exec($ch))
        {
            $err = curl_error($ch);
            Log::info("curl failed errcode:$err url:" . $url . " data:" . var_export($data,true) . " result:" . $result);
        }

        curl_close($ch);
        return $result;
    }



    public static function ctoutf8($str)
    {
        try {
            if (empty($str)) return "";
            if (mb_detect_encoding($str, array("ASCII","GB2312","GBK","BIG5","UTF-8")) != "UTF-8" )
            {
                return iconv("GB2312","UTF-8",$str);
            }

            return $str;
        } catch (\Exception $e) {

        }

        return $str;
    }


    /**
     * GET请求生成url
     * @param $url
     * @param string $mixed_data
     * @return string
     */
    public static function buildUrl($url, $mixed_data = '')
    {
        $query_string = '';
        if (!empty($mixed_data)) {
            $query_mark = strpos($url, '?') > 0 ? '&' : '?';
            if (is_string($mixed_data)) {
                $query_string .= $query_mark . $mixed_data;
            } elseif (is_array($mixed_data)) {
                $query_string .= $query_mark . http_build_query($mixed_data, '', '&');
            }
        }
        return $url . $query_string;
    }
}