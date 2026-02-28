<?php

namespace App\Support\Sms;

use App\Models\Garage;

class SmsSettings
{
    /**
     * Resolve effective SMS settings for a garage.
     *
     * - If garage->use_global_sms = true => use config('sms.*') and config('sms.drivers.*')
     * - Else use garage->sms_driver + garage->sms_config overrides
     */
    public static function forGarage(Garage $garage): array
    {
        $useGlobal = (bool) $garage->use_global_sms;

        // Global base
        $globalDriver = config('sms.driver', 'fake');
        $globalCfg = (array) (config("sms.drivers.$globalDriver") ?? []);

        if ($useGlobal) {
            return [
                'source' => 'global',
                'driver' => $globalDriver,
                'config' => $globalCfg,
            ];
        }

        // Garage-specific
        $garageDriver = $garage->sms_driver ?: $globalDriver;
        $garageCfg = is_array($garage->sms_config) ? $garage->sms_config : [];

        // Merge: global driver defaults + garage overrides
        $merged = array_merge(
            (array) (config("sms.drivers.$garageDriver") ?? []),
            $garageCfg
        );

        return [
            'source' => 'garage',
            'driver' => $garageDriver,
            'config' => $merged,
        ];
    }

    /**
     * Convenience: get "from"/sender for resolved settings (if available)
     */
    public static function sender(array $settings): ?string
    {
        return $settings['config']['from'] ?? null;
    }

    /**
     * Convenience: get URL/api_key for hostpinnacle quickly
     */
    public static function hostPinnacle(array $settings): array
    {
        return [
            'url'     => $settings['config']['url'] ?? null,
            'api_key' => $settings['config']['api_key'] ?? null,
            'from'    => $settings['config']['from'] ?? null,
            'timeout_seconds' => $settings['config']['timeout_seconds'] ?? 15,
        ];
    }
}
