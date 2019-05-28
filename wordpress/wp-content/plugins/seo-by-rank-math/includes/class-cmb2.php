<?php
/**
 * The CMB2 functionality of the plugin.
 *
 * This class defines all code necessary to have setting pages and manager.
 *
 * @since      0.9.0
 * @package    RankMath
 * @subpackage RankMath\Core
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath;

use RankMath\Admin\Admin_Helper;
use MyThemeShop\Helpers\Str;
use MyThemeShop\Helpers\Param;
use MyThemeShop\Helpers\Conditional;

defined( 'ABSPATH' ) || exit;

/**
 * CMB2 class.
 */
class CMB2 {

	/**
	 * Set field arguments based on type.
	 *
	 * @param CMB2 $cmb CMB2 metabox object.
	 */
	public static function pre_init( $cmb ) {
		foreach ( $cmb->prop( 'fields' ) as $id => $field_args ) {
			$type  = $field_args['type'];
			$field = $cmb->get_field( $id );

			if ( in_array( $type, [ 'meta_tab_container_open', 'tab_container_open', 'tab_container_close', 'tab_open', 'tab_close', 'raw' ], true ) ) {
				$field->args['save_field']    = false;
				$field->args['render_row_cb'] = [ '\RankMath\CMB2', "render_{$type}" ];
			}
			if ( 'notice' === $type ) {
				$field->args['save_field'] = false;
			}

			if ( ! empty( $field_args['dep'] ) ) {
				self::set_dependencies( $field, $field_args );
			}
		}
	}

	/**
	 * Generate the dependency html for JavaScript.
	 *
	 * @param CMB2_Field $field CMB2 field object.
	 * @param array      $args  Dependency array.
	 */
	private static function set_dependencies( $field, $args ) {
		if ( ! isset( $args['dep'] ) || empty( $args['dep'] ) ) {
			return;
		}

		$dependency = '';
		$relation   = 'OR';

		if ( 'relation' === key( $args['dep'] ) ) {
			$relation = current( $args['dep'] );
			unset( $args['dep']['relation'] );
		}

		foreach ( $args['dep'] as $dependence ) {
			$compasrison = isset( $dependence[2] ) ? $dependence[2] : '=';
			$dependency .= '<span class="hidden" data-field="' . $dependence[0] . '" data-comparison="' . $compasrison . '" data-value="' . $dependence[1] . '"></span>';
		}

		$where                 = 'group' === $args['type'] ? 'after_group' : 'after_field';
		$field->args[ $where ] = '<div class="rank-math-cmb-dependency hidden" data-relation="' . strtolower( $relation ) . '">' . $dependency . '</div>';
	}

	/**
	 * Get the object type for the current page, based on the $pagenow global.
	 *
	 * @see CMB2->current_object_type()
	 *
	 * @return string Page object type name.
	 */
	public static function current_object_type() {
		global $pagenow;
		$type = 'post';

		if ( in_array( $pagenow, [ 'user-edit.php', 'profile.php', 'user-new.php' ], true ) ) {
			$type = 'user';
		}

		if ( in_array( $pagenow, [ 'edit-comments.php', 'comment.php' ], true ) ) {
			$type = 'comment';
		}

		if ( in_array( $pagenow, [ 'edit-tags.php', 'term.php' ], true ) ) {
			$type = 'term';
		}

		if ( Conditional::is_ajax() && 'add-tag' === Param::post( 'action' ) ) {
			$type = 'term';
		}

		return $type;
	}

	/**
	 * Render raw field.
	 *
	 * @param array      $field_args Array of field arguments.
	 * @param CMB2_Field $field      The field object.
	 *
	 * @return CMB2_Field
	 */
	public static function render_raw( $field_args, $field ) {
		if ( $field->args( 'file' ) ) {
			include $field->args( 'file' );
		} elseif ( $field->args( 'content' ) ) {
			echo $field->args( 'content' );
		}

		return $field;
	}

	/**
	 * Render tab container opening <div> for option panel.
	 *
	 * @param array      $field_args Array of field arguments.
	 * @param CMB2_Field $field      The field object.
	 *
	 * @return CMB2_Field
	 */
	public static function render_tab_container_open( $field_args, $field ) {
		$active = Param::get( 'rank-math-tab', 'general' );
		echo '<div id="' . $field->prop( 'id' ) . '" class="rank-math-tabs">';
		?>
		<div class="rank-math-tabs-navigation wp-clearfix">

			<?php
			foreach ( $field->args( 'tabs' ) as $id => $tab ) :
				if ( empty( $tab ) ) {
					continue;
				}

				if ( isset( $tab['type'] ) && 'seprator' === $tab['type'] ) {
					printf( '<span class="separator">%s</span>', $tab['title'] );
					continue;
				}
				?>
				<a href="#setting-panel-<?php echo $id; ?>"<?php echo $id === $active ? 'class="active"' : ''; ?>><span class="<?php echo esc_attr( $tab['icon'] ); ?>"></span><?php echo $tab['title']; ?></a>
			<?php endforeach; ?>

		</div>

		<div class="rank-math-tabs-content">
		<?php
		return $field;
	}

	/**
	 * Render tab container opening <div> for metabox.
	 *
	 * @param array      $field_args Array of field arguments.
	 * @param CMB2_Field $field      The field object.
	 *
	 * @return CMB2_Field
	 */
	public static function render_meta_tab_container_open( $field_args, $field ) {
		echo '<div id="' . $field->prop( 'id' ) . '" class="rank-math-tabs">';
		if ( Admin_Helper::is_term_profile_page() ) :
			?>
			<h2 class="rank-math-metabox-frame-title"><?php esc_html_e( 'Rank Math', 'rank-math' ); ?></h2>
		<?php endif; ?>
		<div class="rank-math-tabs-navigation custom wp-clearfix">

			<?php
			foreach ( $field->args( 'tabs' ) as $id => $tab ) :
				if ( empty( $tab ) || ! Helper::has_cap( $tab['capability'] ) ) {
					continue;
				}
				?>
				<a href="#setting-panel-<?php echo $id; ?>"><span class="<?php echo esc_attr( $tab['icon'] ); ?>"></span><span class="rank-math-tab-text"><?php echo $tab['title']; ?></span></a>
			<?php endforeach; ?>
		</div>

		<div class="rank-math-tabs-content custom">
		<?php
		return $field;
	}

	/**
	 * Render tab container closing <div>.
	 *
	 * @param array      $field_args Array of field arguments.
	 * @param CMB2_Field $field      The field object.
	 *
	 * @return CMB2_Field
	 */
	public static function render_tab_container_close( $field_args, $field ) {
		echo '</div><!-- /.rank-math-tabs-content -->';
		echo '</div><!-- /#' . $field->prop( 'id' ) . ' -->';

		return $field;
	}

	/**
	 * Render tab content opening <div>.
	 *
	 * @param array      $field_args Array of field arguments.
	 * @param CMB2_Field $field      The field object.
	 *
	 * @return CMB2_Field
	 */
	public static function render_tab_open( $field_args, $field ) {
		echo '<div id="' . $field->prop( 'id' ) . '" class="rank-math-tab">';

		return $field;
	}

	/**
	 * Render tab content closing <div>.
	 *
	 * @param array      $field_args Array of field arguments.
	 * @param CMB2_Field $field      The field object.
	 *
	 * @return CMB2_Field
	 */
	public static function render_tab_close( $field_args, $field ) {
		echo '</div><!-- /#' . $field->prop( 'id' ) . ' -->';

		return $field;
	}

	/**
	 * Handles sanitization for HTML entities.
	 *
	 * @param mixed $value The unsanitized value from the form.
	 *
	 * @return mixed Sanitized value to be stored.
	 */
	public static function sanitize_htmlentities( $value ) {
		return htmlentities( $value );
	}

	/**
	 * Handles sanitization for webmaster tag and remove <meta> tag.
	 *
	 * @param mixed $value The unsanitized value from the form.
	 *
	 * @return mixed Sanitized value to be stored.
	 */
	public static function sanitize_webmaster_tags( $value ) {
		$value = trim( $value );

		if ( ! empty( $value ) && Str::starts_with( '<meta', trim( $value ) ) ) {
			preg_match( '/content="([^"]+)"/i', stripslashes( $value ), $matches );
			$value = $matches[1];
		}

		return $value;
	}
}
