<?php
/**
 * Displaying of labels : only to be detected for poedit
 */
use function  H4APlugin\Core\get_current_plugin_domain;
$current_plugin_domain = get_current_plugin_domain();
/********************************/
/*          Description         */
/********************************/
__( "Accepts paying group registrations. Gives access to restricted content for members or groups of members.", $current_plugin_domain );

/********************************/
/*          Genereal            */
/********************************/

/* All edition */
_x( "Status", "title_postbox", $current_plugin_domain );
_x( "Saving", "title_postbox", $current_plugin_domain );
_x( "Status", "misc_postbox_label", $current_plugin_domain );
__( "Publish", $current_plugin_domain );
__( "Published", $current_plugin_domain );
_x( "Draft", "edition", $current_plugin_domain );
_x( "Add new", "menu_title", $current_plugin_domain );
__( "Move to Trash", $current_plugin_domain );
__( "Delete Permanently", $current_plugin_domain );
__( "Restore", $current_plugin_domain );
__( 'To get WP Group Subscription premium options, <a href="%s">please activate your license key</a>.', $current_plugin_domain );

/********************************/
/*             Plans            */
/********************************/

_x( "The plan", "message_item_name", $current_plugin_domain );

_x( "Plans", "menu_title", $current_plugin_domain );
_x( "All Plans", "menu_title", $current_plugin_domain );
_x( "Search plans", "search-list-item", $current_plugin_domain );
_x( "Plan Name", "plans", $current_plugin_domain );
_x( "Author", "plans", $current_plugin_domain );
_x( "Start Date", "plans", $current_plugin_domain );
_x( "All", "plans", $current_plugin_domain );
_nx( "Published", "Published", 1, "plans", $current_plugin_domain );
_nx( "Draft", "Drafts", 1, "plans", $current_plugin_domain );
_x( "Trash", "plans", $current_plugin_domain );
_x( "name", "search-list-item", $current_plugin_domain );

/* Plan edition */
_x( "Plans", "page_title", $current_plugin_domain );
_x( "New Plan", "menu_title", $current_plugin_domain );
_x( "New Plan", "new_page_title", $current_plugin_domain );
_x( "Edit Plan", "menu_title", $current_plugin_domain );
_x( "Edit Plan", "edit_page_title", $current_plugin_domain );
_x( "plan", "title_display", $current_plugin_domain );
_x( "plan", "title_display_placeholder", $current_plugin_domain );
_x( "Plan", "title_postbox", $current_plugin_domain );
_x( "Plan", "misc_postbox_label", $current_plugin_domain );
_x( "plan", "edit-error", $current_plugin_domain );
_x( "The plan", "editable_item", $current_plugin_domain );
__( "plan", $current_plugin_domain );
__( "Price", $current_plugin_domain );
__( "Add currency", $current_plugin_domain );
__( "Change currency", $current_plugin_domain );
__( "Free", $current_plugin_domain );
__( "Plan duration", $current_plugin_domain );
__( "Valid until", $current_plugin_domain );
__( "Unlimited", $current_plugin_domain );
__( "Plan type", $current_plugin_domain );
__( "Single", $current_plugin_domain );
__( "Group", $current_plugin_domain );
__( "Minimum of member accounts", $current_plugin_domain );
__( "Maximum of member accounts", $current_plugin_domain );
__( "Change ceiling", $current_plugin_domain );
__( "Account Creation Form", $current_plugin_domain );
__( "New form plan", $current_plugin_domain );
__( "Basic single subscription form", $current_plugin_domain );
__( "Basic multiple subscription form", $current_plugin_domain );

/********************************/
/*            Members           */
/********************************/

_x( "The member", "message_item_name", $current_plugin_domain );

_x( "Members", "menu_title", $current_plugin_domain );
_x( "All Members", "menu_title", $current_plugin_domain );
_x( "Member Name", "members", $current_plugin_domain );
_x( "Member Name", "members", $current_plugin_domain );
_x( "Email", "members", $current_plugin_domain );
_x( "Group Name", "members", $current_plugin_domain );
_x( "Last Connection", "members", $current_plugin_domain );
_x( "Last Activation", "members", $current_plugin_domain );
_x( "Start Date", "members", $current_plugin_domain );
_x( "All", "members", $current_plugin_domain );
_nx( "Published", "Published", 1, "members", $current_plugin_domain );
_nx( "Trash", "Trash", 1, "members", $current_plugin_domain );
_x( "Trash", "members", $current_plugin_domain );
_x( "Search members", "search-list-item", $current_plugin_domain );
_x( "First name", "search-list-item", $current_plugin_domain );
_x( "Last name", "search-list-item", $current_plugin_domain );
_x( "Email", "search-list-item", $current_plugin_domain );
_x( "Group name", "search-list-item", $current_plugin_domain );

/* Member edition */
_x( "New Member", "menu_title", $current_plugin_domain );
_x( "New Member", "new_page_title", $current_plugin_domain );
_x( "Edit Member", "menu_title", $current_plugin_domain );
_x( "Edit Member", "edit_page_title", $current_plugin_domain );
_x( "The member", "editable_item", $current_plugin_domain );
_x( "member", "edit-error", $current_plugin_domain );



/********************************/
/*          Accounting          */
/********************************/

_x( "Accounting", "menu_title", $current_plugin_domain );
_x( "Accounting Overview", "page_title", $current_plugin_domain );
_x( "Overview", "menu_title", $current_plugin_domain );

/* Subcribers */

_x( "The subscriber", "message_item_name", $current_plugin_domain );

_x( "Subscriber Accounts", "page_title", $current_plugin_domain );
_x( "Subscriber Accounts", "menu_title", $current_plugin_domain );
_x( "Subs. Accounts", "menu_title", $current_plugin_domain );
_x( "Subs. Number", "subscribers", $current_plugin_domain );
_x( "Representative", "subscribers", $current_plugin_domain );
_x( "Group Name", "subscribers", $current_plugin_domain );
_x( "Plan", "subscribers", $current_plugin_domain );
_x( "Start Date", "subscribers", $current_plugin_domain );
_x( "Last subscription", "subscribers", $current_plugin_domain );
_x( "Search subscribers", "search-list-item", $current_plugin_domain );
_x( "First name", "search-list-item", $current_plugin_domain );
_x( "Last name", "search-list-item", $current_plugin_domain );
_x( "Email", "search-list-item", $current_plugin_domain );
_x( "Group name", "search-list-item", $current_plugin_domain );
_x( "Plan name", "search-list-item", $current_plugin_domain );
_x( "All", "subscribers", $current_plugin_domain );
_nx( "Disabled", "Disabled", 1, "subscribers", $current_plugin_domain );
_nx( "Active", "Active", 1, "subscribers", $current_plugin_domain );
_nx( "Trash", "Trash", 1, "subscribers", $current_plugin_domain );
_x( "Trash", "subscribers", $current_plugin_domain );

/* Subcriber edition */
_x( "New account", "menu_title", $current_plugin_domain );
_x( "New Subscriber", "menu_title", $current_plugin_domain ); //to write it in the accounting page and in the <title>
_x( "New Subscriber", "new_page_title", $current_plugin_domain );
_x( "Edit Subscriber", "menu_title", $current_plugin_domain );
_x( "Edit Subscriber", "edit_page_title", $current_plugin_domain );
_x( "The subscriber", "editable_item", $current_plugin_domain );
_x( "subscriber", "edit-error", $current_plugin_domain );
_x( "Disabled", "edition", $current_plugin_domain );
_x( "Active", "edition", $current_plugin_domain );

/* Payments */

_x( "The payment", "message_item_name", $current_plugin_domain );

_x( "Payments", "page_title", $current_plugin_domain );
_x( "Payments", "menu_title", $current_plugin_domain );
_x( "All", "payments", $current_plugin_domain );
_nx( "Assigned", "Assigned", 1, "payments", $current_plugin_domain );
_nx( "Unassigned", "Unassigned", 1, "payments", $current_plugin_domain );
_x( "Number", "payments", $current_plugin_domain );
_x( "Status", "payments", $current_plugin_domain );
_x( "Date", "payments", $current_plugin_domain );
_x( "Email", "payments", $current_plugin_domain );
_x( "Amount", "payments", $current_plugin_domain );
_x( "Type", "payments", $current_plugin_domain );
_x( "Subscriber account", "payments", $current_plugin_domain );
_x( "Plan", "payments", $current_plugin_domain );
_x( "Transaction", "payments", $current_plugin_domain );


/********************************/
/*          Settings            */
/********************************/

_x( "Settings - WP Group Subscription", "page_title", $current_plugin_domain );
__( "Verification API secret key is invalid", $current_plugin_domain );
__( "License key activated", $current_plugin_domain );
__( "The license key has been deactivated for this domain", $current_plugin_domain );

__( "currency", $current_plugin_domain );
__( "paypal", $current_plugin_domain );
__( "profile-page", $current_plugin_domain );
__( "premium", $current_plugin_domain );
__( "recaptcha", $current_plugin_domain );
__( "plans", $current_plugin_domain );
__( "license-key", $current_plugin_domain );