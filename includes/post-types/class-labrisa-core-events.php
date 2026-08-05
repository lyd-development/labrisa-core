<?php

/**
 * Query helpers for the "events" custom post type (registered via ACF, see
 * sources/acf-export-2026-08-05.json).
 *
 * @link       https://lydbaligroup.com
 * @since      1.0.0
 *
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes/post-types
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Centralizes read access to the "events" post type and its ACF fields so
 * that Elementor widgets, dynamic tags, and blocks can share the same query
 * logic instead of duplicating meta/tax query args.
 *
 * @since      1.0.0
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes/post-types
 */
class Labrisa_Core_Events {

	/**
	 * The "events" post type slug.
	 */
	const POST_TYPE = 'events';

	/**
	 * The "event-types" taxonomy slug.
	 */
	const TAX_EVENT_TYPES = 'event-types';

	/**
	 * The "event-line-up" taxonomy slug.
	 */
	const TAX_EVENT_LINE_UP = 'event-line-up';

	/**
	 * Query events whose event_end_date has already passed, according to the
	 * site's configured timezone (Settings > General).
	 *
	 * @since    1.0.0
	 * @param    array    $args    See self::query_events_by_end_date() for the
	 *                             list of supported keys. Defaults to ordering
	 *                             by event_end_date, newest-past-event first.
	 * @return   WP_Query
	 */
	public static function get_past_events( array $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'orderby' => 'event_end_date',
				'order'   => 'DESC',
			)
		);

		return self::query_events_by_end_date( '<', $args );
	}

	/**
	 * Query events whose event_end_date has not passed yet, according to the
	 * site's configured timezone (Settings > General).
	 *
	 * @since    1.0.0
	 * @param    array    $args    See self::query_events_by_end_date() for the
	 *                             list of supported keys. Defaults to ordering
	 *                             by event_date, soonest-upcoming-event first.
	 * @return   WP_Query
	 */
	public static function get_upcoming_events( array $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'orderby' => 'event_date',
				'order'   => 'ASC',
			)
		);

		return self::query_events_by_end_date( '>=', $args );
	}

	/**
	 * Shared query builder behind get_past_events()/get_upcoming_events().
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string    $compare    Meta compare operator against event_end_date ('<' or '>=').
	 * @param    array     $args       {
	 *     Optional. Query arguments.
	 *
	 *     @type int    $posts_per_page Number of events to return. -1 for all. Default 8.
	 *     @type int    $paged          Page number for pagination. Default 1.
	 *     @type string $orderby        'event_end_date', 'event_date', 'title' or 'date'.
	 *     @type string $order          'ASC' or 'DESC'.
	 *     @type array  $event_types         Term IDs to filter by in the event-types taxonomy.
	 *     @type array  $event_line_up       Term IDs to filter by in the event-line-up taxonomy.
	 *     @type bool   $current_month_only  Only include events whose event_date falls within
	 *                                       the current calendar month (site timezone). Default false.
	 * }
	 * @return   WP_Query
	 */
	private static function query_events_by_end_date( $compare, array $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'posts_per_page'      => 8,
				'paged'               => 1,
				'orderby'             => 'event_end_date',
				'order'               => 'DESC',
				'event_types'         => array(),
				'event_line_up'       => array(),
				'current_month_only'  => false,
			)
		);

		$meta_query = array(
			array(
				'key'     => 'event_end_date',
				'value'   => current_time( 'Y-m-d H:i:s' ),
				'compare' => $compare,
				'type'    => 'DATETIME',
			),
		);

		if ( $args['current_month_only'] ) {
			$now = new DateTimeImmutable( 'now', wp_timezone() );

			$meta_query['relation'] = 'AND';
			$meta_query[]           = array(
				'key'     => 'event_date',
				'value'   => array(
					$now->modify( 'first day of this month' )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' ),
					$now->modify( 'last day of this month' )->setTime( 23, 59, 59 )->format( 'Y-m-d H:i:s' ),
				),
				'compare' => 'BETWEEN',
				'type'    => 'DATETIME',
			);
		}

		$query_args = array(
			'post_type'           => self::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => $args['posts_per_page'],
			'paged'               => $args['paged'],
			'ignore_sticky_posts' => true,
			'order'               => $args['order'],
			'meta_query'          => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		);

		if ( in_array( $args['orderby'], array( 'event_end_date', 'event_date' ), true ) ) {
			$query_args['meta_key'] = $args['orderby']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['orderby']  = 'meta_value';
		} else {
			$query_args['orderby'] = $args['orderby'];
		}

		$tax_query = array();

		if ( ! empty( $args['event_types'] ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_EVENT_TYPES,
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', (array) $args['event_types'] ),
			);
		}

		if ( ! empty( $args['event_line_up'] ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_EVENT_LINE_UP,
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', (array) $args['event_line_up'] ),
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return new WP_Query( $query_args );
	}

	/**
	 * Get the attachment ID stored in an ACF image field for an event.
	 *
	 * Reads the raw postmeta value directly so this works whether or not the
	 * ACF plugin is currently active (return_format only affects get_field()).
	 *
	 * @since    1.0.0
	 * @param    int       $post_id   Event post ID.
	 * @param    string    $meta_key  ACF image field name. Default 'event_past_image_square'.
	 * @return   int                  Attachment ID, or 0 if not set.
	 */
	public static function get_event_image_id( $post_id, $meta_key = 'event_past_image_square' ) {
		$image_id = get_post_meta( $post_id, $meta_key, true );

		return $image_id ? absint( $image_id ) : 0;
	}

	/**
	 * Get the raw ACF meta values for an event.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Event post ID.
	 * @return   array
	 */
	public static function get_event_meta( $post_id ) {
		return array(
			'event_place'                => get_post_meta( $post_id, 'event_place', true ),
			'event_date'                 => get_post_meta( $post_id, 'event_date', true ),
			'event_end_date'             => get_post_meta( $post_id, 'event_end_date', true ),
			'event_ticket_url'           => get_post_meta( $post_id, 'event_ticket_url', true ),
			'event_terms_and_conditions' => get_post_meta( $post_id, 'event_terms_and_conditions', true ),
		);
	}
}
