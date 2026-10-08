<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;
use Boundwize\StructArmed\Rule\Rules\Class_\MustBeFinalRule;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())

    ->layer('Totp', 'src/Totp.php')
    ->layer('TotpFactory', 'src/TotpFactory.php')
    ->layer('ConfigProvider', 'src/ConfigProvider.php')
    ->ruleset([
        'Totp'           => [],
        'TotpFactory'    => ['Totp'],
        'ConfigProvider' => ['+TotpFactory'],
    ])

    ->layer('tests', 'test')
    ->rule(
        'tests_must_be_final',
        new MustBeFinalRule(layer: 'tests')
    );
