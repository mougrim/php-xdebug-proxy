<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'ordered_imports' => [
            'imports_order' => [
                'class',
                'const',
                'function',
            ],
        ],
        'strict_comparison' => true,
        'combine_consecutive_unsets' => true,
        'dir_constant' => true,
        'ereg_to_preg' => true,
        'modernize_types_casting' => true,
        'multiline_whitespace_before_semicolons' => false,
        'no_php4_constructor' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'strict_param' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_align' => false,
        'yoda_style' => false,
        'increment_style' => false,
        'phpdoc_no_empty_return' => false,
        'single_line_throw' => false,
    ])
    ->setFinder(
        Finder::create()
            ->in(__DIR__)
    )
;
