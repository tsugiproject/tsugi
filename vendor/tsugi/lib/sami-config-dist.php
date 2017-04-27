<?php

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in('src')
;

return new Sami($iterator, array(
    'theme'                => 'default',
    'title'                => 'Tsugi API',
    'build_dir'            => '/tmp/tsugi',
    'cache_dir'            => '/tmp/cache',
    'default_opened_level' => 2,
));

