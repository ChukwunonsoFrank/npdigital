<?php

namespace App\Support;

class Cryptocurrencies
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'BTC' => 'Bitcoin (BTC)',
            'ETH' => 'Ethereum (ETH)',
            'USDT-TRC20' => 'USDT (TRC20)',
            'USDT-ERC20' => 'USDT (ERC20)',
            'BNB' => 'BNB (BSC)',
            'SOL' => 'Solana (SOL)',
            'XRP' => 'XRP',
            'DOGE' => 'Dogecoin (DOGE)',
            'LTC' => 'Litecoin (LTC)',
            'TRX' => 'Tron (TRX)',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function label(?string $code): string
    {
        return self::all()[$code] ?? (string) $code;
    }
}
