<?php

namespace Mmccook\JsonCanonicalizator;

class JsonCanonicalizator implements JsonCanonicalizatorInterface
{
    public const JSON_FLAGS = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES;

    /**
     * @param $data
     * @param bool $asHex
     * @return string
     */
    public function canonicalize($data, bool $asHex = false): string
    {
        ob_start();

        $this->serialize($data);

        $result = ob_get_clean();

        return $asHex ? Utils::asHex($result) : $result;
    }

    private function serialize($item)
    {
        if (is_float($item)) {
            echo Utils::es6NumberFormat($item);

            return;
        }

        if (null === $item || is_scalar($item)) {
            echo json_encode($item, self::JSON_FLAGS);

            return;
        }

        if (is_array($item) && ! Utils::isAssoc($item)) {
            echo '[';
            $next = false;
            foreach ($item as $element) {
                if ($next) {
                    echo ',';
                }
                $next = true;
                $this->serialize($element);
            }
            echo ']';

            return;
        }

        if (is_object($item)) {
            $item = (array)$item;
        }

        uksort($item, function (string $a, string $b) {
            $a = mb_convert_encoding($a, 'UTF-16BE');
            $b = mb_convert_encoding($b, 'UTF-16BE');

            return strcmp($a, $b);
        });

        echo '{';
        $next = false;
        foreach ($item as $key => $value) {
            //var_dump($key, $value);
            if ($next) {
                echo ',';
            }
            $next = true;
            $outKey = json_encode((string)$key, self::JSON_FLAGS);
            echo $outKey, ':', $this->serialize($value);
        }
        echo '}';

    }
}
