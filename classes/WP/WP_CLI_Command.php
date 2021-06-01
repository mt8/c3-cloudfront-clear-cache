<?php
namespace C3_CloudFront_Cache_Controller\WP;
/**
 * Control C3_CloudFront_Clear_Cache.
 *
 * @author hideokamoto
 */

use WP_CLI;
use C3_CloudFront_Cache_Controller\Invalidation_Service;
use C3_CloudFront_Cache_Controller\Constants;

/**
 * WP-CLI Command to control C3 CloudFront Cache Controller Plugins
 *
 * @class C3_CloudFront_Clear_Cache_Command
 * @since 2.3.0
 */
class WP_CLI_Command extends \WP_CLI_Command {
	/**
	 * Flush All CloudFront Cache
	 *
	 * ## OPTIONS
	 * <post_id>
	 * post_id
	 *
	 * [--force]
	 * Activate Force Clear Mode
	 *
	 * ## EXAMPLES
	 *
	 *     wp c3 flush <post_id>       : Flush cache of the post (ID=<post_id>).
	 *     wp c3 flush 1               : Flush cache of the post (ID=1).
	 *     wp c3 flush 1,2,4           : Flush cache of these posts (ID=1,2,4).
	 *     wp c3 flush all             : Flush All CloudFront Cache.
	 *     wp c3 flush all --force     : Flush All CloudFront Cache.( Force )
	 *
	 * @param string $args: WP-CLI Command Name
	 * @param string $assoc_args: WP-CLI Command Option
	 * @since 2.3.0
	 */
	function flush( $args, $assoc_args ) {
		WP_CLI::line( 'Start to Clear CloudFront Cache...' );
		if ( empty( $args ) ) {
			WP_CLI::error( 'Please input parameter:post_id(numeric) or all' );
			exit;
		}
		list( $type ) = $args;

		$invalidation_service = new Invalidation_Service();
		if ( array_search( 'force', $assoc_args ) ) {
			WP_CLI::line( 'Force Clear Mode' );
			add_filter( 'c3_invalidation_flag', '__return_false' );
		}

		if ( ! isset( $type ) ) {
			WP_CLI::error( 'Please input parameter:post_id(numeric) or all' );
			exit;
		}

		if ( 'all' === $type ) {
			WP_CLI::line( 'Clear Item = All' );
			$result = $invalidation_service->invalidate_all();
		} elseif ( is_numeric( $type ) ) {
			WP_CLI::line( "Clear Item = (post_id={$type})" );
			$post   = get_post( $type );
			$result = $invalidation_service->invalidate_post_cache( $post );
		} else {
			$post_ids = explode( ',', $type );
			$query    = new \WP_Query(
				array(
					'post__in' => $post_ids,
				)
			);
			$posts    = $query->get_posts();
			wp_reset_postdata();
			$result = $invalidation_service->invalidate_posts_cache( $posts, true );
		}
		if ( ! is_wp_error( $result ) ) {
			WP_CLI::success( 'Create Invalidation Request. Please wait few minutes to finished clear CloudFront Cache.' );
		}
	}

	/**
	 * Update C3 CloudFront Cache Controller Settings
	 *
	 * ## OPTIONS
	 * distribution_id
	 *  Update Distribution ID
	 *
	 * access_key
	 *  Update Access Key
	 *
	 * secret_key
	 *  Update Secrete Key
	 *
	 * <Setting Param>
	 *  Update Setting value
	 *
	 * ## EXAMPLES
	 *
	 *     wp c3 update distribution_id <Setting Param>      :Default usage.
	 *     wp c3 update access_key <Setting Param>      :Default usage.
	 *     wp c3 update secret_key <Setting Param>      :Default usage.
	 *
	 * @param string $args: WP-CLI Command Name
	 * @param string $assoc_args: WP-CLI Command Option
	 * @since 2.4.0
	 */
	function update( $args, $assoc_args ) {
		if ( 1 > count( $args ) ) {
			WP_CLI::error( 'No type serected' );
		} elseif ( 2 > count( $args ) ) {
			WP_CLI::error( 'No value defined' );
		}
		list( $type, $value ) = $args;
		$name                 = Constants::OPTION_NAME;
		$options              = get_option( $name );
		switch ( $type ) {
			case 'distribution_id':
				$options['distribution_id'] = esc_attr( $value );
				break;

			case 'access_key':
				$options['access_key'] = esc_attr( $value );
				break;

			case 'secret_key':
				$options['secret_key'] = esc_attr( $value );
				break;

			default:
				WP_CLI::error( 'No Match Setting Type.' );
				break;
		}
		if ( ! isset( $options['distribution_id'] ) ) {
			$options['distribution_id'] = '';
		}
		if ( ! isset( $options['access_key'] ) ) {
			$options['access_key'] = '';
		}
		if ( ! isset( $options['secret_key'] ) ) {
			$options['secret_key'] = '';
		}

		update_option( Constants::OPTION_NAME, $options );
		WP_CLI::success( 'Update Option' );
	}
}
