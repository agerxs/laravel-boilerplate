<?php


namespace App\Http\Traits;


use App\Http\Utils\Constants;

trait JsonResponseFormatter
{
    public function format(string $status, int $codeStatus, string $message,  $datas = [])
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $datas,

        ], $codeStatus);
    }
}
