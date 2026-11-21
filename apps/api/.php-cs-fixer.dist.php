<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/config', __DIR__.'/migrations'])
    ->exclude(['var'])
    ->notPath(['reference.php', 'preload.php']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP84Migration' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        'final_class' => false,
        'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced', 'strict' => true],
        'phpdoc_to_comment' => false,
        'php_unit_test_class_requires_covers' => false,
        'php_unit_internal_class' => false,
        'php_unit_method_casing' => ['case' => 'camel_case'],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer.cache');
