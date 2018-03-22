<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function json($code,$data=[],$message='')
    {
        $result = ['code' => $code, 'data' => $data , 'message' => $message ];

        $request = app('request');

        $info = [];
        $info["uri"] = $request->getRequestUri();
        $info["params"] = $request->input();
        $info["result"] = $result;


        \Log::info(json_encode($info));

        return response()->json($result);
    }
}
