<?php
/**
 * BP Attachments Media.
 *
 * @package BP Attachments
 * @subpackage \bp-attachments\classes\class-bp-attachments-media
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachments Media Class.
 *
 * @since 1.0.0
 */
class BP_Attachments_Media extends BP_Attachment {
	/**
	 * The constuctor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Allowed types & upload size.
		$allowed_types        = bp_attachments_get_allowed_types( '' );
		$max_upload_file_size = bp_attachments_get_max_upload_file_size();

		parent::__construct(
			array(
				'action'                => 'bp_attachments_media_upload',
				'file_input'            => 'file',
				'original_max_filesize' => $max_upload_file_size,
				'base_dir'              => bp_attachments_uploads_dir_get( 'dir' ),
				'required_wp_files'     => array( 'file' ),

				// Specific errors for media uploads.
				'upload_error_strings'  => array(
					11 => sprintf(
						/* translators: %s is for the max upload file size. */
						__( 'That media is too big. Please upload one smaller than %s', 'bp-attachments' ),
						size_format( $max_upload_file_size )
					),
					12 => sprintf(
						/* translators: %s is for the comma separated list of allowed media types. */
						_n( 'Please upload only this file type: %s.', 'Please upload only these file types: %s.', count( $allowed_types ), 'buddypress' ),
						self::get_media_types( $allowed_types )
					),
				),
			)
		);
	}

	/**
	 * Gets the available media types.
	 *
	 * @since 1.0.0
	 *
	 * @param array $allowed_types Array of allowed avatar types.
	 * @return string comma separated list of allowed avatar types.
	 */
	public static function get_media_types( $allowed_types = array() ) {
		$types = array_map( 'strtoupper', $allowed_types );
		$comma = _x( ',', 'avatar types separator', 'buddypress' );
		return join( $comma . ' ', $types );
	}

	/**
	 * Override the parent method to avoid re-create the directories.
	 *
	 * NB: This has been already set during plugin installation.
	 *
	 * @since 1.0.0
	 */
	public function set_upload_dir() {}

	/**
	 * Set the directory when uploading a file.
	 *
	 * @since 1.0.0
	 *
	 * @param array $upload_dir The original Uploads dir.
	 * @return array $value Upload data (path, url, basedir...).
	 */
	public function upload_dir_filter( $upload_dir = array() ) {
		// Populate media arguments merging default with the requested ones.
		$media_args = wp_parse_args(
			array_map( 'wp_unslash', $_REQUEST ), // phpcs:ignore
			array(
				'status'     => 'private',
				'object'     => 'members',
				'object_id'  => 0,
				'parent_dir' => '',
			)
		);

		if ( $media_args['parent_dir'] ) {
			$bp_uploads = bp_attachments_uploads_dir_get();
			$subdir     = '/' . trim( $media_args['parent_dir'], '/' );

			if ( ! is_dir( $bp_uploads['basedir'] . $subdir ) ) {
				$subdir              = '';
				$upload_dir['error'] = __( 'Please select an existing directory.', 'bp-attachments' );
			} else {
				$upload_dir = array_merge(
					$upload_dir,
					array(
						'path'  => $bp_uploads['basedir'] . $subdir,
						'url'   => $bp_uploads['baseurl'] . $subdir,
						'error' => false,
					)
				);
			}
		} else {
			$private_uploads = bp_attachments_get_private_uploads_dir();
			$public_uploads  = bp_attachments_get_public_uploads_dir();

			if ( 'groups' === $media_args['object'] && bp_is_active( 'groups' ) ) {
				$group_id = (int) $media_args['object_id'];
				$group    = groups_get_group( $group_id );

				if ( $group_id !== (int) $group->id ) {
					$subdir              = '';
					$upload_dir['error'] = __( 'Unknown group. Please try again', 'bp-attachments' );
				} else {
					if ( 'public' === $group->status ) {
						$upload_dir = $public_uploads;
					} else {
						$upload_dir = $private_uploads;
					}

					$subdir     = '/groups/' . $group->id;
					$upload_dir = array_merge(
						$upload_dir,
						array(
							'path' => $upload_dir['path'] . $subdir,
							'url'  => $upload_dir['url'] . $subdir,
						)
					);
				}
			} else {
				$user_id = (int) $media_args['object_id'];

				if ( ! $user_id ) {
					$user_id = (int) bp_loggedin_user_id();
				} else {
					$user = get_user_by( 'id', $user_id );
					if ( ! $user ) {
						$user_id = 0;
					}
				}

				if ( ! $user_id ) {
					$subdir              = '';
					$upload_dir['error'] = __( 'Unknown user. Please try again', 'bp-attachments' );
				} else {
					$upload_dir = $private_uploads;
					if ( $media_args['status'] && 'private' !== $media_args['status'] ) {
						$upload_dir = $public_uploads;
					}

					$subdir     = '/members/' . $user_id;
					$upload_dir = array_merge(
						$upload_dir,
						array(
							'path' => $upload_dir['path'] . $subdir,
							'url'  => $upload_dir['url'] . $subdir,
						)
					);
				}
			}
		}

		if ( ! isset( $upload_dir['subdir'] ) ) {
			$upload_dir['subdir'] = '';
		}

		$upload_dir['subdir'] .= $subdir;

		return $upload_dir;
	}

	/**
	 * BP Attachments specific rules for media uploads.
	 *
	 * @since 1.0.0
	 *
	 * @param array $file The temporary file attributes (before it has been moved).
	 * @return array $file The file with extra errors if needed.
	 */
	public function validate_upload( $file = array() ) {
		// Bail if there's already an error.
		if ( ! empty( $file['error'] ) ) {
			return $file;
		}

		// File size is too big.
		if ( $file['size'] > $this->original_max_filesize ) {
			$file['error'] = 11;

			// File is of invalid type.
		} elseif ( ! bp_attachments_check_filetype( $file['tmp_name'], $file['name'], bp_attachments_get_allowed_mimes( '' ) ) ) {
			$file['error'] = 12;
		}

		// Return with error code attached.
		return $file;
	}
}