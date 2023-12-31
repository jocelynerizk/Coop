<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Xpro_Elementor_Starter_Sites_Elementor' ) ) {
	/**
	 * Xpro_Elementor_Starter_Sites_Elementor
	 */
	class Xpro_Elementor_Starter_Sites_Elementor {
		/**
		 * Main Xpro_Elementor_Starter_Sites_Elementor Instance
		 * Initialize the class and set its properties.
		 *
		 * @return object $instance Xpro_Elementor_Starter_Sites_Elementor Instance
		 * @since    1.0.0
		 */
		public static function instance() {

			// Store the instance locally to avoid private static replication.
			static $instance = null;

			// Only run these methods if they haven't been ran previously.
			if ( null === $instance ) {
				$instance = new self();
			}

			// Always return the instance.
			return $instance;
		}

		/**
		 * Change post id related to elementor to new id
		 *
		 * @param array $item current array of demo list.
		 * @param string $key
		 *
		 * @return void
		 */
		public function elementor_id_import( &$item, $key ) {
			if ( $key == 'id' && ! empty( $item ) && is_numeric( $item ) ) {
				// check if this has been imported before
				$new_meta_val = xpro_elementor_starter_sites_admin()->imported_post_id( $item );
				if ( $new_meta_val ) {
					$item = $new_meta_val;
				}
			}
			if ( $key == 'page' && ! empty( $item ) ) {

				if ( false !== strpos( $item, 'p.' ) ) {
					$new_id = str_replace( 'p.', '', $item );
					// check if this has been imported before
					$new_meta_val = xpro_elementor_starter_sites_admin()->imported_post_id( $new_id );
					if ( $new_meta_val ) {
						$item = 'p.' . $new_meta_val;
					}
				} elseif ( is_numeric( $item ) ) {
					// check if this has been imported before
					$new_meta_val = xpro_elementor_starter_sites_admin()->imported_post_id( $item );
					if ( $new_meta_val ) {
						$item = $new_meta_val;
					}
				}
			}
			if ( $key == 'post_id' && ! empty( $item ) && is_numeric( $item ) ) {
				// check if this has been imported before
				$new_meta_val = xpro_elementor_starter_sites_admin()->imported_post_id( $item );
				if ( $new_meta_val ) {
					$item = $new_meta_val;
				}
			}
			if ( $key == 'url' && ! empty( $item ) && strstr( $item, 'ocalhost' ) ) {
				// check if this has been imported before
				$new_meta_val = xpro_elementor_starter_sites_admin()->imported_post_id( $item );
				if ( $new_meta_val ) {
					$item = $new_meta_val;
				}
			}
			if ( ( $key == 'shortcode' || $key == 'editor' ) && ! empty( $item ) ) {
				// we have to fix the [contact-form-7 id=133] shortcode issue.
				$item = xpro_elementor_starter_sites_admin()->parse_shortcode_meta_content( $item );

			}
		}

		public function elementor_post( $post_id = false ) {

			// regenerate the CSS for this Elementor post
			if ( class_exists( 'Elementor\Core\Files\CSS\Post' ) ) {
				$post_css = new Elementor\Core\Files\CSS\Post( $post_id );
				$post_css->update();
			}
		}

		/**
		 * set and get transient adi_elementor_data_posts
		 * return mix
		 */
		public function elementor_data_posts( $el_post_id = false, $meta_val = false ) {
			$el_posts = get_transient( 'adi_elementor_data_posts' );
			if ( ! is_array( $el_posts ) ) {
				$el_posts = array();
			}
			if ( $el_post_id && $meta_val ) {
				$el_posts[ $el_post_id ] = $meta_val;
				set_transient( 'adi_elementor_data_posts', $el_posts, 60 * 60 * 24 );
			}

			return $el_posts;
		}

		/**
		 * Change post and term id related to elementor meta to new id
		 *
		 * @param array $item current array of demo list.
		 * @param string $key
		 *
		 * @return array
		 */
		public function elementor_data( $elementor_data ) {

			if ( ( is_string( $elementor_data ) && xpro_elementor_starter_sites_admin()->isJson( $elementor_data ) ) ) {
				$elementor_data = json_decode( stripslashes( $elementor_data ), true );
			}

			/*Posts IDS*/
			$replace_post_ids = apply_filters(
				'xpro_elementor_starter_sites_replace_post_ids',
				array(
					'image_id',
					'thumbnail_id',
					'attachment_id',
					'page_id',
					'post_id',
				)
			);

			/*Terms IDS*/
			$replace_term_ids = apply_filters(
				'xpro_elementor_starter_sites_replace_term_ids',
				array(
					'acm_authors_list',
					'categories_selected',
				)
			);

			// Recursively update elementor data.
			foreach ( $elementor_data as $element_id => $element_data ) {
				if ( ! empty( $element_data['elements'] ) ) {
					foreach ( $element_data['elements'] as $el_key => $el_data ) {
						if ( ! empty( $el_data['elements'] ) ) {
							foreach ( $el_data['elements'] as $el_child_key => $child_el_data ) {
								if ( 'widget' === $child_el_data['elType'] ) {
									$settings = $child_el_data['settings'] ?? array();

									if ( ! empty( $settings ) ) {
										foreach ( $settings as $el_set_key => $el_set_data ) {
											if ( in_array( $el_set_key, $replace_post_ids ) ) {
												if ( is_numeric( $el_set_data ) ) {
													$el_set_data = xpro_elementor_starter_sites_admin()->imported_post_id( $el_set_data );
												} elseif ( is_array( $el_set_data ) && ! empty( $el_set_data ) ) {
													$new_set_data = array();
													foreach ( $el_set_data as $el_set_single_data ) {
														if ( is_numeric( $el_set_data ) ) {
															$new_set_data[] = xpro_elementor_starter_sites_admin()->imported_post_id( $el_set_single_data );
														}
													}
													$el_set_data = $new_set_data;
												}
											}
											if ( in_array( $el_set_key, $replace_term_ids ) ) {
												if ( is_numeric( $el_set_data ) ) {
													$el_set_data = xpro_elementor_starter_sites_admin()->imported_term_id( $el_set_data );
												} elseif ( is_array( $el_set_data ) && ! empty( $el_set_data ) ) {
													$new_set_data = array();
													foreach ( $el_set_data as $el_set_single_data ) {
														if ( is_numeric( $el_set_single_data ) ) {
															$new_set_data[] = xpro_elementor_starter_sites_admin()->imported_term_id( $el_set_single_data );
														}
													}
													$el_set_data = $new_set_data;
												}
											}
											$elementor_data[ $element_id ]['elements'][ $el_key ]['elements'][ $el_child_key ]['settings'][ $el_set_key ] = $el_set_data;
										}
									}
								}
							}
						}
					}
				}
			}

			return $elementor_data;
		}

		/**
		 * Change post and term id related to elementor meta to new id
		 *
		 * @param array $item current array of demo list.
		 * @param string $key
		 *
		 * @return void
		 */
		public function process_elementor_posts() {
			$el_posts = $this->elementor_data_posts();
			if ( is_array( $el_posts ) && ! empty( $el_posts ) ) {
				foreach ( $el_posts as $el_post => $el_data ) {
					$el_data = $this->elementor_data( $el_data );
					update_post_meta( $el_post, '_elementor_data', $el_data );
				}
			}
		}
	}
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function xpro_elementor_starter_sites_elementor() {
	return Xpro_Elementor_Starter_Sites_Elementor::instance();
}
