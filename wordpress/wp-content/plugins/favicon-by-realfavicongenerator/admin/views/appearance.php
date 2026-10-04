<?php
// Copyright 2014-2016 RealFaviconGenerator

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

<?php if ( $new_favicon_params_url ) { ?>
	<div id="install_in_progress_message" class="updated">
		<p><?php esc_html_e( 'Favicon installation in progress. Please wait...', 'favicon-by-realfavicongenerator' ); ?></p>
	</div>

	<div id="install_completed_message" class="updated" style="display:none">
		<p>
			<?php esc_html_e( 'Favicon installed!', 'favicon-by-realfavicongenerator' ); ?>
			<span id="rank_notice" style="display:none">
				<?php
				echo wp_kses(
					sprintf(
						// translators: %s is the HTML attributes (target and href) injected into the anchor tag
						__( 'Do you like the result? If so, would you like to <a %s>rate the plugin</a>?', 'favicon-by-realfavicongenerator' ),
						'target="_blank" href="https://wordpress.org/support/view/plugin-reviews/favicon-by-realfavicongenerator"'
					),
					array( 'a' => array( 'href' => array(), 'target' => array() ) )
				);
				?>
			</span>
		</p>
	</div>
	<div id="install_error_message" class="error" style="display:none"><p></p></div>

	<div id="install_completed_container" style="display:none">
		<h3><?php esc_html_e( 'Current favicon', 'favicon-by-realfavicongenerator' ); ?></h3>

		<?php
		include_once plugin_dir_path( __FILE__ ) . DIRECTORY_SEPARATOR .
		'keep_active_notice.php';
		?>
		
		<p><?php esc_html_e( 'The favicon is up and ready.', 'favicon-by-realfavicongenerator' ); ?></p>
		<img id="preview_image">

		<p>
			<?php
			echo wp_kses(
				sprintf(
					// translators: %s is the HTML attributes (id, class and href) injected into the anchor tag
					__( '<a %s>Check your favicon</a> with RealFaviconGenerator\'s favicon checker.', 'favicon-by-realfavicongenerator' ),
					'id="checker_link" class="button-primary" href="#"'
				),
				array( 'a' => array( 'id' => array(), 'class' => array(), 'href' => array() ) )
			);
			?>
			<?php esc_html_e( 'This option works only if your site is accessible from the outside.', 'favicon-by-realfavicongenerator' ); ?>
		</p>
	</div>
<?php } else { ?>
	<h3><?php esc_html_e( 'Current favicon', 'favicon-by-realfavicongenerator' ); ?></h3>

	<?php if ( $favicon_configured ) { ?>
		<?php
		include_once plugin_dir_path( __FILE__ ) . DIRECTORY_SEPARATOR .
		'keep_active_notice.php';
		?>
	<p><?php esc_html_e( 'The favicon is up and ready.', 'favicon-by-realfavicongenerator' ); ?></p>
	<?php } else { ?>
	<p><?php esc_html_e( 'No favicon has been configured yet.', 'favicon-by-realfavicongenerator' ); ?></p>
	<?php } ?>

	<?php
	if ( $favicon_configured ) {
		if ( $preview_url ) {
			?>

	<img src="<?php echo esc_html( $preview_url ) ?>">

<?php } ?>
	<p>
		  <?php
			echo wp_kses(
				sprintf(
					// translators: %s is the HTML attributes (class and href) injected into the anchor tag
					__( '<a %s>Check your favicon</a> with RealFaviconGenerator\'s favicon checker.', 'favicon-by-realfavicongenerator' ),
					'class="button-primary" ' .
					'href="https://realfavicongenerator.net/favicon_checker?site=' . urlencode( home_url() ) . ( $favicon_in_root ? '' : '&ignore_root_issues=on' ) . '"'
				),
				array( 'a' => array( 'class' => array(), 'href' => array() ) )
			);
			?>
			<?php esc_html_e( 'This option works only if your site is accessible from the outside.', 'favicon-by-realfavicongenerator' ); ?>
	</p>
		<?php
	}
}
?>

	<div id="favicon_form_container" <?php echo $new_favicon_params_url ? 'style="display:none"' : ''; ?>>
		<h3><?php esc_html_e( 'Favicon generation', 'favicon-by-realfavicongenerator' ); ?></h3>
<?php if ( $favicon_configured || $new_favicon_params_url ) { ?>
	<p><?php esc_html_e( 'You can replace the existing favicon.', 'favicon-by-realfavicongenerator' ); ?></p>
<?php } ?>
		<form role="form" method="post" action="https://realfavicongenerator.net/api/favicon_generator" id="favicon_form">
			<input type="hidden" name="json_params" id="json_params"/>
			<table class="form-table"><tbody>
				<tr valign="top">
					<th scope="row">
						<label for="master_picture_url"><?php esc_html_e( 'Master picture URL', 'favicon-by-realfavicongenerator' ); ?></label>
					</th>
					<td>
						<input id="master_picture_url" name="master_picture_url" size="55">
						<button id="upload_image_button" value="<?php esc_html_e( 'Select from the Media Library', 'favicon-by-realfavicongenerator' ); ?>">
							<?php esc_html_e( 'Select from the Media Library', 'favicon-by-realfavicongenerator' ); ?>
						</button>
						<p class="description">
							<?php esc_html_e( 'Submit a square picture, at least 70x70 (recommended: 260x260 or more)', 'favicon-by-realfavicongenerator' ); ?>
							<br>
							<?php esc_html_e( 'If the picture is on your hard drive, you can leave this field blank and upload the picture from RealFaviconGenerator.', 'favicon-by-realfavicongenerator' ); ?>
						</p>
					</td>
				</tr>

<?php if ( $can_rewrite ) { ?>
				<tr valign="top">
					<th scope="row">
						<label for="rewrite"><?php esc_html_e( 'Favicon files in root directory', 'favicon-by-realfavicongenerator' ); ?></label>
					</th>
					<td>
						<input type="checkbox" name="rewrite" id="rewrite" checked="true">
						<p class="description">
							<?php esc_html_e( 'The plugin always stores the favicon files in a dedicated directory.', 'favicon-by-realfavicongenerator' ); ?>
							<br>
							<?php esc_html_e( 'However, if this option is enabled, the plugin takes advantage of the permalink feature and the favicon files appear to be in the root directory', 'favicon-by-realfavicongenerator' ); ?>
							(<a href="https://realfavicongenerator.net/faq#why_icons_in_root"><?php esc_html_e( 'recommended', 'favicon-by-realfavicongenerator' ); ?></a>)
						</p>
					</td>
				</tr>
<?php } ?>
			</tbody></table>

			<p class="submit">
				<input type="submit" name="Generate favicon" id="generate_favicon_button" class="button-primary"
					value="<?php esc_html_e( 'Generate favicon', 'favicon-by-realfavicongenerator' ); ?>">
			</p>
		</form>
	</div>
</div>

<script type="text/javascript">
	var picData = null;

	function urlToBase64(url, callback) {
		var img = new Image();
		img.onload = function() {
			callback(getBase64Image(img));
		}
		img.onerror = function() {
			callback(null);
		}
		img.src = url;
	}

	// See http://stackoverflow.com/questions/934012/get-image-data-in-javascript
	// Credits: Matthew Crumley
	function getBase64Image(img) {
		try {
			var canvas = document.createElement("canvas");
			canvas.width = img.width;
			canvas.height = img.height;

			var ctx = canvas.getContext("2d");
			ctx.drawImage(img, 0, 0);

			var dataURL = canvas.toDataURL("image/png");

			return dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
		}
		catch(err) {
			console.log("Cannot get the picture from the Media Library: " + err);
			return null;
		}
	}

	function computeJson() {
		var params = { favicon_generation: {
			callback: {},
			master_picture: {},
			files_location: {},
			api_key: "87d5cd739b05c00416c4a19cd14a8bb5632ea563"
		}};

		if (jQuery('#master_picture_url').val().length <= 0) {
			params.favicon_generation.master_picture.type = "no_picture";
			params.favicon_generation.master_picture.demo = true;
		}
		else if (pictureContent != null) {
			params.favicon_generation.master_picture.type = "inline";
			params.favicon_generation.master_picture.content = pictureContent;
		}
		else {
			params.favicon_generation.master_picture.type = "url";
			params.favicon_generation.master_picture.url = jQuery('#master_picture_url').val();
		}

<?php if ( $can_rewrite ) { ?>
		if ( jQuery("#rewrite").is(':checked') ) {
			params.favicon_generation.files_location.type = 'root';
		}
		else {
			params.favicon_generation.files_location.type = 'path';
			params.favicon_generation.files_location.path = '<?php echo esc_html( $pic_path ) ?>';
		}
<?php } else { ?>
		params.favicon_generation.files_location.type = 'path';
		params.favicon_generation.files_location.path = '<?php echo esc_html( $pic_path ) ?>';
<?php } ?>

		params.favicon_generation.callback.type = 'url';
		params.favicon_generation.callback.url = "<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Page slug from WP admin routing, not form submission
			echo esc_html( admin_url(
				'themes.php?page=' . ( ( isset( $_GET['page'] ) )
				? preg_replace( '/^http:\/\//', '', esc_url_raw( wp_unslash( $_GET['page'] ) ) )
					// esc_url_raw is used to sanitize the path. This function adds a 'http://' prefix, which is then removed.
					// This code cannot be moved to a helper, as phpcs won't detect it and report an issue.
				: Favicon_By_RealFaviconGenerator_Admin::MENU_SLUG_APPEARANCE )
			) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			?>";
		params.favicon_generation.callback.short_url = 'true';
		params.favicon_generation.callback.path_only = 'true';
		params.favicon_generation.callback.custom_parameter = "<?php echo esc_html( $nonce ) ?>";

		return params;
	}

	var pictureContent = null;
	var pictureContentTimestamp = null;

	function prepareInlinePicture(pictureUrl) {
		var timestamp = new Date().getTime();
		pictureContentTimestamp	= timestamp;

		jQuery('#generate_favicon_button').attr('disabled', 'disabled');
		jQuery('#generate_favicon_button').val("<?php esc_html_e( 'Preparing master picture...', 'favicon-by-realfavicongenerator' ); ?>");

		pictureContent = null;

		urlToBase64(pictureUrl, function(content) {
			if (content != null) {
				pictureContent = content;
			}
			restoreGenerateFaviconButton();
		});

		setTimeout(function() {
			if (pictureContentTimestamp == timestamp) {
				restoreGenerateFaviconButton();
			}
		}, 3000);
	}

	function restoreGenerateFaviconButton() {
		jQuery('#generate_favicon_button').removeAttr('disabled');
		jQuery('#generate_favicon_button').val("<?php esc_html_e( 'Generate favicon', 'favicon-by-realfavicongenerator' ); ?>");
	}

<?php if ( $new_favicon_params_url ) { ?>
	var data = {
		action: '<?php echo esc_html( Favicon_By_RealFaviconGenerator_Common::PLUGIN_PREFIX . '_install_new_favicon' ) ?>',
		json_result_url: '<?php echo esc_html( $new_favicon_params_url ) ?>',
		_wpnonce: '<?php echo esc_html( $nonce ) ?>'
	};
<?php } ?>

	jQuery(document).ready(function() {
		jQuery('#favicon_form').submit(function(e) {
			jQuery('#json_params').val(JSON.stringify(computeJson()));
		});

<?php if ( $new_favicon_params_url ) { ?>
		jQuery.get('<?php echo esc_html( $ajax_url ) ?>', data)
			.done(function(response) {
				if (response.status == 'success') {
					jQuery('#preview_image').attr('src', response.preview_url);
					var checkerUrl = "https://realfavicongenerator.net/favicon_checker?site=<?php echo urlencode( home_url() ); ?>" +
						(response.favicon_in_root ? '' : '&ignore_root_issues=on');
					jQuery('#checker_link').attr('href', checkerUrl);
					jQuery('#install_in_progress_message').fadeOut(function() {
						jQuery('#install_completed_message').fadeIn(function() {
							jQuery('#rank_notice').fadeIn(function() {
								jQuery('#rank_notice').effect('pulsate', {
									times: 3,
									duration: 2000
								});
							});
						});
						jQuery('#install_completed_container').fadeIn();
						jQuery('#favicon_form_container').fadeIn();
					});
				}
				else {
					var msg = "<?php esc_html_e( 'An error occured', 'favicon-by-realfavicongenerator' ); ?>";
					if (response.message != null) {
						msg += ": " + response.message;
					}
					jQuery('#install_error_message p').text(msg);
					jQuery('#install_in_progress_message').fadeOut(function() {
						jQuery('#install_error_message').fadeIn();
					});
				}
			})
			.fail(function() {
				var msg = "<?php esc_html_e( 'An internal error occurred', 'favicon-by-realfavicongenerator' ); ?>";
				jQuery('#install_error_message p').text(msg);
				jQuery('#install_in_progress_message').fadeOut(function() {
					jQuery('#install_error_message').fadeIn();
				});
			});
<?php } ?>

		var fileFrame;

		jQuery('#upload_image_button').on('click', function(event) {
			event.preventDefault();

			if (fileFrame) {
				fileFrame.open();
				return;
			}

			// Create the media frame.
			fileFrame = wp.media.frames.file_frame = wp.media({
				title: jQuery(this).data('uploader_title'),
				button: {
					text: jQuery(this).data('uploader_button_text'),
				},
				multiple: false
			});

			fileFrame.on('select', function() {
				attachment = fileFrame.state().get('selection').first().toJSON();
				jQuery('#master_picture_url').val(attachment.url);
				prepareInlinePicture(attachment.url);
			});

			fileFrame.open();
		});


		jQuery('#master_picture_url').change(function() {
			// Whatever the previous content of the field, forget its cached data
			pictureContent = null;
			restoreGenerateFaviconButton();
		});
	});
</script>
