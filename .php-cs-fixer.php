<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
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
        'phpdoc_order' => ['order' => ['param', 'return', 'throws']],
        'strict_param' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_align' => false,
        'yoda_style' => false,
        'increment_style' => false,
        'phpdoc_no_empty_return' => false,
        'single_line_throw' => false,
        'blank_line_before_statement' => false,
        'phpdoc_separation' => [
            'groups' => [
                ['Annotation', 'NamedArgumentConstructor', 'Target'],
                ['author', 'copyright', 'license'],
                ['category', 'package', 'subpackage'],
                ['property', 'property-read', 'property-write'],
                ['deprecated', 'link', 'see', 'since'],
                ['return', 'phpstan-return', 'param', 'phpstan-param'],
            ],
        ],
    ])
    ->setFinder(
        Finder::create()
            ->in(__DIR__)
            ->exclude(['tmp', 'vendor'])
    )
;
