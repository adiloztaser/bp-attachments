/**
 * External dependencies.
 */
const {
	filter,
} = lodash;

/**
 * Internal dependencies.
 */
import {
	fetchFromAPI,
	initTree,
	getLoggedInUser as getLoggedInUserAction,
	getMedia as getMediaAction,
} from './actions';

/**
 * Returns the requests context.
 *
 * @access private
 * @returns {string} The requests context (view or edit).
 */
const _requestContext = () => {
	const { isAdminScreen } = window.bpAttachmentsMediaLibrarySettings || {};
	return isAdminScreen && true === isAdminScreen ? 'edit' : 'view';
}

/**
 * Resolver for retrieving current user.
 */
export function* getLoggedInUser() {
	const path = '/buddypress/v1/members/me?context=edit';
	const user = yield fetchFromAPI( path, true );
	yield getLoggedInUserAction( user );
};

/**
 * Resolver for retrieving the media root directories.
 */
export function* getMedia() {
	const path = '/buddypress/v1/attachments?context=' + _requestContext();
	const files = yield fetchFromAPI( path, true );

	// Init the Directories tree.
	initTree( files );

	yield getMediaAction( files, '' );
}
