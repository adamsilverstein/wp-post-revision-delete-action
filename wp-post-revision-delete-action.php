<?php
/*
 * Plugin Name: Post Revision Delete Action
 * Plugin URI: https://github.com/adamsilverstein/wp-post-meta-revisions
 * Description: Adds delete links to the revision list on the post editing screen.
 * Version: 0.0.1
 * Author: Adam Silverstein
 * License: GPLv2 or later
*/

class Wp_Post_Revision_Delete_Action {

	/**
	 * Plugin setup.
	 */
	public function __construct() {

		/**
		 * Only applies in the admin context.
		 */
		if ( ! is_admin() ) {
			return;
		}

		/**
		 * Hook into the post editing screen revisions list meta
		 * box and add a link to delete individual revisions.
		 */
		add_filter( 'wp_post_revision_actions', array( $this, 'wp_post_revision_delete_action' ), 10, 3 );

		/**
		 * Provide an ajax endpoint for the delete action.
		 */
		add_action( 'wp_ajax_delete_revision', array( $this, 'revision_delete_action' ) );

		/**
		 * Add our confirmation messages to the post edit screen.
		 */
		add_filter( 'post_updated_messages', array( $this, 'add_revision_delete_confirmation_messages' ) );

	}

	/**
	 * Add our confirmation messages
	 */
	public function add_revision_delete_confirmation_messages( $messages ) {
		$messages['post'][99] = sprintf(
			__('Revision id #%d deleted'),
			isset( $_GET['revision'] ) ? (int) $_GET['revision'] : 0
			);
		return $messages;
	}

	/**
	 * Add a link to delete a revision, via the `wp_post_revision_actions` filter.
	 */
	public function wp_post_revision_delete_action( $actions, $revision, $post ) {

			$actions .= ' | ' .
				sprintf(
					'<a href="%s">%s</a>',
					wp_nonce_url(
						add_query_arg(
							array(
								'revision' => $revision->ID,
								'diff' => false,
								'action' => 'delete_revision',
							),
							admin_url( 'admin-ajax.php' )
						),
						"delete-revision_$post->ID|$revision->ID"
					),
					__( 'Delete' )
				);
			return $actions;
	}

	/**
	 * Ajax callback to handle deleting the revision, then redirecting
	 * back to the post edit page with a confirmation message.
	 */
	public function revision_delete_action() {

		/**
		 * Bail if required values unset.
		 */
		if ( ! isset( $_GET['revision'] ) ) {
			return;
		}

		$revision_id = sanitize_key( $_GET['revision'] );

		/**
		 * Verify revision ID valud.
		 */
 		if ( ! $revision = wp_get_post_revision( $revision_id ) ) {
			break;
		}

		/**
		 * Verify parent post valid.
		 */
		if ( ! $post = get_post( $revision->post_parent ) ) {
			break;
		}

		/**
		 * Verify current user can edit parent post.
		 */
		if ( ! current_user_can( 'edit_post', $post ) ) {
			break;
		}

		/**
		 * Verify revisions not disabled and we're not looking at an autosave.
		 */
		if ( ! constant('WP_POST_REVISIONS') && ! wp_is_post_autosave( $revision ) ) {
			break;
		}

		/**
		 * Check the nonce.
		 */
		check_admin_referer( "delete-revision_$post->ID|$revision->ID" );

		/**
		 * Every checks out, delete the revision.
		 */
		wp_delete_post_revision( $revision->ID );
		wp_redirect (
			add_query_arg(
				array(
					'message'  => 99,
					'revision' => $revision->ID,
				),
				get_edit_post_link( $post->ID, 'url' )
			)
		);
		exit();
	}

}

$wp_post_revision_actions = new Wp_Post_Revision_Delete_Action();
