<?php

declare(strict_types=1);

namespace Mmccook\JsonCanonicalizator;

class JsonCanonicalizatorFactory
{
    public static function getInstance(): JsonCanonicalizator
    {
        return new JsonCanonicalizator();
    }
}
