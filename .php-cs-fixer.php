<?php
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => [
            'imports_order' => [
                OrderedImportsFixer::IMPORT_TYPE_CLASS,
                OrderedImportsFixer::IMPORT_TYPE_CONST,
                OrderedImportsFixer::IMPORT_TYPE_FUNCTION,
            ],
        ],
        'php_unit_construct' => true,
        'php_unit_strict' => true,
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
