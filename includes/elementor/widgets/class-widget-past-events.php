<?php

/**
 * Elementor "Past Events" widget.
 *
 * Displays events whose event_end_date has passed (site timezone) as an
 * image grid or masonry layout of their event_past_image_square, opening
 * in Elementor's native lightbox on click.
 *
 * @link       https://lydbaligroup.com
 * @since      1.0.0
 *
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes/elementor/widgets
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Labrisa_Core_Elementor_Widget_Past_Events
 *
 * @since      1.0.0
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes/elementor/widgets
 */
class Labrisa_Core_Elementor_Widget_Past_Events extends \Elementor\Widget_Base {

	public function get_name() {
		return 'labrisa-past-events';
	}

	public function get_title() {
		return __( 'Past Events', 'labrisa-core' );
	}

	public function get_icon() {
		return 'eicon-posts-masonry';
	}

	public function get_categories() {
		return array( Labrisa_Core_Elementor::CATEGORY );
	}

	public function get_keywords() {
		return array( 'event', 'events', 'past event', 'gallery', 'grid', 'masonry', 'lightbox' );
	}

	public function get_style_depends() {
		return array( 'labrisa-core-events' );
	}

	protected function register_controls() {
		$this->register_query_controls();
		$this->register_content_controls();
		$this->register_layout_controls();
		$this->register_style_controls();
	}

	/**
	 * Content Tab: which events to pull in.
	 */
	protected function register_query_controls() {
		$this->start_controls_section(
			'section_query',
			array(
				'label' => __( 'Query', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'Number of Events', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 8,
				'min'     => -1,
				'max'     => 100,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'event_end_date',
				'options' => array(
					'event_end_date' => __( 'Event End Date', 'labrisa-core' ),
					'event_date'     => __( 'Event Start Date', 'labrisa-core' ),
					'title'          => __( 'Title', 'labrisa-core' ),
					'date'           => __( 'Published Date', 'labrisa-core' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => __( 'Newest First', 'labrisa-core' ),
					'ASC'  => __( 'Oldest First', 'labrisa-core' ),
				),
			)
		);

		$this->add_control(
			'event_types',
			array(
				'label'       => __( 'Filter by Event Type', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => array(),
				'options'     => $this->get_taxonomy_options( Labrisa_Core_Events::TAX_EVENT_TYPES ),
			)
		);

		$this->add_control(
			'event_line_up',
			array(
				'label'       => __( 'Filter by Line Up', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => array(),
				'options'     => $this->get_taxonomy_options( Labrisa_Core_Events::TAX_EVENT_LINE_UP ),
			)
		);

		$this->add_control(
			'date_range',
			array(
				'label'     => __( 'Date Range', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'all',
				'options'   => array(
					'all'     => __( 'All Dates', 'labrisa-core' ),
					'month'   => __( 'Last Month', 'labrisa-core' ),
					'3months' => __( 'Last 3 Months', 'labrisa-core' ),
					'6months' => __( 'Last 6 Months', 'labrisa-core' ),
					'year'    => __( 'Last Year', 'labrisa-core' ),
					'custom'  => __( 'Custom Range', 'labrisa-core' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'date_range_start',
			array(
				'label'     => __( 'Start Date', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::DATE_TIME,
				'picker_options' => array(
					'enableTime' => false,
				),
				'condition' => array(
					'date_range' => 'custom',
				),
			)
		);

		$this->add_control(
			'date_range_end',
			array(
				'label'     => __( 'End Date', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::DATE_TIME,
				'picker_options' => array(
					'enableTime' => false,
				),
				'condition' => array(
					'date_range' => 'custom',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content Tab: how each card is displayed.
	 */
	protected function register_content_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Content', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => __( 'Image Size', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'large',
				'options' => $this->get_image_size_options(),
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'   => __( 'Show Event Title', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label'   => __( 'Show Event Date', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'enable_lightbox',
			array(
				'label'   => __( 'Lightbox', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'link_to_ticket',
			array(
				'label'       => __( 'Link Image to Ticket URL Instead', 'labrisa-core' ),
				'description' => __( 'When enabled and the event has a ticket URL, clicking the image opens the ticket link in a new tab instead of the lightbox.', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'default'     => '',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content Tab: grid/masonry layout controls.
	 */
	protected function register_layout_controls() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Layout', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout_mode',
			array(
				'label'   => __( 'Layout', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'    => __( 'Grid', 'labrisa-core' ),
					'masonry' => __( 'Masonry', 'labrisa-core' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'labrisa-core' ),
				'type'           => \Elementor\Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '3',
				'mobile_default' => '2',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors'      => array(
					'{{WRAPPER}} .labrisa-events-grid' => '--labrisa-events-columns: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => __( 'Gap', 'labrisa-core' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 80,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 16,
				),
				'selectors'  => array(
					'{{WRAPPER}} .labrisa-events-grid' => '--labrisa-events-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'     => __( 'Image Ratio', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'condition' => array(
					'layout_mode' => 'grid',
				),
				'default'   => '1',
				'options'   => array(
					'1'    => '1:1',
					'0.8'  => '4:5',
					'1.33' => '4:3',
					'1.78' => '16:9',
					'auto' => __( 'Original', 'labrisa-core' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .labrisa-events-grid' => '--labrisa-events-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'show_pagination',
			array(
				'label'       => __( 'Pagination', 'labrisa-core' ),
				'description' => __( 'Only shown when there is more than one page of results (based on Number of Events).', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style Tab.
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'section_style_image',
			array(
				'label' => __( 'Image', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'image_radius',
			array(
				'label'      => __( 'Border Radius', 'labrisa-core' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
					'%'  => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .labrisa-event-card__media' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'overlay_color',
			array(
				'label'     => __( 'Hover Overlay Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.45)',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-card__overlay' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_title',
			array(
				'label'     => __( 'Title & Date', 'labrisa-core' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_title' => 'yes',
				),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-card__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .labrisa-event-card__title',
			)
		);

		$this->add_control(
			'date_color',
			array(
				'label'     => __( 'Date Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.8)',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-card__date' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * @param string $taxonomy
	 * @return array term_id => name
	 */
	private function get_taxonomy_options( $taxonomy ) {
		$options = array();

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * @return array size_name => size_name
	 */
	private function get_image_size_options() {
		$sizes   = get_intermediate_image_sizes();
		$sizes[] = 'full';

		return array_combine( $sizes, $sizes );
	}

	/**
	 * Turn the "Date Range" control values into the date_range/*_start/*_end
	 * args Labrisa_Core_Events query methods expect. The DATE_TIME control
	 * is date-only here (enableTime: false) but its stored format can still
	 * include a time portion depending on the browser/locale, so this only
	 * trusts the first 10 characters (Y-m-d) and appends explicit start/end
	 * of day boundaries itself.
	 *
	 * @param array $settings
	 * @return array
	 */
	private function get_date_range_query_args( array $settings ) {
		$start = ! empty( $settings['date_range_start'] ) ? substr( $settings['date_range_start'], 0, 10 ) . ' 00:00:00' : '';
		$end   = ! empty( $settings['date_range_end'] ) ? substr( $settings['date_range_end'], 0, 10 ) . ' 23:59:59' : '';

		return array(
			'date_range'       => 'all' === $settings['date_range'] ? '' : $settings['date_range'],
			'date_range_start' => $start,
			'date_range_end'   => $end,
		);
	}

	/**
	 * GET query var used for this widget instance's pagination, namespaced
	 * by widget ID so multiple Past Events widgets on one page (or this
	 * widget alongside WordPress's own paged content) don't fight over the
	 * same param.
	 *
	 * @return string
	 */
	private function get_pagination_query_var() {
		return 'lb_paged_' . $this->get_id();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$pagination_var = $this->get_pagination_query_var();
		// Read-only pagination nav, not a state-changing request — no nonce needed.
		$paged = isset( $_GET[ $pagination_var ] ) ? max( 1, absint( $_GET[ $pagination_var ] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$query = Labrisa_Core_Events::get_past_events(
			array_merge(
				array(
					'posts_per_page' => $settings['posts_per_page'],
					'orderby'        => $settings['orderby'],
					'order'          => $settings['order'],
					'event_types'    => $settings['event_types'],
					'event_line_up'  => $settings['event_line_up'],
					'paged'          => $paged,
				),
				$this->get_date_range_query_args( $settings )
			)
		);

		if ( ! $query->have_posts() ) {
			printf(
				'<div class="labrisa-events-empty">%s</div>',
				esc_html__( 'No past events found.', 'labrisa-core' )
			);
			return;
		}

		$gallery_id = 'labrisa-events-' . $this->get_id();

		$wrapper_classes = array(
			'labrisa-events-grid',
			'labrisa-events-grid--' . $settings['layout_mode'],
		);
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$this->render_event_card( get_the_ID(), $settings, $gallery_id );
			endwhile;
			?>
		</div>
		<?php
		if ( 'yes' === $settings['show_pagination'] ) {
			$this->render_pagination( $query, $paged, $pagination_var );
		}
		wp_reset_postdata();
	}

	/**
	 * @param WP_Query $query
	 * @param int      $paged
	 * @param string   $pagination_var
	 */
	private function render_pagination( $query, $paged, $pagination_var ) {
		if ( $query->max_num_pages <= 1 ) {
			return;
		}

		$base_url = remove_query_arg( $pagination_var );

		$links = paginate_links(
			array(
				'base'      => add_query_arg( $pagination_var, '%#%', $base_url ),
				'format'    => '',
				'current'   => $paged,
				'total'     => (int) $query->max_num_pages,
				'prev_text' => __( '&laquo; Prev', 'labrisa-core' ),
				'next_text' => __( 'Next &raquo;', 'labrisa-core' ),
				'type'      => 'list',
			)
		);

		if ( $links ) {
			printf(
				'<nav class="labrisa-events-pagination" aria-label="%s">%s</nav>',
				esc_attr__( 'Events pagination', 'labrisa-core' ),
				$links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() output is already escaped.
			);
		}
	}

	/**
	 * @param int    $post_id
	 * @param array  $settings
	 * @param string $gallery_id
	 */
	private function render_event_card( $post_id, $settings, $gallery_id ) {
		$image_id = Labrisa_Core_Events::get_event_image_id( $post_id );

		if ( ! $image_id ) {
			return;
		}

		$image_html = wp_get_attachment_image(
			$image_id,
			$settings['image_size'],
			false,
			array( 'class' => 'labrisa-event-card__image' )
		);
		$full_image = wp_get_attachment_image_src( $image_id, 'full' );
		$meta       = Labrisa_Core_Events::get_event_meta( $post_id );

		$href = $full_image ? $full_image[0] : '';

		$link_attrs = array();

		if ( 'yes' === $settings['link_to_ticket'] && ! empty( $meta['event_ticket_url'] ) ) {
			$href                    = $meta['event_ticket_url'];
			$link_attrs['target']    = '_blank';
			$link_attrs['rel']       = 'noopener noreferrer';
		} elseif ( 'yes' === $settings['enable_lightbox'] && $href ) {
			$link_attrs['data-elementor-open-lightbox']       = 'yes';
			$link_attrs['data-elementor-lightbox-slideshow']  = $gallery_id;
			$link_attrs['data-elementor-lightbox-title']      = get_the_title( $post_id );
		} else {
			$link_attrs['data-elementor-open-lightbox'] = 'no';
		}
		?>
		<div class="labrisa-event-card">
			<a
				class="labrisa-event-card__media"
				href="<?php echo esc_url( $href ); ?>"
				<?php foreach ( $link_attrs as $attr => $value ) : ?>
					<?php echo esc_attr( $attr ); ?>="<?php echo esc_attr( $value ); ?>"
				<?php endforeach; ?>
			>
				<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output is already escaped. ?>
				<span class="labrisa-event-card__overlay"></span>
				<?php if ( 'yes' === $settings['show_title'] || 'yes' === $settings['show_date'] ) : ?>
					<span class="labrisa-event-card__caption">
						<?php if ( 'yes' === $settings['show_title'] ) : ?>
							<span class="labrisa-event-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
						<?php endif; ?>
						<?php if ( 'yes' === $settings['show_date'] && ! empty( $meta['event_date'] ) ) : ?>
							<span class="labrisa-event-card__date"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $meta['event_date'] ) ); ?></span>
						<?php endif; ?>
					</span>
				<?php endif; ?>
			</a>
		</div>
		<?php
	}
}
