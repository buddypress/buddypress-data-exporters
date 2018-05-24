<?php

namespace BuddyPress\DataExporters;

/**
 * Finds and exports personal data associated with an email address from the Settings component.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_settings_personal_data_exporter( $email_address, $page ) {
	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$yes = __( 'Yes', 'buddypress' );
	$no  = __( 'No', 'buddypress' );

	$user_settings = array();

	// These settings all default to 'yes' when nothing is saved, so we have to do some pre-processing.
	$notification_settings = array();

	if ( bp_is_active( 'activity' ) ) {
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member mentions you in an update?', 'buddypress' ),
			'key'  => 'notification_activity_new_mention',
		);
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member replies to an update or comment you\'ve posted?', 'buddypress' ),
			'key'  => 'notification_activity_new_reply',
		);
	}

	if ( bp_is_active( 'messages' ) ) {
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member sends you a new message?', 'buddypress' ),
			'key'  => 'notification_messages_new_message',
		);
	}

	if ( bp_is_active( 'friends' ) ) {
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member invites you to join a group?', 'buddypress' ),
			'key'  => 'notification_groups_invite',
		);
	}

	if ( bp_is_active( 'groups' ) ) {
		$notification_settings[] = array(
			'name' => __( 'Receive email when group information is updated?', 'buddypress' ),
			'key'  => 'notification_groups_group_updated',
		);
		$notification_settings[] = array(
			'name' => __( 'Receive email when you are promoted to a group administrator or moderator?', 'buddypress' ),
			'key'  => 'notification_groups_admin_promoted',
		);
		$notification_settings[] = array(
			'name' => __( 'Receive email when a member requests to join a private group for which you are an admin?', 'buddypress' ),
			'key'  => 'notification_groups_membership_request',
		);
		$notification_settings[] = array(
			'name' => __( 'Receive email when your request to join a group has been approved or denied?', 'buddypress' ),
			'key'  => 'notification_membership_request_completed',
		);
	}

	foreach ( $notification_settings as $notification_setting ) {
		$user_notification_setting = bp_get_user_meta( $user->ID, $notification_setting['key'], true );
		if ( empty( $user_notification_setting ) ) {
			$user_notification_setting = 'yes';
		}

		$user_settings[] = array(
			'name'  => $notification_setting['name'],
			'value' => 'yes' === $user_notification_setting ? $yes : $no,
		);
	}

	if ( function_exists( 'bp_nouveau_groups_get_group_invites_setting' ) ) {
		$user_settings[] = array(
			'name'  => __( 'Receive group invitations from my friends only?', 'buddypress' ),
			'value' => bp_nouveau_groups_get_group_invites_setting() ? $yes : $no,
		);
	}

	$data_to_export[] = array(
		'group_id'    => 'bp_settings',
		'group_label' => __( 'Settings', 'buddypress' ),
		'item_id'     => "bp-settings-{$user->ID}",
		'data'        => $user_settings,
	);

	return array(
		'data' => $data_to_export,
		'done' => true,
	);
}

/**
 * Finds and exports personal data associated with an email address from the Activity tables.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_activity_personal_data_exporter( $email_address, $page ) {
	$number = 50;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$activities = bp_activity_get( array(
		'display_comments' => 'stream',
		'per_page'         => $number,
		'page'             => $page,
		'show_hidden'      => true,
		'filter'           => array(
			'user_id' => $user->ID,
		),
	) );

	$user_data_to_export = array();
	$activity_actions    = bp_activity_get_actions();

	foreach ( $activities['activities'] as $activity ) {
		if ( ! empty( $activity_actions->{$activity->component}->{$activity->type}['format_callback'] ) ) {
			$description = call_user_func( $activity_actions->{$activity->component}->{$activity->type}['format_callback'], '', $activity );
		} elseif ( ! empty( $activity->action ) ) {
			$description = $activity->action;
		} else {
			$description = $activity->type;
		}

		$item_data = array(
			array(
				'name'  => __( 'Activity Date', 'buddypress' ),
				'value' => $activity->date_recorded,
			),
			array(
				'name'  => __( 'Activity Description', 'buddypress' ),
				'value' => $description,
			),
			array(
				'name'  => __( 'Activity URL', 'buddypress' ),
				'value' => bp_activity_get_permalink( $activity->id, $activity ),
			),
		);

		if ( ! empty( $activity->content ) ) {
			$item_data[] = array(
				'name'  => __( 'Activity Content', 'buddypress' ),
				'value' => $activity->content,
			);
		}

		/**
		 * Filters the data associated with an activity item when assembled for a WP personal data export.
		 *
		 * Plugins that register activity types whose `action` string doesn't adequately
		 * describe the activity item for the purposes of data export may filter the activity
		 * item data here.
		 *
		 * @since 4.0.0
		 *
		 * @param array                $item_data Array of data describing the activity item.
		 * @param BP_Activity_Activity $activity  Activity item.
		 */
		$item_data = apply_filters( 'bp_activity_personal_data_export_item_data', $item_data, $activity );

		$data_to_export[] = array(
			'group_id'    => 'bp_activity',
			'group_label' => __( 'Activity', 'buddypress' ),
			'item_id'     => "bp-activity-{$activity->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $activities['activities'] ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports personal data associated with an email address from the XProfile tables.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The userss email address.
 * @return array An array of personal data.
 */
function bp_xprofile_personal_data_exporter( $email_address ) {
	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$user_data_to_export = array();

	$user_profile_data = \BP_XProfile_ProfileData::get_all_for_user( $user->ID );
	foreach ( $user_profile_data as $field_name => $field ) {
		// Skip non-array fields, which don't belong to XProfile.
		if ( ! is_array( $field ) ) {
			continue;
		}

		// Re-pull the data so that BuddyPress formats and sanitizes properly.
		$value = xprofile_get_field_data( $field['field_id'], $user->ID, 'comma' );
		$user_data_to_export[] = array(
			'name'  => $field_name,
			'value' => $value,
		);
	}

	$data_to_export[] = array(
		'group_id'    => 'bp_xprofile',
		'group_label' => __( 'Extended Profile Data', 'buddypress' ),
		'item_id'     => "bp-xprofile-{$user->ID}",
		'data'        => $user_data_to_export,
	);

	return array(
		'data' => $data_to_export,
		'done' => true,
	);
}

/**
 * Finds and exports personal data associated with an email address from the Messages tables.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_messages_personal_data_exporter( $email_address, $page ) {
	$number = 10;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$user_data_to_export = array();

	$user_threads = \BP_Messages_Thread::get_current_threads_for_user( array(
		'user_id' => $user->ID,
		'box'     => 'sentbox',
		'type'    => null,
		'limit'   => $number,
		'page'    => $page,
	) );

	foreach ( $user_threads['threads'] as $thread ) {
		$recipient_links = array();
		foreach ( $thread->recipients as $recipient ) {
			if ( $recipient->user_id === $user->ID ) {
				continue;
			}

			$recipient_links[] = bp_core_get_userlink( $recipient->user_id );
		}
		$recipients = implode( ', ', $recipient_links );

		$thread_link = bp_get_message_thread_view_link( $thread->thread_id, $user->ID );

		foreach ( $thread->messages as $message_index => $message ) {
			// Only include messages written by the user.
			if ( $recipient->user_id !== $message->sender_id ) {
				continue;
			}

			$message_data = array(
				array(
					'name'  => __( 'Message Subject', 'buddypress' ),
					'value' => $message->subject,
				),
				array(
					'name'  => __( 'Message Content', 'buddypress' ),
					'value' => $message->message,
				),
				array(
					'name'  => __( 'Date Sent', 'buddypress' ),
					'value' => $message->date_sent,
				),
				array(
					'name' => __( 'Recipients', 'buddypress' ),
					'value' => $recipients,
				),
				array(
					'name'  => __( 'Thread URL', 'buddypress' ),
					'value' => $thread_link,
				),
			);

			$data_to_export[] = array(
				'group_id'    => 'bp_messages',
				'group_label' => __( 'Private Messages', 'buddypress' ),
				'item_id'     => "bp-messages-{$message->id}",
				'data'        => $message_data,
			);
		}
	}

	return array(
		'data' => $data_to_export,
		'done' => true,
	);
}

/**
 * Gets memberships of a user for purposes of a personal data export.
 *
 * @since 4.0.0
 *
 * @param int $user_id ID of the user.
 * @param array $args {
 *    Array of optional arguments.
 *    @type int    $page     Page of memberships being requested. Default 1.
 *    @type int    $per_page Memberships to return per page. Default 20.
 *    @type string $type     Membership type being requested. Accepts 'membership',
 *                           'pending_request', 'pending_received_invitation',
 *                           'pending_sent_invitation'. Default 'membership'.
 * }
 *
 * @return array
 */
function get_user_memberships( $user_id, $args = array() ) {
	global $wpdb;

	$bp = buddypress();

	$r = array_merge( array(
		'page'     => 1,
		'per_page' => 20,
		'type'     => 'membership',
	), $args );

	$sql = array(
		'select' => 'SELECT *',
		'from'   => "FROM {$bp->groups->table_name_members}",
		'where'  => '',
		'limits' => '',
	);

	switch ( $r['type'] ) {
		case 'pending_request' :
			$sql['where'] = $wpdb->prepare( "user_id = %d AND is_confirmed = 0 AND inviter_id = 0", $user_id );
		break;

		case 'pending_received_invitation' :
			$sql['where'] = $wpdb->prepare( "user_id = %d AND is_confirmed = 0 AND inviter_id != 0", $user_id );
		break;

		case 'pending_sent_invitation' :
			$sql['where'] = $wpdb->prepare( "inviter_id = %d AND is_confirmed = 0", $user_id );
		break;

		case 'membership' :
		default :
			$sql['where'] = $wpdb->prepare( "user_id = %d AND is_confirmed = 1", $user_id );
		break;
	}

	if ( $r['page'] && $r['per_page'] ) {
		$sql['limits'] = $wpdb->prepare( "LIMIT %d, %d", ( $r['page'] - 1 ) * $r['per_page'], $r['per_page'] );
	}

	$memberships = $wpdb->get_results( "{$sql['select']} {$sql['from']} WHERE {$sql['where']} {$sql['limits']}" );

	foreach ( $memberships as &$membership ) {
		$membership->id           = (int) $membership->id;
		$membership->group_id     = (int) $membership->group_id;
		$membership->user_id      = (int) $membership->user_id;
		$membership->inviter_id   = (int) $membership->inviter_id;
		$membership->is_admin     = (int) $membership->is_admin;
		$membership->is_mod       = (int) $membership->is_mod;
		$membership->is_banned    = (int) $membership->is_banned;
		$membership->is_confirmed = (int) $membership->is_confirmed;
		$membership->invite_sent  = (int) $membership->invite_sent;
	}

	return $memberships;
}

/**
 * Finds and exports group membership data associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_groups_memberships_personal_data_exporter( $email_address, $page ) {
	$number = 20;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$memberships = get_user_memberships( $user->ID, array(
		'type'     => 'membership',
		'page'     => $page,
		'per_page' => $number,
	) );

	foreach ( $memberships as $membership ) {
		$group = groups_get_group( $membership->group_id );

		$item_data = array(
			array(
				'name'  => __( 'Group Name', 'buddypress' ),
				'value' => bp_get_group_name( $group ),
			),
			array(
				'name'  => __( 'Group URL', 'buddypress' ),
				'value' => bp_get_group_permalink( $group ),
			),
		);

		if ( $membership->inviter_id ) {
			$item_data[] = array(
				'name'  => __( 'Invited By', 'buddypress' ),
				'value' => bp_core_get_userlink( $membership->inviter_id ),
			);
		}

		if ( $group->creator_id === $user->ID ) {
			$group_role = __( 'Creator', 'buddypress' );
		} elseif ( $membership->is_admin ) {
			$group_role = __( 'Admin', 'buddypress' );
		} elseif ( $membership->is_mod ) {
			$group_role = __( 'Moderator', 'buddypress' );
		} else {
			$group_role = __( 'Member', 'buddypress' );
		}

		$item_data[] = array(
			'name'  => __( 'Group Role', 'buddypress' ),
			'value' => $group_role,
		);

		$item_data[] = array(
			'name'  => __( 'Date Joined', 'buddypress' ),
			'value' => $membership->date_modified,
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_groups_memberships',
			'group_label' => __( 'Group Memberships', 'buddypress' ),
			'item_id'     => "bp-group-membership-{$group->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $memberships ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports data on pending group membership requests associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_groups_pending_requests_personal_data_exporter( $email_address, $page ) {
	$number = 20;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$requests = get_user_memberships( $user->ID, array(
		'type'     => 'pending_request',
		'page'     => $page,
		'per_page' => $number,
	) );

	foreach ( $requests as $request ) {
		$group = groups_get_group( $request->group_id );

		$item_data = array(
			array(
				'name'  => __( 'Group Name', 'buddypress' ),
				'value' => bp_get_group_name( $group ),
			),
			array(
				'name'  => __( 'Group URL', 'buddypress' ),
				'value' => bp_get_group_permalink( $group ),
			),
			array(
				'name'  => __( 'Date Sent', 'buddypress' ),
				'value' => $request->date_modified,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_groups_pending_requests',
			'group_label' => __( 'Pending Group Membership Requests', 'buddypress' ),
			'item_id'     => "bp-group-pending-request-{$group->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $requests ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports data on pending group invitations sent by a user associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_groups_pending_sent_invitations_personal_data_exporter( $email_address, $page ) {
	$number = 20;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$invitations = get_user_memberships( $user->ID, array(
		'type'     => 'pending_sent_invitation',
		'page'     => $page,
		'per_page' => $number,
	) );

	foreach ( $invitations as $invitation ) {
		$group = groups_get_group( $invitation->group_id );

		$item_data = array(
			array(
				'name'  => __( 'Group Name', 'buddypress' ),
				'value' => bp_get_group_name( $group ),
			),
			array(
				'name'  => __( 'Group URL', 'buddypress' ),
				'value' => bp_get_group_permalink( $group ),
			),
			array(
				'name'  => __( 'Sent To', 'buddypress' ),
				'value' => bp_core_get_userlink( $invitation->user_id ),
			),
			array(
				'name'  => __( 'Date Sent', 'buddypress' ),
				'value' => $invitation->date_modified,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_groups_pending_sent_invitations',
			'group_label' => __( 'Pending Group Invitations (Sent)', 'buddypress' ),
			'item_id'     => "bp-group-pending-sent-invitation-{$group->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $invitations ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports data on pending group invitations received by a user associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_groups_pending_received_invitations_personal_data_exporter( $email_address, $page ) {
	$number = 20;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$invitations = get_user_memberships( $user->ID, array(
		'type'     => 'pending_received_invitation',
		'page'     => $page,
		'per_page' => $number,
	) );

	foreach ( $invitations as $invitation ) {
		$group = groups_get_group( $invitation->group_id );

		$item_data = array(
			array(
				'name'  => __( 'Group Name', 'buddypress' ),
				'value' => bp_get_group_name( $group ),
			),
			array(
				'name'  => __( 'Group URL', 'buddypress' ),
				'value' => bp_get_group_permalink( $group ),
			),
			array(
				'name'  => __( 'Invited By', 'buddypress' ),
				'value' => bp_core_get_userlink( $invitation->inviter_id ),
			),
			array(
				'name'  => __( 'Date Sent', 'buddypress' ),
				'value' => $invitation->date_modified,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_groups_pending_received_invitations',
			'group_label' => __( 'Pending Group Invitations (Received)', 'buddypress' ),
			'item_id'     => "bp-group-pending-received-invitation-{$group->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $invitations ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports friendship data associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_friends_personal_data_exporter( $email_address, $page ) {
	$number = 50;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$friendships = \BP_Friends_Friendship::get_friendships( $user->ID, array(
		'is_confirmed' => true,
		'page'         => $page,
		'per_page'     => $number,
	) );

	$user_data_to_export = array();

	foreach ( $friendships as $friendship ) {
		if ( (int) $user->ID === (int) $friendship->initiator_user_id ) {
			$friend_id         = $friendship->friend_user_id;
			$user_is_initiator = true;
		} else {
			$friend_id         = $friendship->initiator_user_id;
			$user_is_initiator = false;
		}

		$item_data = array(
			array(
				'name'  => __( 'Friend', 'buddypress' ),
				'value' => bp_core_get_userlink( $friend_id ),
			),
			array(
				'name'  => __( 'Initiated By Me', 'buddypress' ),
				'value' => $user_is_initiator ? __( 'Yes', 'buddypress' ) : __( 'No', 'buddypress' ),
			),
			array(
				'name'  => __( 'Friendship Date', 'buddypress' ),
				'value' => $friendship->date_created,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_friends',
			'group_label' => __( 'Friends', 'buddypress' ),
			'item_id'     => "bp-friends-{$friend_id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $friendships ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports pending sent friendship request data associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_friends_pending_sent_requests_personal_data_exporter( $email_address, $page ) {
	$number = 50;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$friendships = \BP_Friends_Friendship::get_friendships( $user->ID, array(
		'is_confirmed'      => false,
		'initiator_user_id' => $user->ID,
		'page'              => $page,
		'per_page'          => $number,
	) );

	$user_data_to_export = array();

	foreach ( $friendships as $friendship ) {
		$item_data = array(
			array(
				'name'  => __( 'Recipient', 'buddypress' ),
				'value' => bp_core_get_userlink( $friendship->friend_user_id ),
			),
			array(
				'name'  => __( 'Date Sent', 'buddypress' ),
				'value' => $friendship->date_created,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_friends_pending_sent_requests',
			'group_label' => __( 'Pending Friend Requests (Sent)', 'buddypress' ),
			'item_id'     => "bp-friends-pending-sent-request-{$friendship->friend_user_id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $friendships ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports pending received friendship request data associated with an email address.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The user's email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_friends_pending_received_requests_personal_data_exporter( $email_address, $page ) {
	$number = 50;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$friendships = \BP_Friends_Friendship::get_friendships( $user->ID, array(
		'is_confirmed'   => false,
		'friend_user_id' => $user->ID,
		'page'           => $page,
		'per_page'       => $number,
	) );

	$user_data_to_export = array();

	foreach ( $friendships as $friendship ) {
		$item_data = array(
			array(
				'name'  => __( 'Requester', 'buddypress' ),
				'value' => bp_core_get_userlink( $friendship->initiator_user_id ),
			),
			array(
				'name'  => __( 'Date Sent', 'buddypress' ),
				'value' => $friendship->date_created,
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_friends_pending_received_requests',
			'group_label' => __( 'Pending Friend Requests (Received)', 'buddypress' ),
			'item_id'     => "bp-friends-pending-received-request-{$friendship->initiator_user_id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $friendships ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Finds and exports personal data associated with an email address from the Notifications tables.
 *
 * @since 4.0.0
 *
 * @param string $email_address  The users email address.
 * @param int    $page           Batch number.
 * @return array An array of personal data.
 */
function bp_notifications_personal_data_exporter( $email_address, $page ) {
	$number = 50;

	$email_address = trim( $email_address );

	$data_to_export = array();

	$user = get_user_by( 'email', $email_address );

	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$notifications = \BP_Notifications_Notification::get( array(
		'is_new'   => null,
		'per_page' => $number,
		'page'     => $page,
		'user_id'  => $user->ID,
		'order'    => 'DESC',
	) );

	$user_data_to_export = array();

	foreach ( $notifications as $notification ) {
		if ( 'xprofile' == $notification->component_name ) {
			$component_name = 'profile';
		} else {
			$component_name = $notification->component_name;
		}

		// Format notifications.
		if ( isset( buddypress()->{$component_name}->notification_callback ) && is_callable( buddypress()->{$component_name}->notification_callback ) ) {
			$content = call_user_func( buddypress()->{$component_name}->notification_callback, $notification->component_action, $notification->item_id, $notification->secondary_item_id, 1, 'string', $notification->id );
		} else {
			// The array to reference with apply_filters_ref_array().
			$ref_array = array(
				$notification->component_action,
				$notification->item_id,
				$notification->secondary_item_id,
				$notification->total_count,
				'string',
				$notification->component_action,
				$component_name,
				$notification->id,
			);

			/** This filter is documented in bp-notifications/bp-notifications-functions.php */
			$content = apply_filters_ref_array( 'bp_notifications_get_notifications_for_user', $ref_array );
		}

		$item_data = array(
			array(
				'name'  => __( 'Notification Content', 'buddypress' ),
				'value' => $content,
			),
			array(
				'name'  => __( 'Notification Date', 'buddypress' ),
				'value' => $notification->date_notified,
			),
			array(
				'name'  => __( 'Status', 'buddypress' ),
				'value' => $notification->is_new ? __( 'Unread', 'buddypress' ) : __( 'Read', 'buddypress' ),
			),
		);

		$data_to_export[] = array(
			'group_id'    => 'bp_notifications',
			'group_label' => __( 'Notifications' ),
			'item_id'     => "bp-notifications-{$notification->id}",
			'data'        => $item_data,
		);
	}

	// Tell core if we have more items to process.
	$done = count( $notifications ) < $number;

	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}
