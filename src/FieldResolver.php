<?php

namespace GraphQL\Resolvers;

class FieldResolver {
    public static function ResolveField($value, $args, $ctx, $info, $resolver) {
        $name = $info->fieldName;
        $node = $info->fieldNodes[0];

        $fn = $resolver($info->parentType->name, $name);
        if (is_callable($fn)) {
            $value = call_user_func_array($fn, array($value, $args, $ctx, $info));
        }

        return $value;
    }

    public static function TypeConfigDecorator($typeConfig, $resolver) {

        $typeConfig['resolveField'] = function($value, $args, $ctx, $info) use ($resolver) {
            return static::ResolveField($value, $args, $ctx, $info, $resolver);
        };

        return $typeConfig;
    }
}