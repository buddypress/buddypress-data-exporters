<?php

/*
Plugin name: BuddyPress Data Exporters
Description: GDPR compliance tools for BuddyPress. This plugin lets you use these tools before they're part of a BuddyPress release.
Author: The BuddyPress Team
Version: 0.1
*/

/**
 * Load late, so that BuddyPress-native tools take precedence.
 */
add_action( 'bp_init', function() {
	require __DIR__ . '/exporters.php';

	add_filter( 'wp_privacy_personal_data_exporters', function( $exporters ) {
		if ( bp_is_active( 'settings' ) && ! isset( $exporters['buddypress-settings'] ) ) {
			$exporters['buddypress-settings'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Settings Data', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_settings_personal_data_exporter',
			);
		}

		if ( bp_is_active( 'activity' ) && ! isset( $exporters['buddypress-activity'] ) ) {
			$exporters['buddypress-activity'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Activity Data', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_activity_personal_data_exporter',
			);
		}

		if ( bp_is_active( 'xprofile' ) && ! isset( $exporters['buddypress-xprofile'] ) ) {
			$exporters['buddypress-xprofile'] = array(
				'exporter_friendly_name' => __( 'BuddyPress XProfile Data', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_xprofile_personal_data_exporter',
			);
		}

		if ( bp_is_active( 'messages' ) && ! isset( $exporters['buddypress-messages'] ) ) {
			$exporters['buddypress-messages'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Messages', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_messages_personal_data_exporter',
			);
		}

		if ( bp_is_active( 'groups' ) && ! isset( $exporters['buddypress-groups-memberships'] ) ) {
			$exporters['buddypress-groups-memberships'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Group Memberships', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_groups_memberships_personal_data_exporter',
			);

			$exporters['buddypress-groups-pending-requests'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Pending Group Membership Requests', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_groups_pending_requests_personal_data_exporter',
			);

			$exporters['buddypress-groups-pending-received-invitations'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Pending Group Invitations (Received)', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_groups_pending_received_invitations_personal_data_exporter',
			);

			$exporters['buddypress-groups-pending-sent-invitations'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Pending Group Invitations (Sent)', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_groups_pending_sent_invitations_personal_data_exporter',
			);
		}

		if ( bp_is_active( 'friends' ) && ! isset( $exporters['buddypress-friends'] ) ) {
			$exporters['buddypress-friends'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Friends', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_friends_personal_data_exporter',
			);

			$exporters['buddypress-friends-pending-sent-requests'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Friend Requests (Sent)', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_friends_pending_sent_requests_personal_data_exporter',
			);

			$exporters['buddypress-friends-pending-received-requests'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Friend Requests (Received)', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_friends_pending_received_requests_personal_data_exporter',
			);
		}

		if ( bp_is_active( 'notifications' ) && ! isset( $exporters['buddypress-notifications'] ) ) {
			$exporters['buddypress-notifications'] = array(
				'exporter_friendly_name' => __( 'BuddyPress Notifications Data', 'buddypress' ),
				'callback'               => '\BuddyPress\DataExporters\bp_notifications_personal_data_exporter',
			);
		}

		return $exporters;
	} );
}, 100 );
