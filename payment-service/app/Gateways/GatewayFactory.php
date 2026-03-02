<?php

namespace App\Gateways;

use InvalidArgumentException;

/**
 * Factory to resolve gateway adapter based on operator's gateway_type.
 */
class GatewayFactory
{
    /**
     * Supported gateway types mapped to their adapter classes.
     */
    private static array $adapters = [
        'digivas'          => DigivasGateway::class,
        'safaricom_daraja' => SafaricomDarajaGateway::class,
        'airtel_africa'    => AirtelAfricaGateway::class,
        'mtn_momo'         => MtnMomoGateway::class,
    ];

    /**
     * Resolve and return a gateway adapter instance.
     */
    public static function make(string $gatewayType): GatewayInterface
    {
        $type = strtolower(trim($gatewayType));

        if (!isset(self::$adapters[$type])) {
            throw new InvalidArgumentException("Unsupported gateway type: {$gatewayType}");
        }

        return new self::$adapters[$type]();
    }

    /**
     * List all supported gateway types.
     */
    public static function supportedTypes(): array
    {
        return array_keys(self::$adapters);
    }

    /**
     * Register a custom gateway adapter.
     */
    public static function register(string $type, string $adapterClass): void
    {
        if (!in_array(GatewayInterface::class, class_implements($adapterClass))) {
            throw new InvalidArgumentException("Adapter class must implement GatewayInterface.");
        }
        self::$adapters[strtolower($type)] = $adapterClass;
    }
}
