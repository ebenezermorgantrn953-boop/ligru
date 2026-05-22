<?php
/**
 * 统一JSON响应
 */
class Response
{
    public static function success($data = null, $msg = 'success')
    {
        self::output(0, $msg, $data);
    }

    public static function error($msg = 'error', $code = 1, $data = null)
    {
        self::output($code, $msg, $data);
    }

    public static function output($code, $msg, $data = null)
    {
        echo json_encode([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'time' => time(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
