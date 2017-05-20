WP GraphQL Meta
===============

This plugin is an add-on for the awesome [WP GraphQL][wp-graphql]

It builds on top of both WP GraphQL and the REST API. Any
meta data you register using `register_meta()` will be available
to retrieve in your results. You get your data in the REST API and
GraphQL at the same time.

## Installing

1. Make sure that [WP GraphQL][wp-graphql] is installed and activated first.
2. Upload this repo (or git clone) to your plugins folder and activate it.

## Usage

Your theme or other plugins may use meta data to add custom functionality. That
data or functionality can be beneficial / required for a front end or
other app that consumes your API.

```php
register_meta( 'post', 'custom-key', array(
    'type' => 'string', // number, boolean, integer or a type from WPGraphQL\Types
    'description' => 'My custom field',
    'single' => true,   // Whether to make this require a list or not.
    'show_in_rest' => true, // Required to make this field public.
) );
```

NOTE: If you use `WPGraphQL\Types` instance the built in REST API will
ignore the field.

[wp-graphql]: https://github.com/wp-graphql/wp-graphql
