<?php

/**
 * Elementor "Upcoming Events" widget.
 *
 * Displays events whose event_end_date has not passed yet (site timezone)
 * as an infinite-loop marquee carousel of their event_banner_image, each
 * slide offering a "Book Now" link (event_ticket_url) and an "Explore More"
 * button that opens a popup with the event's details.
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
 * Class Labrisa_Core_Elementor_Widget_Upcoming_Events
 *
 * @since      1.0.0
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes/elementor/widgets
 */
class Labrisa_Core_Elementor_Widget_Upcoming_Events extends \Elementor\Widget_Base {

	public function get_name() {
		return 'labrisa-upcoming-events';
	}

	public function get_title() {
		return __( 'Upcoming Events', 'labrisa-core' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return array( Labrisa_Core_Elementor::CATEGORY );
	}

	public function get_keywords() {
		return array( 'event', 'events', 'upcoming event', 'carousel', 'marquee', 'slider', 'popup' );
	}

	public function get_style_depends() {
		return array( 'labrisa-core-upcoming-events' );
	}

	public function get_script_depends() {
		return array( 'labrisa-core-upcoming-events' );
	}

	protected function register_controls() {
		$this->register_query_controls();
		$this->register_content_controls();
		$this->register_marquee_controls();
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
				'default' => -1,
				'min'     => -1,
				'max'     => 100,
				'description' => __( 'Use -1 to show all upcoming events.', 'labrisa-core' ),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'event_date',
				'options' => array(
					'event_date'     => __( 'Event Start Date', 'labrisa-core' ),
					'event_end_date' => __( 'Event End Date', 'labrisa-core' ),
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
				'default' => 'ASC',
				'options' => array(
					'ASC'  => __( 'Soonest First', 'labrisa-core' ),
					'DESC' => __( 'Latest First', 'labrisa-core' ),
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
			'current_month_only',
			array(
				'label'       => __( 'This Month Only', 'labrisa-core' ),
				'description' => __( 'Only show events whose date falls within the current calendar month.', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'default'     => '',
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content Tab: how each slide is displayed.
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
			'show_place',
			array(
				'label'   => __( 'Show Event Place', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'heading_buttons',
			array(
				'label'     => __( 'Buttons', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_book_now',
			array(
				'label'       => __( 'Show "Book Now" Button', 'labrisa-core' ),
				'description' => __( 'Only shown on slides that have a Ticket URL set.', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'book_now_label',
			array(
				'label'     => __( 'Book Now Label', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Book Now', 'labrisa-core' ),
				'condition' => array(
					'show_book_now' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_explore',
			array(
				'label'   => __( 'Show "Explore More" Button', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'explore_label',
			array(
				'label'     => __( 'Explore More Label', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Explore More', 'labrisa-core' ),
				'condition' => array(
					'show_explore' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content Tab: Swiper carousel controls (no autoplay — navigation only).
	 */
	protected function register_marquee_controls() {
		$this->start_controls_section(
			'section_marquee',
			array(
				'label' => __( 'Carousel', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_navigation',
			array(
				'label'   => __( 'Navigation Arrows', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'       => __( 'Infinite Loop', 'labrisa-core' ),
				'description' => __( 'Wrap back to the first/last event when navigating past an edge.', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'default'     => 'yes',
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
					'{{WRAPPER}} .labrisa-marquee' => '--labrisa-marquee-columns: {{VALUE}};',
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
					'size' => 24,
				),
				'selectors'  => array(
					'{{WRAPPER}} .labrisa-marquee' => '--labrisa-marquee-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'     => __( 'Image Ratio', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '1.33',
				'options'   => array(
					'1'    => '1:1',
					'0.8'  => '4:5',
					'1.33' => '4:3',
					'1.78' => '16:9',
					'auto' => __( 'Original', 'labrisa-core' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .labrisa-marquee' => '--labrisa-marquee-ratio: {{VALUE}};',
				),
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
					'{{WRAPPER}} .labrisa-event-slide__media' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_navigation',
			array(
				'label'     => __( 'Navigation', 'labrisa-core' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'enable_navigation' => 'yes',
				),
			)
		);

		$this->add_control(
			'nav_color',
			array(
				'label'     => __( 'Arrow Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#111111',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-marquee__nav-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'nav_bg_color',
			array(
				'label'     => __( 'Background Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-marquee__nav-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'nav_border_color',
			array(
				'label'     => __( 'Border Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.15)',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-marquee__nav-btn' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
			array(
				'label' => __( 'Title & Meta', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-slide__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .labrisa-event-slide__title',
			)
		);

		$this->add_control(
			'meta_color',
			array(
				'label'     => __( 'Date & Place Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.8)',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-slide__meta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_buttons',
			array(
				'label' => __( 'Buttons', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'book_now_bg_color',
			array(
				'label'     => __( 'Book Now Background', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#111111',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-slide__btn--primary' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'book_now_text_color',
			array(
				'label'     => __( 'Book Now Text Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-slide__btn--primary' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'explore_text_color',
			array(
				'label'     => __( 'Explore More Text Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-slide__btn--secondary' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'explore_border_color',
			array(
				'label'     => __( 'Explore More Border Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.6)',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-slide__btn--secondary' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'buttons_typography',
				'selector' => '{{WRAPPER}} .labrisa-event-slide__btn',
			)
		);

		$this->add_control(
			'buttons_radius',
			array(
				'label'      => __( 'Button Border Radius', 'labrisa-core' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .labrisa-event-slide__btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_popup',
			array(
				'label'     => __( 'Explore Popup', 'labrisa-core' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_explore' => 'yes',
				),
			)
		);

		$this->add_control(
			'popup_bg_color',
			array(
				'label'     => __( 'Background Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-modal__dialog' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'popup_title_color',
			array(
				'label'     => __( 'Title Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#111111',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-modal__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'popup_cta_bg_color',
			array(
				'label'     => __( 'CTA Button Background', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#111111',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-modal__cta' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'popup_cta_text_color',
			array(
				'label'     => __( 'CTA Button Text Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-event-modal__cta' => 'color: {{VALUE}};',
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

	protected function render() {
		$settings = $this->get_settings_for_display();

		$query = Labrisa_Core_Events::get_upcoming_events(
			array(
				'posts_per_page'      => $settings['posts_per_page'],
				'orderby'             => $settings['orderby'],
				'order'               => $settings['order'],
				'event_types'         => $settings['event_types'],
				'event_line_up'       => $settings['event_line_up'],
				'current_month_only'  => 'yes' === $settings['current_month_only'],
			)
		);

		if ( ! $query->have_posts() ) {
			printf(
				'<div class="labrisa-events-empty">%s</div>',
				esc_html__( 'No upcoming events found.', 'labrisa-core' )
			);
			return;
		}

		$posts = $query->posts;

		// Swiper's loop mode clones slides internally to fake the wrap-
		// around; with very few real slides its clone math can run out of
		// room once the visible area (wider on desktop) approaches or
		// exceeds the real slide count, which breaks slideNext() at larger
		// viewports even though it works fine on mobile. Giving it more
		// real DOM slides to loop through sidesteps that entirely.
		if ( 'yes' === $settings['loop'] && count( $posts ) > 0 && count( $posts ) < 6 ) {
			$original_posts = $posts;
			while ( count( $posts ) < 6 ) {
				$posts = array_merge( $posts, $original_posts );
			}
		}
		?>
		<div class="labrisa-upcoming-events">
			<div class="labrisa-marquee">
				<div
					class="swiper labrisa-marquee__swiper"
					data-loop="<?php echo esc_attr( $settings['loop'] ); ?>"
				>
					<div class="swiper-wrapper">
						<?php
						foreach ( $posts as $post ) {
							setup_postdata( $post );
							$this->render_event_slide( $post->ID, $settings );
						}
						wp_reset_postdata();
						?>
					</div>
				</div>
				<?php if ( 'yes' === $settings['enable_navigation'] ) : ?>
					<div class="labrisa-marquee__nav">
						<button type="button" class="labrisa-marquee__nav-btn labrisa-marquee__nav-btn--prev" data-labrisa-prev aria-label="<?php esc_attr_e( 'Previous', 'labrisa-core' ); ?>">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 3L5 8L10 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
						<button type="button" class="labrisa-marquee__nav-btn labrisa-marquee__nav-btn--next" data-labrisa-next aria-label="<?php esc_attr_e( 'Next', 'labrisa-core' ); ?>">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3L11 8L6 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( 'yes' === $settings['show_explore'] ) : ?>
				<?php $this->render_explore_modal(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param int   $post_id
	 * @param array $settings
	 */
	private function render_event_slide( $post_id, $settings ) {
		$image_id = Labrisa_Core_Events::get_event_image_id( $post_id, 'event_banner_image' );
		$meta     = Labrisa_Core_Events::get_event_meta( $post_id );
		$title    = get_the_title( $post_id );

		$date_display = '';
		if ( ! empty( $meta['event_date'] ) ) {
			$date_display = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $meta['event_date'] );
		}
		?>
		<div class="swiper-slide labrisa-event-slide">
			<div class="labrisa-event-slide__media">
				<?php if ( $image_id ) : ?>
					<?php echo wp_get_attachment_image( $image_id, $settings['image_size'], false, array( 'class' => 'labrisa-event-slide__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output is already escaped. ?>
				<?php endif; ?>
				<span class="labrisa-event-slide__overlay"></span>
				<div class="labrisa-event-slide__body">
					<?php if ( 'yes' === $settings['show_title'] ) : ?>
						<span class="labrisa-event-slide__title"><?php echo esc_html( $title ); ?></span>
					<?php endif; ?>
					<?php if ( ( 'yes' === $settings['show_date'] && $date_display ) || ( 'yes' === $settings['show_place'] && $meta['event_place'] ) ) : ?>
						<span class="labrisa-event-slide__meta">
							<?php
							$meta_parts = array();
							if ( 'yes' === $settings['show_date'] && $date_display ) {
								$meta_parts[] = $date_display;
							}
							if ( 'yes' === $settings['show_place'] && $meta['event_place'] ) {
								$meta_parts[] = $meta['event_place'];
							}
							echo esc_html( implode( ' • ', $meta_parts ) );
							?>
						</span>
					<?php endif; ?>
					<?php if ( ( 'yes' === $settings['show_book_now'] && ! empty( $meta['event_ticket_url'] ) ) || 'yes' === $settings['show_explore'] ) : ?>
						<span class="labrisa-event-slide__actions">
							<?php if ( 'yes' === $settings['show_book_now'] && ! empty( $meta['event_ticket_url'] ) ) : ?>
								<a
									class="labrisa-event-slide__btn labrisa-event-slide__btn--primary"
									href="<?php echo esc_url( $meta['event_ticket_url'] ); ?>"
									target="_blank"
									rel="noopener noreferrer"
								>
									<?php echo esc_html( $settings['book_now_label'] ); ?>
								</a>
							<?php endif; ?>
							<?php if ( 'yes' === $settings['show_explore'] ) : ?>
								<button
									type="button"
									class="labrisa-event-slide__btn labrisa-event-slide__btn--secondary"
									data-labrisa-explore="<?php echo esc_attr( $this->get_explore_payload( $post_id, $title, $date_display, $image_id, $meta ) ); ?>"
								>
									<?php echo esc_html( $settings['explore_label'] ); ?>
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5" style="width: 14px;
    padding-left: 4px;">
  <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
</svg>

								</button>
							<?php endif; ?>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Build the JSON payload embedded on each "Explore More" button and read
	 * by public/js/labrisa-core-upcoming-events.js to populate the popup.
	 *
	 * @param int    $post_id
	 * @param string $title
	 * @param string $date_display
	 * @param int    $image_id
	 * @param array  $meta
	 * @return string
	 */
	private function get_explore_payload( $post_id, $title, $date_display, $image_id, $meta ) {
		$payload = array(
			'title'     => $title,
			'place'     => $meta['event_place'],
			'date'      => $date_display,
			'image'     => $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '',
			'terms'     => ! empty( $meta['event_terms_and_conditions'] ) ? wp_kses_post( wpautop( $meta['event_terms_and_conditions'] ) ) : '',
			'ticketUrl' => $meta['event_ticket_url'],
		);

		return wp_json_encode( $payload );
	}

	/**
	 * Render the single popup shell reused by every "Explore More" click on
	 * this widget instance. public/js/labrisa-core-upcoming-events.js fills
	 * it in and moves it to the end of <body> on first use.
	 */
	private function render_explore_modal() {
		?>
		<div class="labrisa-event-modal" data-labrisa-event-modal hidden>
			<div class="labrisa-event-modal__backdrop" data-labrisa-event-modal-close></div>
			<div class="labrisa-event-modal__dialog" role="dialog" aria-modal="true">
				<button type="button" class="labrisa-event-modal__close" data-labrisa-event-modal-close aria-label="<?php esc_attr_e( 'Close', 'labrisa-core' ); ?>">&times;</button>
				<div class="labrisa-event-modal__media">
					<img src="" alt="" hidden>
				</div>
				<div class="labrisa-event-modal__body">
					<h3 class="labrisa-event-modal__title"></h3>
					<div class="labrisa-event-modal__meta"></div>
					<div class="labrisa-event-modal__terms"></div>
					<a class="labrisa-event-modal__cta" href="#" target="_blank" rel="noopener noreferrer" hidden>
						<?php esc_html_e( 'Book Now', 'labrisa-core' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}
