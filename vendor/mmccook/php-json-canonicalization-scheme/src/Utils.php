<?php

namespace Mmccook\JsonCanonicalizator;

class Utils
{
    /**
     * @param array $array
     * @return bool
     */
    public static function isAssoc(array $array): bool
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    public static function asHex(string $data): string
    {
        return rtrim(chunk_split(bin2hex($data), 2, ' '));
    }

    public static function es6NumberFormat(float $number): string
    {

        if (is_nan($number) || is_infinite($number)) {
            throw new \RuntimeException("can't use Nan or Infinity in json");
        }

        if (0.0 === $number) {
            return '0';
        }

        $sign = '';
        if ($number < 0) {
            $sign = '-';
            $number = -$number;
        }

        if ($number < 1e+21 && $number >= 1e-6) {
            $formatted = number_format($number, 7, '.', '');
            $formatted = rtrim($formatted, '.0');
        } else {
            $formatted = sprintf('%e', $number);
            $parts = explode('e', $formatted);
            $parts[0] = rtrim($parts[0], '.0');
            $formatted = implode('e', $parts);
        }

        return $sign . $formatted;
    }
}
