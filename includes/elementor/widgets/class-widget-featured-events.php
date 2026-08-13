<?php

/**
 * Elementor "Featured Events" widget.
 *
 * Same data source as the Upcoming Events widget (Labrisa_Core_Events::get_upcoming_events(),
 * same taxonomy filter controls), but a completely different layout: one
 * event fully visible at a time — event_banner_image on one side, title +
 * full description (post_content via get_event_content()) + place/date meta
 * + a ticket button on the other — in an optionally autoplaying Swiper
 * carousel, one event per slide. No card grid, no "Explore" popup — unlike
 * Past/Upcoming/All/Regular Events, everything is shown directly on the slide.
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
 * Class Labrisa_Core_Elementor_Widget_Featured_Events
 *
 * @since      1.0.0
 * @package    Labrisa_Core
 * @subpackage Labrisa_Core/includes/elementor/widgets
 */
class Labrisa_Core_Elementor_Widget_Featured_Events extends \Elementor\Widget_Base {

	public function get_name() {
		return 'labrisa-featured-events';
	}

	public function get_title() {
		return __( 'Featured Events', 'labrisa-core' );
	}

	public function get_icon() {
		return 'eicon-star';
	}

	public function get_categories() {
		return array( Labrisa_Core_Elementor::CATEGORY );
	}

	public function get_keywords() {
		return array( 'event', 'events', 'featured', 'carousel', 'autoplay', 'slider' );
	}

	public function get_style_depends() {
		return array( 'labrisa-core-featured-events' );
	}

	public function get_script_depends() {
		return array( 'labrisa-core-featured-events' );
	}

	protected function register_controls() {
		$this->register_query_controls();
		$this->register_content_controls();
		$this->register_carousel_controls();
		$this->register_style_controls();
	}

	/**
	 * Content Tab: which events to pull in. Same shape as Upcoming Events'
	 * Query section, since this widget shares the same data source.
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
				'label'       => __( 'Number of Events', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 5,
				'min'         => -1,
				'max'         => 50,
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
					'ASC'  => __( 'Earliest First', 'labrisa-core' ),
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
			'event_brands',
			array(
				'label'       => __( 'Filter by Brand', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => array(),
				'options'     => $this->get_taxonomy_options( Labrisa_Core_Events::TAX_EVENT_BRANDS ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content Tab: what shows on each slide.
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
			'image_position',
			array(
				'label'   => __( 'Image Position', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'left',
				'options' => array(
					'left'  => __( 'Left', 'labrisa-core' ),
					'right' => __( 'Right', 'labrisa-core' ),
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
					'{{WRAPPER}} .labrisa-featured-slide' => '--labrisa-featured-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'show_place',
			array(
				'label'     => __( 'Show Event Place', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
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
			'show_ticket_button',
			array(
				'label'       => __( 'Show Ticket Button', 'labrisa-core' ),
				'description' => __( 'Only shown on slides that have a Ticket URL set.', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'default'     => 'yes',
			)
		);

		$this->add_control(
			'ticket_button_label',
			array(
				'label'     => __( 'Ticket Button Label', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Buy Tickets Here', 'labrisa-core' ),
				'condition' => array(
					'show_ticket_button' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content Tab: Swiper carousel controls, including autoplay.
	 */
	protected function register_carousel_controls() {
		$this->start_controls_section(
			'section_carousel',
			array(
				'label' => __( 'Carousel', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_autoplay',
			array(
				'label'   => __( 'Autoplay', 'labrisa-core' ),
				'type'    => \Elementor\Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => __( 'Autoplay Delay (ms)', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 5000,
				'min'       => 1000,
				'max'       => 20000,
				'step'      => 500,
				'condition' => array(
					'enable_autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'     => __( 'Pause on Hover', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array(
					'enable_autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'enable_navigation',
			array(
				'label'     => __( 'Navigation Arrows', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'       => __( 'Infinite Loop', 'labrisa-core' ),
				'description' => __( 'Wrap back to the first/last event when navigating or autoplaying past an edge.', 'labrisa-core' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'default'     => 'yes',
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
					'{{WRAPPER}} .labrisa-featured-slide__media' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
			array(
				'label' => __( 'Title, Description & Meta', 'labrisa-core' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .labrisa-featured-slide__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .labrisa-featured-slide__description',
			)
		);

		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Description Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.8)',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__description' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'meta_typography',
				'selector' => '{{WRAPPER}} .labrisa-featured-slide__meta',
			)
		);

		$this->add_control(
			'meta_color',
			array(
				'label'     => __( 'Meta Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.8)',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__meta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_button',
			array(
				'label'     => __( 'Ticket Button', 'labrisa-core' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_ticket_button' => 'yes',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .labrisa-featured-slide__btn',
			)
		);

		$this->add_control(
			'button_radius',
			array(
				'label'      => __( 'Border Radius', 'labrisa-core' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .labrisa-featured-slide__btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => __( 'Normal', 'labrisa-core' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Text Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_bg_color',
			array(
				'label'     => __( 'Background Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_border_color',
			array(
				'label'     => __( 'Border Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__btn' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => __( 'Hover', 'labrisa-core' ),
			)
		);

		$this->add_control(
			'button_hover_text_color',
			array(
				'label'     => __( 'Text Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_bg_color',
			array(
				'label'     => __( 'Background Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_border_color',
			array(
				'label'     => __( 'Border Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-slide__btn:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

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

		$this->start_controls_tabs( 'tabs_nav_style' );

		$this->start_controls_tab(
			'tab_nav_normal',
			array(
				'label' => __( 'Normal', 'labrisa-core' ),
			)
		);

		$this->add_control(
			'nav_color',
			array(
				'label'     => __( 'Arrow Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#111111',
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-nav-btn' => 'color: {{VALUE}};',
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
					'{{WRAPPER}} .labrisa-featured-nav-btn' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .labrisa-featured-nav-btn' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_nav_hover',
			array(
				'label' => __( 'Hover', 'labrisa-core' ),
			)
		);

		$this->add_control(
			'nav_hover_color',
			array(
				'label'     => __( 'Arrow Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-nav-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'nav_hover_bg_color',
			array(
				'label'     => __( 'Background Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-nav-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'nav_hover_border_color',
			array(
				'label'     => __( 'Border Color', 'labrisa-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .labrisa-featured-nav-btn:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

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

	protected function render() {
		$settings = $this->get_settings_for_display();

		$query = Labrisa_Core_Events::get_upcoming_events(
			array(
				'posts_per_page' => $settings['posts_per_page'],
				'orderby'        => $settings['orderby'],
				'order'          => $settings['order'],
				'event_types'    => $settings['event_types'],
				'event_line_up'  => $settings['event_line_up'],
				'event_brands'   => $settings['event_brands'],
			)
		);

		if ( ! $query->have_posts() ) {
			printf(
				'<div class="labrisa-events-empty">%s</div>',
				esc_html__( 'No upcoming events found.', 'labrisa-core' )
			);
			return;
		}

		$posts       = $query->posts;
		$wrapper_class = 'labrisa-featured-events';

		if ( 'right' === $settings['image_position'] ) {
			$wrapper_class .= ' labrisa-featured-events--image-right';
		}
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>">
			<?php if ( 'yes' === $settings['enable_navigation'] && count( $posts ) > 1 ) : ?>
				<div class="labrisa-featured-nav">
					<button type="button" class="labrisa-featured-nav-btn labrisa-featured-nav-btn--prev" data-labrisa-featured-prev aria-label="<?php esc_attr_e( 'Previous', 'labrisa-core' ); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 3L5 8L10 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<button type="button" class="labrisa-featured-nav-btn labrisa-featured-nav-btn--next" data-labrisa-featured-next aria-label="<?php esc_attr_e( 'Next', 'labrisa-core' ); ?>">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 3L11 8L6 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				</div>
			<?php endif; ?>
			<div
				class="swiper labrisa-featured-events__swiper"
				data-loop="<?php echo esc_attr( $settings['loop'] ); ?>"
				data-autoplay="<?php echo esc_attr( $settings['enable_autoplay'] ); ?>"
				data-autoplay-speed="<?php echo esc_attr( $settings['autoplay_speed'] ); ?>"
				data-pause-on-hover="<?php echo esc_attr( $settings['pause_on_hover'] ); ?>"
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
		$content  = Labrisa_Core_Events::get_event_content( $post_id );

		$date_display = '';
		if ( ! empty( $meta['event_date'] ) ) {
			$date_display = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $meta['event_date'] );
		}
		?>
		<div class="swiper-slide labrisa-featured-slide">
			<div class="labrisa-featured-slide__media">
				<?php if ( $image_id ) : ?>
					<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'labrisa-featured-slide__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output is already escaped. ?>
				<?php endif; ?>
			</div>
			<div class="labrisa-featured-slide__content">
				<h3 class="labrisa-featured-slide__title"><?php echo esc_html( $title ); ?></h3>

				<?php if ( $content ) : ?>
					<div class="labrisa-featured-slide__description"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_event_content() already runs wp_kses_post(). ?></div>
				<?php endif; ?>

				<?php if ( ( 'yes' === $settings['show_place'] && $meta['event_place'] ) || ( 'yes' === $settings['show_date'] && $date_display ) ) : ?>
					<div class="labrisa-featured-slide__meta">
						<?php if ( 'yes' === $settings['show_place'] && $meta['event_place'] ) : ?>
							<span class="labrisa-featured-slide__meta-row"><?php echo esc_html( $meta['event_place'] ); ?></span>
						<?php endif; ?>
						<?php if ( 'yes' === $settings['show_date'] && $date_display ) : ?>
							<span class="labrisa-featured-slide__meta-row"><?php echo esc_html( $date_display ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['show_ticket_button'] && ! empty( $meta['event_ticket_url'] ) ) : ?>
					<a
						class="labrisa-featured-slide__btn"
						href="<?php echo esc_url( $meta['event_ticket_url'] ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php echo esc_html( $settings['ticket_button_label'] ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
