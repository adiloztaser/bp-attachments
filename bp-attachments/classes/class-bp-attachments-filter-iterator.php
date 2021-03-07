<?php
/**
 * BP Attachments FilterIterator.
 *
 * @package \bp-attachments\classes\class-bp-attachments-filter-iterator
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Attachments FilterIterator Class.
 *
 * @since 1.0.0
 */
class BP_Attachments_Filter_Iterator extends FilterIterator {
	/**
	 * Make sure to only get the JSON files.
	 *
	 * These files are describing the Media.
	 *
	 * @since 1.0.0
	 */
	public function accept() {
		$spl_file_info = $this->getInnerIterator()->current();
		return preg_match( '#\.json$#', $spl_file_info );
	}
}
