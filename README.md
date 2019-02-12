# GraphQL Resolvers
[![License](https://poser.pugx.org/ecodev/graphql-upload/license.png)](https://packagist.org/packages/diasfs/graphql-php-resolvers)
Simplified resolvers for [webonyx/graphql-php](https://github.com/webonyx/graphql-php).
## Quick start

Install the library via composer:

```sh
composer require diasfs/graphql-php-resolvers
```

### Field Resolver

```php
require __DIR__ . "/vendor/autoload.php";
use GraphQL\Utils\BuildSchema;
use GraphQL\GraphQL;
use GraphQL\Resolvers\FieldResolver;

$schema_gql = <<<gql
schema {
    query Query
}

type Query {
    hello:String
}
gql;

$data = array(
    'Query' => array(
        'hello' => "Hello World!"
    )
);

$schema = BuildSchema::build($schema_gql, function($typeConfig) use ($data) {

    return FieldResolver::TypeConfigDecorator($typeConfig, function($type_name, $field_name) use ($data) {
        return $data[$type_name][$field_name];
    });

});

$query = <<<gql
query {
    hello
}
gql;

$result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues, $operationName);
$result = $result->toArray();

print_r($result);
```
Result:
```php
Array
(
    [data] => Array
        (
            [hello] => Hello World!
        )
)
```

### Directive Resolver

```php
require __DIR__ . "/vendor/autoload.php";
use GraphQL\Utils\BuildSchema;
use GraphQL\GraphQL;
use GraphQL\Resolvers\FieldResolver;
use GraphQL\Resolvers\DirectiveResolver;

$schema_gql = <<<gql
directive @upper on FIELD_DEFINITION | FIELD
directive @lower on FIELD_DEFINITION | FIELD

schema {
    query Query
}

type Query {
    hello:String @upper
    world:String
}
gql;

$data = array(
    'Query' => array(
        'hello' => "Hello",
        'world' => "World",
    )
);

$schema = BuildSchema::build($schema_gql, function($typeConfig) use ($data) {

    return FieldResolver::TypeConfigDecorator($typeConfig, function($type_name, $field_name) use ($data) {
        return $data[$type_name][$field_name];
    });

});

$directives = array(
    'upper' => function($next, $directiveArgs, $value, $args, $context, $info) {
        return $next($value, $args, $ctx, $info)->then(function($str) {
            return strtolupper($str);
        });
    },
    'lower' => function($next, $directiveArgs, $value, $args, $context, $info) {
        return $next($value, $args, $ctx, $info)->then(function($str) {
            return strtolower($str);
        });
    }
);

DirectivesResolver::bind($schema, function($name) use ($directives) {
    return $directives[$name];
});

$query = <<<gql
query {
    hello
    world @lower
}
gql;

$result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues, $operationName);
$result = $result->toArray();

print_r($result);
```
Result:
```php
Array
(
    [data] => Array
        (
            [hello] => HELLO
            [world] => world
        )
)
```