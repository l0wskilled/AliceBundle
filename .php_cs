<?php

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Yaml\Yaml;
use Symfony\CS\Config\Config;
use Symfony\CS\Finder\DefaultFinder;
use Symfony\CS\Fixer\Contrib\HeaderCommentFixer;
use Symfony\CS\FixerInterface;

$header = <<<EOF
This file is part of the Hautelook\AliceBundle package.

(c) Baldur Rensch <brensch@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

HeaderCommentFixer::setHeader($header);

// Harmonize php-cs-fixer config with StyleCI
$styleCIConfig = Yaml::parse(file_get_contents(__DIR__.'/.styleci.yml'));

$fixers = array_merge(
    isset($styleCIConfig['enabled']) ? $styleCIConfig['enabled'] : [],
    isset($styleCIConfig['disabled']) ? array_map(function ($disabledFixer) {
        return '-'.$disabledFixer;
    }, $styleCIConfig['disabled']) : []
);
// Header comment fixer cannot be configured on .styleci.yml for now.
array_push($fixers, 'header_comment');

$levels = [
    'psr1'    => FixerInterface::PSR1_LEVEL,
    'psr2'    => FixerInterface::PSR1_LEVEL,
    'symfony' => FixerInterface::SYMFONY_LEVEL,
];
$level = isset($styleCIConfig['preset']) && isset($levels[$styleCIConfig['preset']])
    ? $levels[$styleCIConfig['preset']] : FixerInterface::SYMFONY_LEVEL;

$finder = DefaultFinder::create()->in([__DIR__]);
if (isset($styleCIConfig['finder']) && is_array($styleCIConfig['finder'])) {
    $finderConfig = $styleCIConfig['finder'];
    foreach ($finderConfig as $key => $values) {
        $finderMethod = Container::camelize(str_replace('-', '_', $key));
        foreach ($values as $value) {
            $finder->$finderMethod($value);
        }
    }
}

return Config::create()
    ->level($level)
    ->fixers($fixers)
    ->setUsingCache(true)
    ->finder($finder)
;