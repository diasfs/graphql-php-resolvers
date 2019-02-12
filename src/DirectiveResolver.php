<?php

namespace GraphQL\Resolvers;

use GuzzleHttp\Promise\Promise;

class DirectiveResolver {
    public static function getDirectives($node) {
        $directives = array();
        foreach($node->directives as $directive) {
            $name = $directive->name->value;
            $args = array();
            foreach($directive->arguments as $arg) {
                $args[$arg->name->value] = $arg->value->value;
            }
            $directives[$name] = $args;
        }
        return $directives;
    }

    public static function bind($schema, $resolver) {
        $types = $schema->getTypeMap();
        foreach($types as $type) {
            if (!$type instanceof \GraphQL\Type\Definition\ObjectType) {
                continue;
            }
            foreach($type->getFields() as $field) {
                if (!is_object($field)) continue;
                if (!is_object($field->astNode)) continue;

                $schema_directives = static::getDirectives($field->astNode);

                $original =  $field->resolveFn;
                if (!$original) {
                    $original = $type->resolveFieldFn;
                }
                if (!$original) {
                    $original = function($value, $args, $ctx, $info) {
                        return \GraphQL\Executor\Executor::defaultFieldResolver($value, $args, $ctx, $info);
                    };
                }

                $field->resolveFn = function($value, $args, $ctx, $info) use ($schema_directives, $original, $resolver) {
                    $field_directives = static::getDirectives($info->fieldNodes[0]);

                    $original = function($value, $args, $ctx, $info) use ($original) {
                        $p = new Promise();
                        $value = $original($value, $args, $ctx, $info);
                        $p->resolve($value);
                        return $p;
                    };

                    $p = $original;
                    foreach(array($schema_directives, $field_directives ) as $directives) {
                        foreach($directives as $directive => $directive_args) {
                            $fn = $resolver($directive);
                            $p = function($value, $args, $ctx, $info) use ($p, $directive_args, $fn) {
                                return call_user_func_array($fn, array($p, $directive_args, $value, $args, $ctx, $info));
                            };
                        }
                    }

                    return $p($value, $args, $ctx, $info)->wait();
                };
            }
        }
    }
}