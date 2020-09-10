<?php
if (! function_exists('customThrow')) {

    function customThrow($message = '', $code = 422, $type = 'none')
    {
        if ($type == 'none') {
            throw (new \App\Exceptions\ApiCustomException())->withMessage(__($message))->withCode($code);
        } else {
            throw (new \App\Exceptions\ApiCustomException())->withError(__($message), $type)->withCode($code);
        }
    }
}

if (! function_exists('customThrowIf')) {

    function customThrowIf($bolean, $message = '', $code = 422, $type = 'none')
    {
        if($bolean) {
            customThrow($message, $code, $type);
        }
    }
}

if (! function_exists('timeToSeconds')) {

    function timeToSeconds($time)
    {
        $parsed = date_parse($time);
        $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
        return $seconds;
    }
}

if (! function_exists('secondsToHour')) {

    function secondsToHour($seconds, $format = 'H:i')
    {
        return gmdate($format, $seconds);
    }
}

if (! function_exists('randomMD5')) {

    function randomMD5()
    {
        return md5(microtime().str_random(100));
    }
}

if ( ! function_exists('toCarbon')) {

    function toCarbon($string)
    {
        if ( ! $string) {
            return null;
        }

        try {
            return new \Carbon\Carbon($string);
        } catch (\Exception $e) {
            return null;
        }
    }
}
