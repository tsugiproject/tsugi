<?php

declare(strict_types=1);

namespace Mmccook\JsonCanonicalizator;

interface JsonCanonicalizatorInterface
{
    public function canonicalize($data, bool $asHex): string;
}
