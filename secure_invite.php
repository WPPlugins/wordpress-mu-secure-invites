<?php
/*
Plugin Name: Secure invitation plugin for Wordpress MU, "secure_invite"
Description: Stops public signup on your Wordpress MU site, but allows users to email and invite their friend to join your blog community. This plugin is based on a plugin by kt (Gord), from http://www.ikazoku.com
Version: 0.8.5
Author: Chris Taylor
Author URI: http://www.stillbreathing.co.uk
Plugin URI: http://www.stillbreathing.co.uk/projects/mu-secure-invites/
Date: 2009-01-06
*/

// when the wp-signup.php page is requested
$secure_invite_signup_page = stripslashes( get_site_option("secure_invite_signup_page") );
if ((strpos($_SERVER["REQUEST_URI"], "wp-signup.php") !== false || secure_invite_is_restricted_page()) && stripslashes( get_site_option("secure_invite_open_signup") ) != "1")
{
	// set the signup request as not valid
	$valid = false;

	// check the email address is a valid invitation
	if (isset($_SERVER["QUERY_STRING"]) && (secure_invites_is_valid_email($_SERVER["QUERY_STRING"]) || secure_invites_is_valid_email(trim(@$_POST["user_email"]))))
	{
		$valid = true;
		if ($_SERVER["QUERY_STRING"] != "")
		{
			$_POST['user_email'] = $_SERVER["QUERY_STRING"];
		}
	}

	// if the signup request is not valid
	if (!$valid)
	{
		// show the message
		$secure_invite_no_invite_message = stripslashes( get_site_option("secure_invite_no_invite_message") );
		if ($secure_invite_no_invite_message == "") { $secure_invite_no_invite_message = "Sorry, you must be invited to join this community."; }
		// stop processing
		wp_die($secure_invite_no_invite_message);
		exit();
	}
}

// when the admin menu is built
add_action('admin_menu', 'secure_invites_add_admin');

function secure_invite_is_restricted_page() {
	$secure_invite_signup_page = stripslashes( get_site_option("secure_invite_signup_page") );
	if ( strpos( $secure_invite_signup_page, "," ) ) {
		$pages = explode( ",", $secure_invite_signup_page );
		foreach( $pages as $page ) {
			if ( strpos( $_SERVER["REQUEST_URI"], $page ) ) {
				return true;
			}
		}
	} else {
		if ( strpos( $_SERVER["REQUEST_URI"], $secure_invite_signup_page ) ) {
			return true;
		}
	}
	return false;
}

// check an email address has been invited
function secure_invites_is_valid_email($email) {
	if ($email && is_email($email))
	{
		$timelimit = stripslashes( get_site_option("secure_invite_signup_time_limit") );
		if ($timelimit == "")
		{
			// default time limit of 3 days
			$timelimit = 3;
		}
		global $wpdb;
		$sql = $wpdb->prepare("select count(id) from ".$wpdb->base_prefix."invitations where invited_email = '%s' and UNIX_TIMESTAMP(datestamp) > %d;", $email, (time()-($timelimit*60*60*24)));
		$invites = $wpdb->get_var($sql);
		
		if ($invites == "0")
		{
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}

// add the admin invitation button
function secure_invites_add_admin() {
	$secure_invite_show_admin_link = stripslashes( get_site_option("secure_invite_show_admin_link") );
	if ($secure_invite_show_admin_link == "") { $secure_invite_show_admin_link = "yes"; }
	// if the user can send invites
	if (secure_invite_user_can_invite() && $secure_invite_show_admin_link == "yes")
	{
		add_submenu_page('index.php', __('Invite friends', "secure_invite"), __('Invite friends', "secure_invite"), 0, 'secure_invite', 'secure_invite_admin');
	}
	add_submenu_page('wpmu-admin.php', __('Invites', "secure_invite"), __('Invites', "secure_invite"), 10, 'secure_invite_list', 'secure_invite_list');
}

// show the settings for secure invites
function secure_invite_settings()
{
	// check the invites table exists
	secure_invite_check_table();

	if (@$_POST && is_array($_POST) && count($_POST) > 0)
	{
		update_site_option("secure_invite_days_after_joining", (int)$_POST["secure_invite_days_after_joining"]);
		update_site_option("secure_invite_signup_page", $_POST["secure_invite_signup_page"]);
		update_site_option("secure_invite_registration_page", $_POST["secure_invite_registration_page"]);
		update_site_option("secure_invite_no_invite_message", trim($_POST["secure_invite_no_invite_message"]));
		update_site_option("secure_invite_signup_time_limit", trim($_POST["secure_invite_signup_time_limit"]));
		update_site_option("secure_invite_default_message", trim($_POST["secure_invite_default_message"]));
		update_site_option("secure_invite_open_signup", trim($_POST["secure_invite_open_signup"]));
		update_site_option("secure_invite_invite_limit", trim($_POST["secure_invite_invite_limit"]));
		update_site_option("secure_invite_show_admin_link", trim($_POST["secure_invite_show_admin_link"]));
		
		echo '<div id="message" class="updated fade"><p><strong>'.__('The settings have been updated', "secure_invite").'</strong></p></div>';
	}

	$secure_invite_days_after_joining = stripslashes( get_site_option("secure_invite_days_after_joining") );
	if ($secure_invite_days_after_joining == "") { $secure_invite_days_after_joining = "30"; }
	
	$secure_invite_open_signup = stripslashes( get_site_option("secure_invite_open_signup") );
	$open_signup = "";
	if ($secure_invite_open_signup == "1") { $open_signup = ' checked="checked"'; }
	
	$secure_invite_signup_page = stripslashes( get_site_option("secure_invite_signup_page") );
	if ($secure_invite_signup_page == "") { $secure_invite_signup_page = "wp-signup.php"; }
	
	$secure_invite_registration_page = stripslashes( get_site_option("secure_invite_registration_page") );
	if ($secure_invite_registration_page == "") { $secure_invite_registration_page = trim(get_bloginfo("wpurl"), '/') . "/wp-signup.php"; }
	
	$secure_invite_no_invite_message = stripslashes( get_site_option("secure_invite_no_invite_message") );
	if ($secure_invite_no_invite_message == "") { $secure_invite_no_invite_message = "Sorry, you must be invited to join this community."; }
	
	$secure_invite_signup_time_limit = stripslashes( get_site_option("secure_invite_signup_time_limit") );
	if ($secure_invite_signup_time_limit == "") { $secure_invite_signup_time_limit = 3; }
	
	$secure_invite_invite_limit = stripslashes( get_site_option("secure_invite_invite_limit") );
	if ($secure_invite_invite_limit == "") { $secure_invite_invite_limit = 0; }
	
	$secure_invite_show_admin_link = stripslashes( get_site_option("secure_invite_show_admin_link") );
	if ($secure_invite_show_admin_link == "") { $secure_invite_show_admin_link = "yes"; }
	
	$secure_invite_default_message = stripslashes( get_site_option("secure_invite_default_message") );
	if ($secure_invite_default_message == "") { $secure_invite_default_message = "----------------------------------------------------------------------------------------
	
You have been invited to open a free weblog at [sitename]. To open and register for your weblog today, please visit

[signuplink]

Regards,

[name]

This invitation will work for the next [timeout] days. After that your invitation will expire and you will have to be invited again.

If clicking the links in this message does not work, copy and paste them into the address bar of your browser."; }

	echo '<div class="wrap">
	<h2>' . __("Invitation settings", "secure_invite") . ' (<a href="wpmu-admin.php?page=secure_invite_list">' . __("view list", "secure_invite") . '</a>)</h2>
	<p>' . __("Change the settings for secure invitations here.", "secure_invite") . '</p>
	<form action="wpmu-admin.php?page=secure_invite_list&amp;view=secure_invite_settings" method="post">
	<fieldset>
	
	<p>' . __("Show the invite link in the admin area for normal users", "secure_invite") . '</p>
	<p><label for="secure_invite_show_admin_link" style="float:left;width:30%;">' . __("Show admin link", "secure_invite") . '</label>
	<select name="secure_invite_show_admin_link" id="secure_invite_show_admin_link">
	<option value="yes"';
	if ($secure_invite_show_admin_link == "yes"){ echo ' selected="selected"'; }
	echo '">'.__("Yes", "secure_invite").'</option>
	<option value="no"';
	if ($secure_invite_show_admin_link == "no"){ echo ' selected="selected"'; }
	echo '">'.__("No", "secure_invite").'</option>
	</select></p>
	
	<p>' . __("A user must have been registered for how many days before they can invite friends?", "secure_invite") . '</p>
	<p><label for="secure_invite_days_after_joining" style="float:left;width:30%;">' . __("Inviting lockdown (days)", "secure_invite") . '</label>
	<input type="text" name="secure_invite_days_after_joining" id="secure_invite_days_after_joining" value="'.$secure_invite_days_after_joining.'" style="width:60%" /></p>
	
	<p>' . __("How many invites can one user send? (set as 0 or blank for unlimited)", "secure_invite") . '</p>
	<p><label for="secure_invite_invite_limit" style="float:left;width:30%;">' . __("Maximum number of invites per person", "secure_invite") . '</label>
	<input type="text" name="secure_invite_invite_limit" id="secure_invite_invite_limit" value="'.$secure_invite_invite_limit.'" style="width:60%" /></p>
	
	<p>' . __("What is the address of the signup page? (wp-signup.php is the default)", "secure_invite") . '</p>
	<p><label for="secure_invite_signup_page" style="float:left;width:30%;">' . __("Signup page", "secure_invite") . '</label>
	<input type="text" name="secure_invite_signup_page" id="secure_invite_signup_page" value="'.$secure_invite_signup_page.'" style="width:60%" /></p>
	<p>' . __("You can put multiple addresses here separated by a comma (,). For example, when using Buddypress you will want to add &quot;wp-signup.php,wp-login.php?action=register,/register&quot;", "secure_invite") . '</p>
	
	<p>' . __("What address do you want the invitation emails to send people to? Please add the full URL to the registration page.", "secure_invite") . '</p>
	<p><label for="secure_invite_registration_page" style="float:left;width:30%;">' . __("Signup page", "secure_invite") . '</label>
	<input type="text" name="secure_invite_registration_page" id="secure_invite_registration_page" value="'.$secure_invite_registration_page.'" style="width:60%" /></p>
	
	<p>' . __("What message do you want to show if someone tries to sign up without being invited?", "secure_invite") . '</p>
	<p><label for="secure_invite_no_invite_message" style="float:left;width:30%;">' . __("No invitation message", "secure_invite") . '</label>
	<input type="text" name="secure_invite_no_invite_message" id="secure_invite_no_invite_message" value="'.$secure_invite_no_invite_message.'" style="width:60%" /></p>
	
	<p>' . __("How many days would you like an invitation to be open for?", "secure_invite") . '</p>
	<p><label for="secure_invite_signup_time_limit" style="float:left;width:30%;">' . __("Time limit for signups (days)", "secure_invite") . '</label>
	<input type="text" name="secure_invite_signup_time_limit" id="secure_invite_signup_time_limit" value="'.$secure_invite_signup_time_limit.'" style="width:60%" /></p>
	
	<p>' . __("Allow anyone to sign up? This disables the security on the signup page", "secure_invite") . '</p>
	<p><label for="secure_invite_open_signup" style="float:left;width:30%;">' . __("Open signup", "secure_invite") . '</label>
	<input type="checkbox" name="secure_invite_open_signup" id="secure_invite_open_signup"' . $open_signup . ' value="1" /></p>
	
	<p>' . __("Enter the message you would like to appear below the users personal message in the invite email. There are four special keywords to enter which are automatically changed when the email is sent. Use these keywords:", "secure_invite") . '</p>
	<ul>
		<li><code>[sitename]</code> ' . __("where you want the name of your site to appear", "secure_invite") . '</li>
		<li><code>[signuplink]</code> ' . __("where you want the special link to the signup form to appear", "secure_invite") . '</li>
		<li><code>[name]</code> ' . __("where you want the name of the email sender to appear", "secure_invite") . '</li>
		<li><code>[timeout]</code> ' . __("where you want the number of days this invitation is valid to appear", "secure_invite") . '</li>
	</ul>
	<p><label for="secure_invite_default_message" style="float:left;width:30%;>"' . __("Default message for invites", "secure_invite") . '</label>
	<textarea name="secure_invite_default_message" id="secure_invite_default_message" style="width:60%" rows="12" cols="30">'.$secure_invite_default_message.'</textarea></p>
	
	<p><button type="submit" name="secure_invite_save_settings" class="button">' . __("Save these settings", "secure_invite") . '</button></p>
	
	</fieldset>
	</form>
	';
	
	echo '</div>
	';
}

// add the list of invitations
function secure_invite_list()
{
	global $wpdb;
	
	if (@$_GET["view"] == "")
	{
	
	echo '
	<div class="wrap">
	';
	secure_invite_wp_plugin_standard_header( "GBP", "Secure invites", "Chris Taylor", "chris@stillbreathing.co.uk", "http://wordpress.org/extend/plugins/wordpress-mu-secure-invites/" );
	echo '
	<h2>' . __("Secure invites admin", "secure_invite") . ' (<a href="wpmu-admin.php?page=secure_invite_list&amp;view=secure_invite_settings">' . __("edit settings", "secure_invite") . '</a>)</h2>
	';
	
	// if deleting
	if (@$_GET["delete"] != "")
	{
		$sql = "delete from ".$wpdb->base_prefix."invitations
				where invited_email = '" . str_replace(" ", "+", urldecode($wpdb->escape($_GET["delete"]))) . "';";
		if ($wpdb->query($sql)) {
			echo '<div id="message" class="updated fade"><p><strong>' . __("The invitation for this email address has been deleted", "secure_invite") . '</strong></p></div>';
		} else {
			echo '<div id="message" class="updated fade"><p><strong>' . __("The invitation for this email address could not be deleted", "secure_invite") . '</strong></p></div>';
		}
	}

	// check the invites table exists
	secure_invite_check_table();
	
	// show the number of invites per month
	$sql = "select UNIX_TIMESTAMP(i.datestamp) as date,
			count(i.invited_email) as invites,
			(select count(i2.invited_email)
			from ".$wpdb->base_prefix."invitations i2
			inner join ".$wpdb->base_prefix."users u2 on u2.user_email = i2.invited_email
			where year(i2.datestamp) = year(i.datestamp)
			and month(i2.datestamp) = month(i.datestamp)) as signups
			from ".$wpdb->base_prefix."invitations i
			group by month(i.datestamp)
			order by i.datestamp desc
			limit 0, 12;";
	$invites_per_month = $wpdb->get_results($sql);
	$invites_per_month_num = count($invites_per_month);	
	echo '
	<div style="float:left;width:45%">
	<h3>' . __("Invitations per month", "secure_invite") . '</h3>
	';
	if ($invites_per_month && $invites_per_month_num > 0)
	{
	echo '
	<table summary="'.__("Invitations per month", "secure_invite").'" class="widefat">
	<thead>
	<tr>
		<th>'.__("Month", "secure_invite").'</th>
		<th>'.__("Invites sent", "secure_invite").'</th>
		<th>'.__("Resulting signups", "secure_invite").'</th>
	</tr>
	</thead>
	<tbody>
	';
	foreach ($invites_per_month as $invite_month)
	{
		if ($alt == '') { $alt = ' class="alternate"'; } else { $alt = ''; }
		echo '
		<tr'.$alt.'>
			<td>'.__(date("F Y", $invite_month->date)).'</td>
			<td>'.__($invite_month->invites).'</td>
			<td>'.__($invite_month->signups).'</td>
		</tr>
		';
	}
	echo '
	</tbody>
	</table>
	';
	} else {
	echo '
	<p>'.__("No invitations sent yet", "secure_invite").'</p>
	';
	}
	echo '
	</div>
	';
	
	// get the best inviters
	$sql = "select u.user_nicename,
			count(i.invited_email) as invites,
			(select count(i2.invited_email)
			from ".$wpdb->base_prefix."invitations i2
			inner join ".$wpdb->base_prefix."users u2 on u2.user_email = i2.invited_email
			where i2.user_id = i.user_id) as signups
			from ".$wpdb->base_prefix."invitations i
			inner join ".$wpdb->base_prefix."users u on u.id = i.user_id
			group by i.user_id
			order by count(i.invited_email) desc
			limit 0, 12;";
	$best_inviters = $wpdb->get_results($sql);
	$best_inviters_num = count($best_inviters);	
	echo '
	<div style="float:right;width:45%">
	<h3>' . __("Best inviters", "secure_invite") . '</h3>
	';
	if ($best_inviters && $best_inviters_num > 0)
	{
	echo '
	<table summary="'.__("Best inviters", "secure_invite").'" class="widefat">
	<thead>
	<tr>
		<th>'.__("Name", "secure_invite").'</th>
		<th>'.__("Invites sent", "secure_invite").'</th>
		<th>'.__("Resulting signups", "secure_invite").'</th>
	</tr>
	</thead>
	<tbody>
	';
	foreach ($best_inviters as $best_inviter)
	{
		if ($alt == '') { $alt = ' class="alternate"'; } else { $alt = ''; }
		echo '
		<tr'.$alt.'>
			<td>'.__($best_inviter->user_nicename).'</td>
			<td>'.__($best_inviter->invites).'</td>
			<td>'.__($best_inviter->signups).'</td>
		</tr>
		';
	}
	echo '
	</tbody>
	</table>
	';
	} else {
	echo '
	<p>'.__("No invitations sent yet", "secure_invite").'</p>
	';
	}
	echo '
	</div>
	';
			
	// get the page
	$page = @(int)$_GET["p"];
	if ($page == "")
	{
		$page = "1";
	}
	$start = ($page * 50) -50;
	if ($start == "") { $start = 0; }
	
	// get the invites
	$sql = $wpdb->prepare("select SQL_CALC_FOUND_ROWS i.user_id, i.invited_email, UNIX_TIMESTAMP(i.datestamp) as datestamp, u.user_nicename as inviter, l.user_nicename as signed_up
			from ".$wpdb->base_prefix."invitations i
			inner join ".$wpdb->users." u on u.id = i.user_id
			left outer join ".$wpdb->users." l on l.user_email = i.invited_email
			order by i.datestamp desc
			limit %d, 50", $start);
	$invites = $wpdb->get_results($sql);

	echo '
	<h3 style="clear:both;padding-top:20px">' . __("Invitation list", "secure_invite") . '</h3>
	';
	
	$invites_num = count($invites);
	$total = $wpdb->get_var( "SELECT found_rows() AS found_rows" );
	$invites_pages = ceil($total/50);
	
	if ($invites && $invites_num > 0)
	{
		if ($invites_pages > 1)
		{
			$thisp = @$_GET["p"];
			if ($thisp == "") { $thisp = 1; }
			echo '<ul style="list-style: none;">
			';
			for ($i = 1; $i <= $invites_pages; $i++)
			{
				if ($i == $thisp)
				{
					echo '<li style="display: inline;">'.$i.'</li>
				';
				} else {
					echo '<li style="display: inline;"><a href="wpmu-admin.php?page=secure_invite_list&amp;p='.$i.'">'.$i.'</a></li>
				';
				}
			}
			echo '</ul>
			';
		}
		echo '<table summary="'.__("Invitations sent by site users", "secure_invite").'" class="widefat">
		<thead>
		<tr>
			<th>'.__("Inviter", "secure_invite").'</th>
			<th>'.__("Datestamp", "secure_invite").'</th>
			<th>'.__("Invited email", "secure_invite").'</th>
			<th>'.__("Signed up name", "secure_invite").'</th>
			<th>'.__("Delete invitation", "secure_invite").'</th>
		</tr>
		</thead>
		<tbody>
		';
		$alt = '';
		foreach ($invites as $invite)
		{
			if ($alt == '') { $alt = ' class="alternate"'; } else { $alt = ''; }
			echo '<tr'.$alt.'>
			<td>' . $invite->inviter . '</td>
			<td>' . date("F j, Y, g:i a", $invite->datestamp) . '</td>
			<td>' . $invite->invited_email . '</td>
			<td>' . $invite->signed_up . '</td>';
			if ($invite->signed_up == "") {
			echo '
			<td><a href="wpmu-admin.php?page=secure_invite_list&amp;delete='.urlencode($invite->invited_email).'">' . __("Delete", "secure_invite") . '</a></td>
			';
			} else {
			echo '
			<td></td>
			';
			}
			echo '
			</tr>
			';
		}
		echo '
		</tbody>
		</table>
		';
		if ($invites_pages > 1)
		{
			echo '<ul style="list-style: none;">
			';
			for ($i = 1; $i <= $invites_pages; $i++)
			{
				if ($i == $thisp)
				{
					echo '<li style="display: inline;">'.$i.'</li>
				';
				} else {
					echo '<li style="display: inline;"><a href="wpmu-admin.php?page=secure_invite_list&amp;p='.$i.'">'.$i.'</a></li>
				';
				}
			}
			echo '</ul>
			';
		}
	} else {
		echo '<p>' . __("No invitations sent yet.", "secure_invite") . '</p>';
	}
	
	} else {
	
		secure_invite_settings();
	
	}
	
	secure_invite_wp_plugin_standard_footer( "GBP", "Secure invites", "Chris Taylor", "chris@stillbreathing.co.uk", "http://wordpress.org/extend/plugins/wordpress-mu-secure-invites/" );
	echo '</div>';
}

// check the invites table exists
function secure_invite_check_table()
{
	global $wpdb;
	// if the invitations table does not exist
	$sql = "select count(id) from ".$wpdb->base_prefix."invitations;";
	$exists = $wpdb->get_var($sql);
	if($exists == "")
	{
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		// include the file with the required database manipulation functions
		// create the table
		$sql = "CREATE TABLE ".$wpdb->base_prefix."invitations (
id mediumint(9) NOT NULL AUTO_INCREMENT,
user_id mediumint(9),
invited_email varchar(255),
datestamp datetime,
PRIMARY KEY  (id)
);";
		dbDelta($sql);
	}
}

// show an invitation form
function secure_invite_form($success='<p class="success">Thanks, your invitation has been sent</p>', $error='<p class="error">Sorry, your invitation could not be sent</p>')
{
	// if the current user is allowed to send invites
	if (secure_invite_user_can_invite())
	{
		// if an email has been supplied
		if (@$_POST['email'] != "" && is_email($_POST['email']))
		{
			// if the invite can be sent
			if (secure_invite_send())
			{
				// show the success message
				echo $success;
			} else {
				// show the error message
				echo $error;
			}
		}
		$qs = "";
		if ($_SERVER["QUERY_STRING"] != "")
		{
			$sq = "?" . $_SERVER["QUERY_STRING"];
		}
		// show the form
		echo '
		<form action="' . $_SERVER[ "REQUEST_URI" ] . $qs . '" method="get" class="secure_invite_form">
		<fieldset>
			<p><label for="secure_invite_name">' . __("Name of person to invite", "secure_invite") . '</label>
			<input name="name" type="text" id="secure_invite_name" value="" /></p>
			<p><label for="secure_invite_email">' . __("Email of person to invite", "secure_invite") . '</label>
			<input name="email" type="text" id="secure_invite_email" value="" /></p>
			<p><label for="secure_invite_personalmessage">' . __("A personal message (optional)", "secure_invite") . '</label>
			<textarea rows="10" cols="50" name="personalmessage" id="secure_invite_personalmessage"></textarea></p>
			<p><label for="secure_invite_send">' . __("Send this invitation", "secure_invite") . '</label>
			<input type="submit" id="secure_invite_send" name="submit" value="' . __("Send Invitation", "secure_invite") . '" /> ' . secure_invite_user_invites_remaining() . '</p>
		</fieldset>
		</form>';
	}
}

// see if a user can send an invite
function secure_invite_user_can_invite()
{
	global $wpdb, $current_user;
	$site_registration = stripslashes( get_site_option( "registration" ) );
	// if the current user exists
	if ($current_user && $current_user->id != "" && ($site_registration == "all" || $site_registration == "user"))
	{
		// get the date this user was registered
		$registered = $wpdb->get_var($wpdb->prepare("select UNIX_TIMESTAMP(user_registered) from ".$wpdb->users." where id=%d;", $current_user->id));
		// get how many days after registration invites are locked
		$secure_invite_days_after_joining = (int)stripslashes( get_site_option("secure_invite_days_after_joining") );
		if ($secure_invite_days_after_joining == "") { $secure_invite_days_after_joining = 30; }
		// get the total number of invites a user is allowed to send
		$secure_invite_invite_limit = stripslashes( get_site_option("secure_invite_invite_limit") );
		if ($secure_invite_invite_limit == "") { $secure_invite_invite_limit = 0; }
		// get the number of invites this user has sent
		$sent = secure_invite_user_sent_invites();
		if ($registered < (time() - ($secure_invite_days_after_joining * 24 * 60 * 60)) && (($secure_invite_invite_limit=="" || $secure_invite_invite_limit==0) || $sent < $secure_invite_invite_limit))
		{
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// get the number of invites this user has sent
function secure_invite_user_sent_invites()
{
	global $wpdb, $current_user;
	return $wpdb->get_var($wpdb->prepare("select count(user_id) from ".$wpdb->base_prefix."invitations where user_id=%d", $current_user->id));
}

// show how many invites this user is allowed to send
function secure_invite_user_invites_remaining()
{
	// get the total number of invites a user is allowed to send
	$secure_invite_invite_limit = stripslashes( get_site_option("secure_invite_invite_limit") );
	if ($secure_invite_invite_limit == "") { $secure_invite_invite_limit = 0; }
	if ($secure_invite_invite_limit > 0)
	{
		// get the number of invites sent
		$sent = secure_invite_user_sent_invites();
		return __("Number of invites left to send:", "secure_invite") . " " . ($secure_invite_invite_limit - $sent);
	} else {
		return "";
	}
}

// send an invitation
function secure_invite_send()
{
	global $current_site, $current_user, $blog_id, $wpdb;
	if (secure_invite_user_can_invite())
	{
		$usernickname = $current_user->display_name;
		$to = trim($_POST['email']);
		$from = $current_user->display_name . ' <' . $current_user->user_email . '>';
		$pname = trim($_POST['name']);
		$site_url = $current_site->domain;
		$site_name = stripslashes( get_site_option("site_name") );
		
		// save the invitation 
		$sql = $wpdb->prepare("insert into ".$wpdb->base_prefix."invitations
	(user_id, invited_email, datestamp)
	values
	(%d, %s, now());", $current_user->id, $to);
								$wpdb->print_error();
		$query = $wpdb->query($sql);
		$query_error = mysql_error();
		// if the invitation could be saved
		if ($query)
		{
			if(!empty($pname)) {
				$subject = $pname.', '.$usernickname.'  has invited you to join '.$site_name;
				$message .= "Dear ".$pname.", ";
			}
			else {
				$subject = 'Hi there, '. $usernickname.'  has invited you to join '.$site_name;
				$message .= "Hi there, ";
			}
			
			$secure_invite_signup_time_limit = (int)stripslashes( get_site_option("secure_invite_signup_time_limit") );
			if ($secure_invite_signup_time_limit == "") { $secure_invite_signup_time_limit = 3; }
			
			$secure_invite_signup_page = stripslashes( get_site_option("secure_invite_signup_page") );
			if ($secure_invite_signup_page == "") { $secure_invite_signup_page = "wp-signup.php"; }
			
			$secure_invite_registration_page = stripslashes( get_site_option("secure_invite_registration_page") );
	if ($secure_invite_registration_page == "") { $secure_invite_registration_page = trim(get_bloginfo("wpurl"), '/') . "/wp-signup.php"; }
			
			$secure_invite_default_message = stripslashes( get_site_option("secure_invite_default_message") );
			if ($secure_invite_default_message == "") { $secure_invite_default_message = "----------------------------------------------------------------------------------------

	You have been invited to open a free weblog at [sitename]. To open and register for your weblog today, please visit

	[signuplink]

	Regards,

	[adminname]

	This invitation will work for the next [timeout] days. After that your invitation will expire and you will have to be invited again.

	If clicking the links in this message does not work, copy and paste them into the address bar of your browser."; }

			$secure_invite_default_message = str_replace("[sitename]", $site_name, $secure_invite_default_message);
			$secure_invite_default_message = str_replace("[signuplink]", $secure_invite_registration_page . "?" . $to, $secure_invite_default_message);
			$secure_invite_default_message = str_replace("[name]", $usernickname, $secure_invite_default_message);
			$secure_invite_default_message = str_replace("[timeout]", $secure_invite_signup_time_limit, $secure_invite_default_message);
			
			$message = $message . "\n\n" . stripslashes($_POST['personalmessage']) . "\n\n" . $secure_invite_default_message;
			
			$headers = 'From: '. $from . "\r\n" . 
						'Reply-To: ' . $from;
			wp_mail($to, $subject, $message, $headers);
			return true;
		} else {
			$headers = 'From: '. $from . "\r\n" . 
						'Reply-To: ' . $from;
			wp_mail(stripslashes( get_site_option("admin_email") ), "Secure invite failure for ".$from, "A user just tried to invite someone to join ".$site_name.". The following SQL query could not be completed:\n\n".$sql."\n\nThe error reported was:\n\n".$query_error."\n\nThis is an automatic email sent by the Secure Invites plugin.", $headers);
			return false;
		}
	} else {
		return false;
	}
}

// add an invitation to the database
function secure_invite_admin() {
	global $current_site, $current_user, $blog_id, $wpdb;

	$site_url = $current_site->domain;
	$site_name = stripslashes( get_site_option("site_name") );
	
	// check the invites table exists
	secure_invite_check_table();

	if($_POST['action']=="send")
	{
		// if the email is valid
		if(is_email($_POST['email']))
		{
			// try to send
			if (secure_invite_send())
			{
				echo '<div id="message" class="updated fade"><p><strong>'.__('Your invitation has been successfully sent to', "secure_invite").' '.$_POST['email'].'.</strong></p></div>';
				// the invitation could not be saved, show an error
			} else {
				echo '<div id="message" class="updated fade"><p><strong>'.__('Your invitation could not be sent to', "secure_invite").' '.$_POST['email'].'. '.__('Please try again. If it fails more than twice please contact the site administrator.', "secure_invite").'</strong></p></div>';
			}
		}
		else
		{
			echo '<div id="message" class="updated fade"><p><strong>'.__('Please enter a valid email address', "secure_invite").'</strong></p></div>';
		} // end error
	} // end if action is send
	
	echo '<div class="wrap">';
  echo '<h2>' . __("Invite a friend to join", "secure_invite") . ' '.$site_name.'</h2> ';
  echo '<form method="post" action="index.php?page=secure_invite"> 
		<fieldset>
			<p> 
				<label for="name" style="float:left;width:20%;">'.__('Name', "secure_invite").'</label>
				<input name="name" type="text" id="name" value="" style="float:left;width:79%;" />
			</p> 
			<p style="clear:both">
				<label for="email" style="float:left;width:20%;">'.__('Email', "secure_invite").'</label> 
				<input name="email" type="text" id="email" value="" style="float:left;width:79%;" />
			</p>
			<p style="clear:both">
				<label for="personalmessage" style="display:block">' . __("Your message", "secure_invite") . '</label>
				<textarea rows="10" cols="50" name="personalmessage" id="personalmessage" style="width:99%;height:6em">' . sprintf(__("I've been blogging at %s and thought you might like to try it out.\n\nMy blog is at %s", "secure_invite"), $site_name, get_option('home')) . '</textarea>
			</p>
			<p class="submit" style="clear:both">
				<input type="submit" name="Submit" tabindex="4" value="' . __("Send Invitation", "secure_invite") . ' &raquo;" /> ' . secure_invite_user_invites_remaining() . '
				<input type="hidden" name="action" value="send" />
			</p>
		</fieldset>
		</form>
		</div>';
}

// a standard header for your plugins, offers a PayPal donate button and link to a support page
function secure_invite_wp_plugin_standard_header( $currency = "", $plugin_name = "", $author_name = "", $paypal_address = "", $bugs_page ) {
	$r = "";
	$option = get_option( $plugin_name . " header" );
	if ( $_GET[ "header" ] != "" || $_GET["thankyou"] == "true" ) {
		update_option( $plugin_name . " header", "hide" );
		$option = "hide";
	}
	if ( $_GET["thankyou"] == "true" ) {
		$r .= '<div class="updated"><p>' . __( "Thank you for donating" ) . '</p></div>';
	}
	if ( $currency != "" && $plugin_name != "" && $_GET[ "header" ] != "hide" && $option != "hide" )
	{
		$r .= '<div class="updated">';
		$pageURL = 'http';
		if ( $_SERVER["HTTPS"] == "on" ) { $pageURL .= "s"; }
		$pageURL .= "://";
		if ( $_SERVER["SERVER_PORT"] != "80" ) {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		if ( strpos( $pageURL, "?") === false ) {
			$pageURL .= "?";
		} else {
			$pageURL .= "&";
		}
		$pageURL = htmlspecialchars( $pageURL );
		if ( $bugs_page != "" ) {
			$r .= '<p>' . sprintf ( __( 'To report bugs please visit <a href="%s">%s</a>.' ), $bugs_page, $bugs_page ) . '</p>';
		}
		if ( $paypal_address != "" && is_email( $paypal_address ) ) {
			$r .= '
			<form id="wp_plugin_standard_header_donate_form" action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_donations" />
			<input type="hidden" name="item_name" value="Donation: ' . $plugin_name . '" />
			<input type="hidden" name="business" value="' . $paypal_address . '" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="no_shipping" value="1" />
			<input type="hidden" name="rm" value="1" />
			<input type="hidden" name="currency_code" value="' . $currency . '">
			<input type="hidden" name="return" value="' . $pageURL . 'thankyou=true" />
			<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" />
			<p>';
			if ( $author_name != "" ) {
				$r .= sprintf( __( 'If you found %1$s useful please consider donating to help %2$s to continue writing free Wordpress plugins.' ), $plugin_name, $author_name );
			} else {
				$r .= sprintf( __( 'If you found %s useful please consider donating.' ), $plugin_name );
			}
			$r .= '
			<p><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="" /></p>
			</form>
			';
		}
		$r .= '<p><a href="' . $pageURL . 'header=hide" class="button">' . __( "Hide this" ) . '</a></p>';
		$r .= '</div>';
	}
	print $r;
}
function secure_invite_wp_plugin_standard_footer( $currency = "", $plugin_name = "", $author_name = "", $paypal_address = "", $bugs_page ) {
	$r = "";
	if ( $currency != "" && $plugin_name != "" )
	{
		$r .= '<form id="wp_plugin_standard_footer_donate_form" action="https://www.paypal.com/cgi-bin/webscr" method="post" style="clear:both;padding-top:50px;"><p>';
		if ( $bugs_page != "" ) {
			$r .= sprintf ( __( '<a href="%s">Bugs</a>' ), $bugs_page );
		}
		if ( $paypal_address != "" && is_email( $paypal_address ) ) {
			$r .= '
			<input type="hidden" name="cmd" value="_donations" />
			<input type="hidden" name="item_name" value="Donation: ' . $plugin_name . '" />
			<input type="hidden" name="business" value="' . $paypal_address . '" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="no_shipping" value="1" />
			<input type="hidden" name="rm" value="1" />
			<input type="hidden" name="currency_code" value="' . $currency . '">
			<input type="hidden" name="return" value="' . $pageURL . 'thankyou=true" />
			<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" />
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="' . __( "Donate" ) . ' ' . $plugin_name . '" />
			';
		}
		$r .= '</p></form>';
	}
	print $r;
}
?>