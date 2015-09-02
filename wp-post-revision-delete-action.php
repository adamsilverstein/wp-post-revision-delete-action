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
		 * Hook into the post editing screen revisions list meta
		 * box and add a link to delete individual revisions.
		 */
		add_action( 'wp_post_revision_actions', array( $this, 'wp_post_revision_delete_action' ), 10, 3 );

		/**
		 * Provide an ajax endpoint for the delete action.
		 */

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
								'action' => 'delete-revision',
							),
							admin_url( 'admin-ajax.php' )
						),
						"delete-revision_$post->ID|$revision->ID"
					),
					__( 'Delete' )
				);
			return $actions;
	}

}

$wp_post_revision_actions = new Wp_Post_Revision_Delete_Action();
