<?php

declare(strict_types=1);

use Happyr\CS\Config;

if (!\class_exists(Config::class)) {
    die('You need to install happyr/coding-standard. See https://gitlab.com/happyr/coding-standard');
}

return (new Config([]))
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__.'/src')
            ->name('*.php')
    );
