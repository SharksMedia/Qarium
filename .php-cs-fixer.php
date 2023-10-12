<?php

require __DIR__.'/vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->in('src')
    ->in('tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    // ->registerCustomFixers([new Sharksmedia\SharQ\Fixer\SquareBracketNewLineFixer()])
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->setRules([
        'indentation_type'                    => true,
        'linebreak_after_opening_tag'         => true,
        'no_spaces_after_function_name'       => true,
        'no_spaces_around_offset'             => true,
        'visibility_required'                 => ['elements' => ['method', 'property']],
        'method_chaining_indentation'         => true,
         
        'return_type_declaration'             => ['space_before' => 'none'],
        'no_spaces_inside_parenthesis'        => true,
        'phpdoc_scalar'                       => true,
        'phpdoc_types'                        => true,
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_no_empty_return'              => false,
        'blank_line_before_statement'         => [
            'statements' => ['return', 'if', 'foreach', 'for', 'while', 'try', 'declare', 'switch']
        ],
        'braces' => [ 
            'allow_single_line_closure'                   => true,
            'position_after_functions_and_oop_constructs' => 'next',
            'position_after_control_structures'           => 'next',
            'position_after_anonymous_constructs'         => 'next',
        ],
        'concat_space'            => ['spacing' => 'none'],
        'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
        'phpdoc_align'            => ['align' => 'left'],
        'binary_operator_spaces'  => [
            'default'   => 'align_single_space',
            'operators' => ['=>' => 'align_single_space']
        ],
        'array_indentation'                     => true,
        // Fixer\SquareBracketNewLineFixer::name() => true,
        // PhpCsFixerCustomFixers\Fixer\NoDuplicatedArrayKeyFixer::name() => true,
        // PhpCsFixerCustomFixers\Fixer\NoDuplicatedImportsFixer::name() => true,

    ])
    ->setFinder($finder);
