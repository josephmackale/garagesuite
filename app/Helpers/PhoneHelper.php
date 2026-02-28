<?php

namespace App\Helpers;

class PhoneHelper
{
    public static function normalize($phone)
    {
        if (!$phone) return null;

        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        return $phone;
    }
}
