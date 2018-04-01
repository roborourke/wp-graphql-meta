<?php
/**
 * Plugin Name: WP GraphQL Meta
 * Description: Adds registered meta data to objects by leveraging the existing register_meta() information.
 * Author: Robert O'Rourke
 * Author URI: https://röb.co
 */

namespace WPGraphQL\Extensions\Meta;

/**
 * Get a collection of registered post types and taxonomies
 * then run them through the GraphQL fields filter.
 */
add_action( 'graphql_init', function() {
	/**
	 * Gather a summary of post types and taxonomies with their registration
	 * details. This allows us to inspect for graphql_single_name. The user model
	 * is fixed so we just use null.
	 */
	$all_types = array_merge(
		array_map( 'get_post_type_object', get_post_types() ),
		array_map( 'get_taxonomy', get_taxonomies() ),
		array( 'user' => null )
	);

	foreach ( $all_types as $type => $object ) {
		$graphql_type = $type;

		// Use the graphql_single_name if it has been registered. Otherwise the
		// filter will not work.
		if ( isset( $object->graphql_single_name ) ) {
			$graphql_type = $object->graphql_single_name;
		}

		add_filter( "graphql_{$graphql_type}_fields", function ( $fields ) use ( $type ) {
			return add_meta_fields( $fields, $type );
		} );
	}

} );


/**
 * Adds the meta fields for this $object_type registered using
 * register_meta().
 *
 * @param array $fields
 * @param string $object_type
 * @return array
 * @throws Exception If a meta key is the same as a default key warn the dev.
 */
function add_meta_fields( $fields, $object_type ) {

	$meta_keys = get_registered_meta_keys( $object_type );

	if ( ! empty( $meta_keys ) ) {
		foreach ( $meta_keys as $key => $field_args ) {

			if ( isset( $fields[ $key ] ) ) {
				throw new \Exception( sprintf( 'Post meta key "%s" is a reserved word.', $key ) );
			}

			if ( ! $field_args['show_in_rest'] ) {
				continue;
			}

			$fields[ $key ] = array(
				'type'        => resolve_meta_type( $field_args['type'], $field_args['single'] ),
				'description' => $field_args['description'],
				'resolve'     => function( $object ) use ( $object_type, $key, $field_args ) {
					if ( 'post' === $object_type || in_array( $object_type, get_post_types(), true ) ) {
						return get_post_meta( $object->ID, $key, $field_args['single'] );
					}
					if ( 'term' === $object_type || in_array( $object_type, get_taxonomies(), true ) ) {
						return get_term_meta( $object->term_id, $key, $field_args['single'] );
					}
					if ( 'user' === $object_type ) {
						return get_user_meta( $object->ID, $key, $field_args['single'] );
					}
					return '';
				},
			);
		}
	}

	return $fields;
}

/**
 * Resolves REST API types to meta data types.
 *
 * @param \GraphQL\Type\Definition\AbstractType $type
 * @param bool $single
 * @return mixed
 */
function resolve_meta_type( $type, $single = true ) {
	if ( $type instanceof \GraphQL\Type\Definition\AbstractType ) {
		return $type;
	}

	switch ( $type ) {
		case 'integer':
			$type = \WPGraphQL\Types::int();
			break;
		case 'number':
			$type = \WPGraphQL\Types::float();
			break;
		case 'boolean':
			$type = \WPGraphQL\Types::boolean();
			break;
		default:
			$type = apply_filters( "graphql_{$type}_type", \WPGraphQL\Types::string(), $type );
	}

	return $single ? $type : \WPGraphQL\Types::list_of( $type );
}
