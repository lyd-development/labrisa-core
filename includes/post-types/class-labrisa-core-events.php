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
	 * Query every published event regardless of date — no event_end_date
	 * filtering at all.
	 *
	 * @since    1.0.0
	 * @param    array    $args    {
	 *     Optional. Query arguments.
	 *
	 *     @type int    $posts_per_page Number of events to return. -1 for all. Default 8.
	 *     @type int    $paged          Page number for pagination. Default 1.
	 *     @type string $orderby        'event_end_date', 'event_date', 'title' or 'date'. Default 'event_date'.
	 *     @type string $order          'ASC' or 'DESC'. Default 'ASC'.
	 *     @type array  $event_types       Term IDs to filter by in the event-types taxonomy.
	 *     @type array  $event_line_up     Term IDs to filter by in the event-line-up taxonomy.
	 *     @type string $date_range        Filter by event_date: '', 'month', '3months', '6months',
	 *                                     'year' (rolling window ending now), or 'custom'
	 *                                     (uses $date_range_start/$date_range_end). Default ''
	 *                                     (no filtering).
	 *     @type string $date_range_start  'Y-m-d H:i:s' lower bound, only used when $date_range is 'custom'.
	 *     @type string $date_range_end    'Y-m-d H:i:s' upper bound, only used when $date_range is 'custom'.
	 *     @type bool   $start_from_current_month  Only meaningful with $orderby 'event_date' and $order
	 *                                     'ASC'. After the normal chronological query, moves events
	 *                                     from the 1st of the current calendar month onward to the
	 *                                     front (still soonest first, like get_upcoming_events()),
	 *                                     pushing older events to the end instead of the start.
	 *                                     Default false.
	 * }
	 * @return   WP_Query
	 */
	public static function get_all_events( array $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'posts_per_page'            => 8,
				'paged'                     => 1,
				'orderby'                   => 'event_date',
				'order'                     => 'ASC',
				'event_types'               => array(),
				'event_line_up'             => array(),
				'date_range'                => '',
				'date_range_start'          => '',
				'date_range_end'            => '',
				'start_from_current_month'  => false,
			)
		);

		$query_args = array(
			'post_type'           => self::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => $args['posts_per_page'],
			'paged'               => $args['paged'],
			'ignore_sticky_posts' => true,
			'order'               => $args['order'],
		);

		if ( in_array( $args['orderby'], array( 'event_end_date', 'event_date' ), true ) ) {
			$query_args['meta_key'] = $args['orderby']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query_args['orderby']  = 'meta_value';
		} else {
			$query_args['orderby'] = $args['orderby'];
		}

		$tax_query = self::build_tax_query( $args['event_types'], $args['event_line_up'] );

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$date_range_query = self::build_date_range_meta_query( $args['date_range'], $args['date_range_start'], $args['date_range_end'] );

		if ( ! empty( $date_range_query ) ) {
			$query_args['meta_query'] = $date_range_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$query = new WP_Query( $query_args );

		if ( $args['start_from_current_month'] && 'event_date' === $args['orderby'] && 'ASC' === strtoupper( $args['order'] ) ) {
			self::rotate_posts_from_current_month( $query );
		}

		return $query;
	}

	/**
	 * Re-partition an already-run, event_date-ASC-ordered WP_Query's
	 * `$posts` array (in place) so events from the 1st of the current
	 * calendar month onward come first — still in the same ascending order
	 * the SQL query already returned — with older events moved to the end
	 * instead of the front. Only makes sense for queries that fetch every
	 * matching row in one go (posts_per_page => -1, no SQL-level paging),
	 * which is how get_all_events() is used today (a carousel, not a
	 * paginated list) — this does not re-sort across separate pages.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    WP_Query $query
	 */
	private static function rotate_posts_from_current_month( WP_Query $query ) {

		if ( empty( $query->posts ) ) {
			return;
		}

		$cutoff = ( new DateTimeImmutable( 'first day of this month midnight', wp_timezone() ) )->format( 'Y-m-d H:i:s' );

		$from_current_month = array();
		$before_current_month = array();

		foreach ( $query->posts as $post ) {
			$event_date = get_post_meta( $post->ID, 'event_date', true );

			if ( $event_date && $event_date >= $cutoff ) {
				$from_current_month[] = $post;
			} else {
				$before_current_month[] = $post;
			}
		}

		$query->posts = array_merge( $from_current_month, $before_current_month );
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
	 *     @type string $date_range          Filter by event_date: '', 'month', '3months', '6months',
	 *                                       'year' (rolling window ending now), or 'custom'
	 *                                       (uses $date_range_start/$date_range_end). Default ''
	 *                                       (no filtering).
	 *     @type string $date_range_start    'Y-m-d H:i:s' lower bound, only used when $date_range is 'custom'.
	 *     @type string $date_range_end      'Y-m-d H:i:s' upper bound, only used when $date_range is 'custom'.
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
				'date_range'          => '',
				'date_range_start'    => '',
				'date_range_end'      => '',
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

			$meta_query[] = array(
				'key'     => 'event_date',
				'value'   => array(
					$now->modify( 'first day of this month' )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' ),
					$now->modify( 'last day of this month' )->setTime( 23, 59, 59 )->format( 'Y-m-d H:i:s' ),
				),
				'compare' => 'BETWEEN',
				'type'    => 'DATETIME',
			);
		}

		$date_range_query = self::build_date_range_meta_query( $args['date_range'], $args['date_range_start'], $args['date_range_end'] );

		if ( ! empty( $date_range_query ) ) {
			$meta_query[] = $date_range_query[0];
		}

		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'AND';
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

		$tax_query = self::build_tax_query( $args['event_types'], $args['event_line_up'] );

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return new WP_Query( $query_args );
	}

	/**
	 * Build a WP_Query-ready tax_query array from event-types/event-line-up
	 * term ID lists. Shared by every query method on this class.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    array    $event_types    Term IDs in the event-types taxonomy.
	 * @param    array    $event_line_up  Term IDs in the event-line-up taxonomy.
	 * @return   array
	 */
	private static function build_tax_query( array $event_types, array $event_line_up ) {

		$tax_query = array();

		if ( ! empty( $event_types ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_EVENT_TYPES,
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', $event_types ),
			);
		}

		if ( ! empty( $event_line_up ) ) {
			$tax_query[] = array(
				'taxonomy' => self::TAX_EVENT_LINE_UP,
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', $event_line_up ),
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}

	/**
	 * Build a WP_Query-ready meta_query clause (as an array containing zero
	 * or one clause) filtering by event_date for a "Date Range" preset.
	 * Shared by every query method on this class that exposes a date-range
	 * filter.
	 *
	 * The rolling presets ('month', '3months', '6months', 'year') are a
	 * window ending "now" (site timezone) and starting N months/years back
	 * — distinct from `current_month_only`, which is calendar-month-boundary
	 * based (1st through last day of the current month) rather than rolling.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string $preset  '', 'month', '3months', '6months', 'year', or 'custom'.
	 * @param    string $start   'Y-m-d H:i:s' lower bound, only used when $preset is 'custom'.
	 * @param    string $end     'Y-m-d H:i:s' upper bound, only used when $preset is 'custom'.
	 * @return   array           Zero or one meta_query clause, ready to merge into a larger meta_query.
	 */
	private static function build_date_range_meta_query( $preset, $start = '', $end = '' ) {

		if ( 'custom' === $preset ) {
			if ( empty( $start ) || empty( $end ) ) {
				return array();
			}

			return array(
				array(
					'key'     => 'event_date',
					'value'   => array( $start, $end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME',
				),
			);
		}

		$intervals = array(
			'month'   => '-1 month',
			'3months' => '-3 months',
			'6months' => '-6 months',
			'year'    => '-1 year',
		);

		if ( ! isset( $intervals[ $preset ] ) ) {
			return array();
		}

		$now = new DateTimeImmutable( 'now', wp_timezone() );

		return array(
			array(
				'key'     => 'event_date',
				'value'   => array(
					$now->modify( $intervals[ $preset ] )->format( 'Y-m-d H:i:s' ),
					$now->format( 'Y-m-d H:i:s' ),
				),
				'compare' => 'BETWEEN',
				'type'    => 'DATETIME',
			),
		);
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
			'event_place'      => get_post_meta( $post_id, 'event_place', true ),
			'event_date'       => get_post_meta( $post_id, 'event_date', true ),
			'event_end_date'   => get_post_meta( $post_id, 'event_end_date', true ),
			'event_ticket_url' => get_post_meta( $post_id, 'event_ticket_url', true ),
		);
	}

	/**
	 * Get an event's terms & conditions / details content, rendered the same
	 * way WordPress renders normal post content (paragraphs, blocks,
	 * shortcodes, embeds all applied via the `the_content` filter chain),
	 * then sanitized for safe HTML output. Replaces the old
	 * `event_terms_and_conditions` ACF field — this content now lives in
	 * the event's regular post_content.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    Event post ID.
	 * @return   string             Rendered, sanitized HTML. Empty string if the post has no content.
	 */
	public static function get_event_content( $post_id ) {
		$event_post = get_post( $post_id );

		if ( ! $event_post || '' === $event_post->post_content ) {
			return '';
		}

		// Some `the_content` filters (galleries/shortcodes/embeds that key
		// off the "current" post) expect the global $post to be set — swap
		// it in temporarily rather than assuming callers are already inside
		// The Loop (this is called from AJAX handlers, which aren't).
		global $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$original_post = $post;
		$post          = $event_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$content = apply_filters( 'the_content', $event_post->post_content );

		if ( $original_post ) {
			$post = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
		} else {
			wp_reset_postdata();
		}

		return wp_kses_post( $content );
	}
}
