/*
 *
 *  This file contains all WordPress i18n calls in the plugin javaScript.
 *  It exists solely so WordPress translation tools can detect strings.
 */
import { __, _n, sprintf } from "@wordpress/i18n";

/* eslint-disable no-unused-vars */
const variable = "";
const translations = [
  __("New!", "advanced-database-cleaner"),
  __("Collapse sidebar", "advanced-database-cleaner"),
  __("Expand sidebar", "advanced-database-cleaner"),
  __("Free", "advanced-database-cleaner"),
  __("Pro-lifetime", "advanced-database-cleaner"),
  __("Premium", "advanced-database-cleaner"),
  __("By", "advanced-database-cleaner"),
  __("Support", "advanced-database-cleaner"),
  __("Docs", "advanced-database-cleaner"),
  __("Pro - lifetime", "advanced-database-cleaner"),
  __("Please activate your license key to receive plugin updates.", "advanced-database-cleaner"),
  __("Activate now", "advanced-database-cleaner"),
  __(
    "There is an issue with your license. Please check your license status in the settings.",
    "advanced-database-cleaner"
  ),
  __("Check license status", "advanced-database-cleaner"),
  __(
    "You are now using the newest premium version of the plugin, would you like to import your data from the previous version?",
    "advanced-database-cleaner"
  ),
  __(
    "For technical reasons, all automation tasks imported from the previous version have been paused. Please review your tasks and reactivate those you wish to continue using.",
    "advanced-database-cleaner"
  ),
  __("Go to automation", "advanced-database-cleaner"),
  __("Help", "advanced-database-cleaner"),
  __("General cleanup", "advanced-database-cleaner"),
  __("Tables", "advanced-database-cleaner"),
  __("Options", "advanced-database-cleaner"),
  __("Post meta", "advanced-database-cleaner"),
  __("User meta", "advanced-database-cleaner"),
  __("Transients", "advanced-database-cleaner"),
  __("Cron jobs", "advanced-database-cleaner"),
  __("Post types", "advanced-database-cleaner"),
  __("WooCommerce", "advanced-database-cleaner"),
  __("Automation", "advanced-database-cleaner"),
  __("DB analytics", "advanced-database-cleaner"),
  __("Addons activity", "advanced-database-cleaner"),
  __("Info & logs", "advanced-database-cleaner"),
  __("Settings", "advanced-database-cleaner"),
  __("Orphaned product variations", "advanced-database-cleaner"),
  __("Variations no longer attached to any parent product.", "advanced-database-cleaner"),
  __("Orphaned customer analytics", "advanced-database-cleaner"),
  __(
    "WooCommerce customer-lookup rows referencing users that no longer exist.",
    "advanced-database-cleaner"
  ),
  __("Expired customer sessions", "advanced-database-cleaner"),
  __(
    "Customer session rows in wp_woocommerce_sessions whose expiry date has passed.",
    "advanced-database-cleaner"
  ),
  sprintf(
    // translators: %s is the REST URL the plugin tried to reach.
    __(
      "The plugin could not reach the WordPress REST API at %s. This usually means the REST URL prefix has been customized on your site (for example /wp-json/ changed to /api/) without applying the 'rest_url_prefix' filter. Please contact your developer or hosting support.",
      "advanced-database-cleaner"
    ),
    variable
  ),
  sprintf(
    /* translators: %d: number of tables */
    _n("Empty %d table?", "Empty %d tables?", variable, "advanced-database-cleaner"),
    variable
  ),
  __("Once emptied, this action cannot be undone!", "advanced-database-cleaner"),
  __("Empty", "advanced-database-cleaner"),
  __("Convert table to InnoDB?", "advanced-database-cleaner"),
  __(
    "Converting a table to InnoDB will alter its storage engine. Are you sure you want to proceed?",
    "advanced-database-cleaner"
  ),
  __("Convert", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d: number of items */
    _n("Delete %d item?", "Delete %d items?", variable, "advanced-database-cleaner"),
    variable
  ),
  __("Once deleted, this action cannot be undone!", "advanced-database-cleaner"),
  __("Delete", "advanced-database-cleaner"),
  __("Completed successfully!", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d: number of items that could not be processed */
    _n(
      " %d item could not be processed.",
      " %d items could not be processed.",
      variable,
      "advanced-database-cleaner"
    ),
    variable
  ),
  __("year", "advanced-database-cleaner"),
  __("years", "advanced-database-cleaner"),
  __("month", "advanced-database-cleaner"),
  __("months", "advanced-database-cleaner"),
  __("week", "advanced-database-cleaner"),
  __("weeks", "advanced-database-cleaner"),
  __("day", "advanced-database-cleaner"),
  __("days", "advanced-database-cleaner"),
  __("hour", "advanced-database-cleaner"),
  __("hours", "advanced-database-cleaner"),
  __("min", "advanced-database-cleaner"),
  __("mins", "advanced-database-cleaner"),
  __("sec", "advanced-database-cleaner"),
  __("secs", "advanced-database-cleaner"),
  __("just now", "advanced-database-cleaner"),
  sprintf(
    /* translators: %1$d: number of days */
    _n("%1$d day", "%1$d days", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %1$d: number of hours */
    _n("%1$d hour", "%1$d hours", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %1$d: number of minutes */
    _n("%1$d minute", "%1$d minutes", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %1$d: number of seconds */
    _n("%1$d second", "%1$d seconds", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: time units string (e.g., "2 hours 30 minutes") */
    __("%s ago", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %d: number of years */
    _n("%d year", "%d years", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %d: number of months */
    _n("%d month", "%d months", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %d: number of days */
    _n("%d day", "%d days", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %d: number of hours */
    _n("%d hour", "%d hours", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %d: number of minutes */
    _n("%d min", "%d mins", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %d: number of seconds */
    _n("%d sec", "%d secs", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %d: number of seconds (always 0 in this case) */
    _n("%d second", "%d seconds", variable, "advanced-database-cleaner"),
    variable
  ),
  __("All tables", "advanced-database-cleaner"),
  __("Plugins tables", "advanced-database-cleaner"),
  __("Themes tables", "advanced-database-cleaner"),
  __("WP tables", "advanced-database-cleaner"),
  __("All options", "advanced-database-cleaner"),
  __("Plugins options", "advanced-database-cleaner"),
  __("Themes options", "advanced-database-cleaner"),
  __("WP options", "advanced-database-cleaner"),
  __("All post meta", "advanced-database-cleaner"),
  __("Plugins meta", "advanced-database-cleaner"),
  __("Themes meta", "advanced-database-cleaner"),
  __("WP meta", "advanced-database-cleaner"),
  __("All user meta", "advanced-database-cleaner"),
  __("All transients", "advanced-database-cleaner"),
  __("Plugins transients", "advanced-database-cleaner"),
  __("Themes transients", "advanced-database-cleaner"),
  __("WP transients", "advanced-database-cleaner"),
  __("All cron jobs", "advanced-database-cleaner"),
  __("Plugins cron jobs", "advanced-database-cleaner"),
  __("Themes cron jobs", "advanced-database-cleaner"),
  __("WP cron jobs", "advanced-database-cleaner"),
  __("All post types", "advanced-database-cleaner"),
  __("Plugins post types", "advanced-database-cleaner"),
  __("Themes post types", "advanced-database-cleaner"),
  __("WP post types", "advanced-database-cleaner"),
  __("All items", "advanced-database-cleaner"),
  __("Plugins items", "advanced-database-cleaner"),
  __("Themes items", "advanced-database-cleaner"),
  __("WP items", "advanced-database-cleaner"),
  __("[Filter]", "advanced-database-cleaner"),
  __("No data found", "advanced-database-cleaner"),
  __("Try changing the filters or search term", "advanced-database-cleaner"),
  __("Loading data...", "advanced-database-cleaner"),
  __("Site", "advanced-database-cleaner"),
  __("ID", "advanced-database-cleaner"),
  __("Title", "advanced-database-cleaner"),
  __("Content", "advanced-database-cleaner"),
  __("Size", "advanced-database-cleaner"),
  __("Date (UTC)", "advanced-database-cleaner"),
  __("Author", "advanced-database-cleaner"),
  __("Post ID", "advanced-database-cleaner"),
  __("Meta key", "advanced-database-cleaner"),
  __("Meta value", "advanced-database-cleaner"),
  __("Customer lookup ID", "advanced-database-cleaner"),
  __("Missing user ID", "advanced-database-cleaner"),
  __("Email address", "advanced-database-cleaner"),
  __("Username", "advanced-database-cleaner"),
  __("Object ID", "advanced-database-cleaner"),
  __("Term taxonomy ID", "advanced-database-cleaner"),
  __("Term order", "advanced-database-cleaner"),
  __("Name", "advanced-database-cleaner"),
  __("Value", "advanced-database-cleaner"),
  __("Autoload", "advanced-database-cleaner"),
  __(
    "Indicates whether a transient is autoloaded or not. Values to autoload are: yes, on, auto, auto-on. Values to not autoload are: no, off, auto-off",
    "advanced-database-cleaner"
  ),
  __("Expired at", "advanced-database-cleaner"),
  __("All dates/times are in your local time zone", "advanced-database-cleaner"),
  __("Found in", "advanced-database-cleaner"),
  __("Hook", "advanced-database-cleaner"),
  __("Args", "advanced-database-cleaner"),
  __("Scheduled at (UTC)", "advanced-database-cleaner"),
  __("Status", "advanced-database-cleaner"),
  __("Log ID", "advanced-database-cleaner"),
  __("Action ID", "advanced-database-cleaner"),
  __("Message", "advanced-database-cleaner"),
  __("Log date (UTC)", "advanced-database-cleaner"),
  __("Revisions", "advanced-database-cleaner"),
  __("Auto drafts", "advanced-database-cleaner"),
  __("Trashed posts", "advanced-database-cleaner"),
  __("Unapproved comments", "advanced-database-cleaner"),
  __("Spam comments", "advanced-database-cleaner"),
  __("Trashed comments", "advanced-database-cleaner"),
  __("Pingbacks", "advanced-database-cleaner"),
  __("Trackbacks", "advanced-database-cleaner"),
  __("Unused postmeta", "advanced-database-cleaner"),
  __("Duplicated postmeta", "advanced-database-cleaner"),
  __("Unused commentmeta", "advanced-database-cleaner"),
  __("Duplicated commentmeta", "advanced-database-cleaner"),
  __("Unused usermeta", "advanced-database-cleaner"),
  __("Duplicated usermeta", "advanced-database-cleaner"),
  __("Unused termmeta", "advanced-database-cleaner"),
  __("Duplicated termmeta", "advanced-database-cleaner"),
  __("Unused relationships", "advanced-database-cleaner"),
  __("Expired transients", "advanced-database-cleaner"),
  __("oEmbed caches", "advanced-database-cleaner"),
  __("Actionscheduler completed actions", "advanced-database-cleaner"),
  __("Actionscheduler failed actions", "advanced-database-cleaner"),
  __("Actionscheduler canceled actions", "advanced-database-cleaner"),
  __("Actionscheduler completed logs", "advanced-database-cleaner"),
  __("Actionscheduler failed logs", "advanced-database-cleaner"),
  __("Actionscheduler canceled logs", "advanced-database-cleaner"),
  __("Actionscheduler orphan logs", "advanced-database-cleaner"),
  __("Orphaned product variations", "advanced-database-cleaner"),
  __("Orphaned customer analytics", "advanced-database-cleaner"),
  __("Repair tables", "advanced-database-cleaner"),
  __("Optimize tables", "advanced-database-cleaner"),
  __("Completed actions", "advanced-database-cleaner"),
  __("Failed actions", "advanced-database-cleaner"),
  __("Canceled actions", "advanced-database-cleaner"),
  __("Completed logs", "advanced-database-cleaner"),
  __("Failed logs", "advanced-database-cleaner"),
  __("Canceled logs", "advanced-database-cleaner"),
  __("Orphan logs", "advanced-database-cleaner"),
  __("KB", "advanced-database-cleaner"),
  __("0 days", "advanced-database-cleaner"),
  __("Run cleanup", "advanced-database-cleaner"),
  __("Success!", "advanced-database-cleaner"),
  __("Done!", "advanced-database-cleaner"),
  __("Successfully saved!", "advanced-database-cleaner"),
  __("Error!", "advanced-database-cleaner"),
  __("Unknown error occurred!", "advanced-database-cleaner"),
  __("Info!", "advanced-database-cleaner"),
  __("Start a scan for tables", "advanced-database-cleaner"),
  __("Start a scan for options", "advanced-database-cleaner"),
  __("Start a scan for post meta", "advanced-database-cleaner"),
  __("Start a scan for user meta", "advanced-database-cleaner"),
  __("Start a scan for transients", "advanced-database-cleaner"),
  __("Start a scan for cron jobs", "advanced-database-cleaner"),
  __("Start a scan for post types", "advanced-database-cleaner"),
  __(
    "We couldn't verify your license. Please check that your license key is active for the current website.",
    "advanced-database-cleaner"
  ),
  __(
    "You don't have enough credits to send additional remote requests.",
    "advanced-database-cleaner"
  ),
  __(
    "The remote database cannot scan such a large number of items. Contact the plugin developer for more information.",
    "advanced-database-cleaner"
  ),
  __(
    "An error occurred during the remote request. Check the logs for more details.",
    "advanced-database-cleaner"
  ),
  __(
    "The remote server is being prepared for maintenance. Please try again later.",
    "advanced-database-cleaner"
  ),
  __(
    "The remote server is under maintenance. Please try again later.",
    "advanced-database-cleaner"
  ),
  __("Invalid credit code. Please check and try again.", "advanced-database-cleaner"),
  __(
    "This credit code has already been redeemed. Please close this pop-up and click the 'Refresh credits info' button to refresh your credit balance",
    "advanced-database-cleaner"
  ),
  __("Invalid or empty system information", "advanced-database-cleaner"),
  __("Generated on:", "advanced-database-cleaner"),
  __("[Server time]", "advanced-database-cleaner"),
  __("No data available", "advanced-database-cleaner"),
  __("Invalid table prefix!", "advanced-database-cleaner"),
  __("(to optimize)", "advanced-database-cleaner"),
  __("Name not found!", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: percentage value (e.g. "12.5") - meaning this table's size vs total database size */
    __("%s%% of total database size", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: formatted time interval */
    __("Every %s", "advanced-database-cleaner"),
    variable
  ),
  __("Defined in:", "advanced-database-cleaner"),
  __("Yes", "advanced-database-cleaner"),
  __("Expires at:", "advanced-database-cleaner"),
  __("(local time)", "advanced-database-cleaner"),
  __("In", "advanced-database-cleaner"),
  __("Never expires", "advanced-database-cleaner"),
  __("No", "advanced-database-cleaner"),
  __("Current Database Size", "advanced-database-cleaner"),
  __(
    "You must first activate your plugin license before synchronizing your balance.",
    "advanced-database-cleaner"
  ),
  __(
    "No remote scan credits available. Buy or redeem credits to use remote scan.",
    "advanced-database-cleaner"
  ),
  __(
    "Remote scan daily limit reached. Wait for reset or upgrade for higher limits.",
    "advanced-database-cleaner"
  ),
  __("Remote Scan Credits", "advanced-database-cleaner"),
  __("Remote Scan Credits:", "advanced-database-cleaner"),
  __("Recommended for WooCommerce stores", "advanced-database-cleaner"),
  __("Speed up & clean your WooCommerce store with WOptimize", "advanced-database-cleaner"),
  __(
    "WOptimize is the ultimate solution to clean and speed up your WooCommerce store, it removes old orders, expired sessions, orphaned product and customer data, stale carts and caches, then optimizes what remains, all on autopilot.",
    "advanced-database-cleaner"
  ),
  __("Exclusive deal for you", "advanced-database-cleaner"),
  __("Need more WooCommerce optimization?", "advanced-database-cleaner"),
  __(
    "We built WOptimize, an advanced cleaner that hunts down WooCommerce bloat and clears it in one click: Old orders, Orphaned products/customers meta, expired sessions, etc.",
    "advanced-database-cleaner"
  ),
  __("Faster checkout", "advanced-database-cleaner"),
  __("Lighter database", "advanced-database-cleaner"),
  __("Safe one-click cleanup", "advanced-database-cleaner"),
  __("Dismiss", "advanced-database-cleaner"),
  __("Launch offer", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d: discount percentage. */
    __("%d%% OFF", "advanced-database-cleaner"),
    variable
  ),
  __("Use code", "advanced-database-cleaner"),
  __("I want to try it", "advanced-database-cleaner"),
  __("Not supported!", "advanced-database-cleaner"),
  __("Cancel", "advanced-database-cleaner"),
  __("Don't forget to make a backup of your database first!", "advanced-database-cleaner"),
  __("Successful", "advanced-database-cleaner"),
  __("Failed", "advanced-database-cleaner"),
  __("Partially successful", "advanced-database-cleaner"),
  __(
    "The Free and old Pro versions can be uninstalled since you are using the newest Premium version",
    "advanced-database-cleaner"
  ),
  __(
    "The Free version can be uninstalled since you are using the Premium version",
    "advanced-database-cleaner"
  ),
  __(
    "The old Pro version can be uninstalled since you are using the newest Premium version",
    "advanced-database-cleaner"
  ),
  __("Manual corrections", "advanced-database-cleaner"),
  __("Keep Last", "advanced-database-cleaner"),
  __("Automation tasks", "advanced-database-cleaner"),
  __("Uninstall old versions", "advanced-database-cleaner"),
  __("Import completed", "advanced-database-cleaner"),
  __("Here are the results of your data import", "advanced-database-cleaner"),
  __("Close", "advanced-database-cleaner"),
  __("No data to import. You can uninstall the previous version(s)", "advanced-database-cleaner"),
  __("Import data from previous version", "advanced-database-cleaner"),
  __("Select the items you want to import or actions to perform", "advanced-database-cleaner"),
  __("Import manual corrections", "advanced-database-cleaner"),
  __(
    "Import your custom manual corrections you made in the previous version",
    "advanced-database-cleaner"
  ),
  __("Import Keep Last", "advanced-database-cleaner"),
  __("Preserve your -Keep Last- settings and configurations", "advanced-database-cleaner"),
  __("Import tasks", "advanced-database-cleaner"),
  __("Import all your automation tasks and schedules.", "advanced-database-cleaner"),
  __(
    "(For technical reasons, tasks will be deactivated after import and you will need to activate them again)",
    "advanced-database-cleaner"
  ),
  __("Uninstall previous versions?", "advanced-database-cleaner"),
  __("Processing ...", "advanced-database-cleaner"),
  __("Proceed", "advanced-database-cleaner"),
  __("Processing, please wait...", "advanced-database-cleaner"),
  __("View documentation", "advanced-database-cleaner"),
  __("Access comprehensive guides and tutorials", "advanced-database-cleaner"),
  __("Find answers to common questions", "advanced-database-cleaner"),
  __("Contact us", "advanced-database-cleaner"),
  __("Contact us for personalized assistance", "advanced-database-cleaner"),
  __("Get help with technical issues, billing ...", "advanced-database-cleaner"),
  __("Read more", "advanced-database-cleaner"),
  __("Confirm", "advanced-database-cleaner"),
  __("All", "advanced-database-cleaner"),
  __("Activation", "advanced-database-cleaner"),
  __("Deactivation", "advanced-database-cleaner"),
  __("Uninstall", "advanced-database-cleaner"),
  __("No activity found!", "advanced-database-cleaner"),
  __(
    "No activity found for the applied filters. Try different filters.",
    "advanced-database-cleaner"
  ),
  __(
    "Once you activate, deactivate or uninstall addons, they will appear here.",
    "advanced-database-cleaner"
  ),
  __(
    "This module tracks plugin and theme activity, including activation, deactivation, and uninstallation, and provides a complete history for debugging and site analysis.",
    "advanced-database-cleaner"
  ),
  __(
    "(Only the current site will be monitored. Activities on child sites will not be tracked).",
    "advanced-database-cleaner"
  ),
  __(
    "You disabled this module. Please enable it in the settings to start recording addon activities.",
    "advanced-database-cleaner"
  ),
  __("Go to settings", "advanced-database-cleaner"),
  __("Search for", "advanced-database-cleaner"),
  __("Addon name or slug", "advanced-database-cleaner"),
  __("Activity type", "advanced-database-cleaner"),
  __("Filter", "advanced-database-cleaner"),
  __("Total activities", "advanced-database-cleaner"),
  __("Refresh", "advanced-database-cleaner"),
  __("Reset filters", "advanced-database-cleaner"),
  __("(All dates/times are in your local time zone)", "advanced-database-cleaner"),
  __("Unlock Addons Activity Timeline", "advanced-database-cleaner"),
  __("Upgrade to the Premium to access the addons activity timeline.", "advanced-database-cleaner"),
  __("Complete activity timeline", "advanced-database-cleaner"),
  __("See when an addon was activated or removed", "advanced-database-cleaner"),
  __("Search and filter activities", "advanced-database-cleaner"),
  __("Upgrade to Premium", "advanced-database-cleaner"),
  __("Learn more about Premium features", "advanced-database-cleaner"),
  __("Overview", "advanced-database-cleaner"),
  __("Tables analytics", "advanced-database-cleaner"),
  __("Last analytics execution was successful on:", "advanced-database-cleaner"),
  __("Last analytics execution failed on:", "advanced-database-cleaner"),
  __(
    "You disabled this module. Please enable it in the settings to track database changes.",
    "advanced-database-cleaner"
  ),
  __(" (local time)", "advanced-database-cleaner"),
  __("Note: Analytics data is refreshed every 24 hours.", "advanced-database-cleaner"),
  __("Database size", "advanced-database-cleaner"),
  __("Total tables", "advanced-database-cleaner"),
  __("Tables added", "advanced-database-cleaner"),
  __("Tables deleted", "advanced-database-cleaner"),
  __("Daily", "advanced-database-cleaner"),
  __("Monthly", "advanced-database-cleaner"),
  __("Last 7 days", "advanced-database-cleaner"),
  __("Last 30 days", "advanced-database-cleaner"),
  __("Last 90 days", "advanced-database-cleaner"),
  __("Please select valid start and end dates.", "advanced-database-cleaner"),
  __("Start date cannot be after end date.", "advanced-database-cleaner"),
  __("Quick ranges", "advanced-database-cleaner"),
  __("From", "advanced-database-cleaner"),
  __("To", "advanced-database-cleaner"),
  __("Apply", "advanced-database-cleaner"),
  __("Refresh analytics", "advanced-database-cleaner"),
  __("Tables count", "advanced-database-cleaner"),
  __("Raw data", "advanced-database-cleaner"),
  __("Date", "advanced-database-cleaner"),
  __("DB size (MB)", "advanced-database-cleaner"),
  __("Tables added on:", "advanced-database-cleaner"),
  __("view", "advanced-database-cleaner"),
  __("Tables deleted on:", "advanced-database-cleaner"),
  __("Unlock Database Analytics", "advanced-database-cleaner"),
  __(
    "Upgrade to the Premium to access comprehensive database analytics.",
    "advanced-database-cleaner"
  ),
  __("Database size chart", "advanced-database-cleaner"),
  __("Tables count chart", "advanced-database-cleaner"),
  __("See exactly tables added/deleted", "advanced-database-cleaner"),
  __("Custom date range filtering", "advanced-database-cleaner"),
  __("View charts by day or month", "advanced-database-cleaner"),
  __("Select tables ...", "advanced-database-cleaner"),
  __("Search...", "advanced-database-cleaner"),
  __("Unselect all", "advanced-database-cleaner"),
  __("No tables match your search.", "advanced-database-cleaner"),
  __("Total rows", "advanced-database-cleaner"),
  __("Total columns", "advanced-database-cleaner"),
  __("Rows", "advanced-database-cleaner"),
  __("Columns", "advanced-database-cleaner"),
  __("Table size", "advanced-database-cleaner"),
  __("Please select a table!", "advanced-database-cleaner"),
  __("Select one or more tables to view analytics.", "advanced-database-cleaner"),
  __("Unlock Tables Analytics", "advanced-database-cleaner"),
  __(
    "Upgrade to the Premium to access comprehensive tables analytics.",
    "advanced-database-cleaner"
  ),
  __("Tables size charts", "advanced-database-cleaner"),
  __("Individual or multiple table selection", "advanced-database-cleaner"),
  __("Track tables rows & columns changes", "advanced-database-cleaner"),
  __("Once", "advanced-database-cleaner"),
  __("Once hourly", "advanced-database-cleaner"),
  __("Twice daily", "advanced-database-cleaner"),
  __("Once daily", "advanced-database-cleaner"),
  __("Once weekly", "advanced-database-cleaner"),
  __("Once monthly", "advanced-database-cleaner"),
  __("Days", "advanced-database-cleaner"),
  __("Preserve items from the last X days from being cleaned.", "advanced-database-cleaner"),
  __("Items", "advanced-database-cleaner"),
  __("(premium)", "advanced-database-cleaner"),
  __(
    "Keep the last X items for each parent (e.g., the last 5 revisions per post). If an item has no parent, keep the last X items globally.",
    "advanced-database-cleaner"
  ),
  __("Available in the premium version.", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: WooCommerce cleanup item label. */
    __("WooCommerce: %s", "advanced-database-cleaner"),
    variable
  ),
  __("Action Scheduler completed actions", "advanced-database-cleaner"),
  __("Action Scheduler failed actions", "advanced-database-cleaner"),
  __("Action Scheduler canceled actions", "advanced-database-cleaner"),
  __("Action Scheduler completed logs", "advanced-database-cleaner"),
  __("Action Scheduler failed logs", "advanced-database-cleaner"),
  __("Action Scheduler canceled logs", "advanced-database-cleaner"),
  __("Action Scheduler orphan logs", "advanced-database-cleaner"),
  __("Name & start date are required", "advanced-database-cleaner"),
  __("Updated successfully!", "advanced-database-cleaner"),
  __("Created successfully!", "advanced-database-cleaner"),
  __("'Keep last' not applicable", "advanced-database-cleaner"),
  __("Keep last", "advanced-database-cleaner"),
  __("Back", "advanced-database-cleaner"),
  __("Select items to clean", "advanced-database-cleaner"),
  __("Select items…", "advanced-database-cleaner"),
  __("items selected", "advanced-database-cleaner"),
  __("Unselect All", "advanced-database-cleaner"),
  __("Select All", "advanced-database-cleaner"),
  __("In premium", "advanced-database-cleaner"),
  __("Premium Features Selected", "advanced-database-cleaner"),
  __(
    "The following operations are only available in the premium version and will not be cleaned:",
    "advanced-database-cleaner"
  ),
  __("Premium only", "advanced-database-cleaner"),
  __("Will not be cleaned", "advanced-database-cleaner"),
  __("Select actions to do", "advanced-database-cleaner"),
  __("Update task", "advanced-database-cleaner"),
  __("Create task", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d: number of selected items */
    __("%d selected", "advanced-database-cleaner"),
    variable
  ),
  __("Task name", "advanced-database-cleaner"),
  __("Frequency", "advanced-database-cleaner"),
  __("Start time", "advanced-database-cleaner"),
  __("Local time", "advanced-database-cleaner"),
  __("Active", "advanced-database-cleaner"),
  __("Paused", "advanced-database-cleaner"),
  __("Summary", "advanced-database-cleaner"),
  __("Items to clean", "advanced-database-cleaner"),
  __("Premium items (won't be cleaned)", "advanced-database-cleaner"),
  __("Actions to do", "advanced-database-cleaner"),
  __("No operations selected yet", "advanced-database-cleaner"),
  __("more", "advanced-database-cleaner"),
  __("Deleted successfully!", "advanced-database-cleaner"),
  __("Delete task?", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: task name */
    __("Task to delete: %s", "advanced-database-cleaner"),
    variable
  ),
  __("New automated task", "advanced-database-cleaner"),
  __("Free Plan", "advanced-database-cleaner"),
  sprintf(
    /* translators: 1: number of tasks used, 2: total task limit */
    __("%1$d/%2$d tasks used", "advanced-database-cleaner"),
    variable,
    // %1$d
    variable

    // %2$d
  ),
  __("Upgrade", "advanced-database-cleaner"),
  __("Total Tasks", "advanced-database-cleaner"),
  __("Active Tasks", "advanced-database-cleaner"),
  __("Paused Tasks", "advanced-database-cleaner"),
  __("No tasks found!", "advanced-database-cleaner"),
  __("You don't have any tasks matching the selected filter.", "advanced-database-cleaner"),
  __("Get started by creating your first automated cleaning task.", "advanced-database-cleaner"),
  __("Unlock Premium Automation", "advanced-database-cleaner"),
  __("Upgrade to create unlimited tasks with advanced features.", "advanced-database-cleaner"),
  __("Unlimited Automated Tasks", "advanced-database-cleaner"),
  __("Create as many automation tasks as you need.", "advanced-database-cleaner"),
  __("Many Premium Features", "advanced-database-cleaner"),
  __("Access advanced cleaning options, analytics, and more.", "advanced-database-cleaner"),
  __("Priority Support", "advanced-database-cleaner"),
  __("Get expert assistance and early access to new features.", "advanced-database-cleaner"),
  __("Maybe Later", "advanced-database-cleaner"),
  __("30-day money-back guarantee. No questions asked.", "advanced-database-cleaner"),
  __("Start date:", "advanced-database-cleaner"),
  __("Frequency:", "advanced-database-cleaner"),
  __("Last run:", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: time ago string */
    __("Executed %s", "advanced-database-cleaner"),
    variable
  ),
  __("Next run:", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: remaining time string */
    __("Runs in %s", "advanced-database-cleaner"),
    variable
  ),
  __("Running now", "advanced-database-cleaner"),
  __("Items to clean:", "advanced-database-cleaner"),
  __("Created:", "advanced-database-cleaner"),
  __("Edited:", "advanced-database-cleaner"),
  __("never", "advanced-database-cleaner"),
  __("Edit", "advanced-database-cleaner"),
  __("Events log", "advanced-database-cleaner"),
  __("premium", "advanced-database-cleaner"),
  __("Cleared successfully!", "advanced-database-cleaner"),
  __("Clear these events log?", "advanced-database-cleaner"),
  __("Clear", "advanced-database-cleaner"),
  __("Cleanup events log for:", "advanced-database-cleaner"),
  __("items cleaned", "advanced-database-cleaner"),
  __("No events recorded", "advanced-database-cleaner"),
  __("Events will appear here when the task runs.", "advanced-database-cleaner"),
  __("Unlock Cleanup Events Log", "advanced-database-cleaner"),
  __(
    "Upgrade to the Premium to access detailed logs of your cleanup tasks.",
    "advanced-database-cleaner"
  ),
  __("Detailed tasks events logging", "advanced-database-cleaner"),
  __("See what was cleaned and when", "advanced-database-cleaner"),
  __("Clear events log anytime you want", "advanced-database-cleaner"),
  __("Priority support", "advanced-database-cleaner"),
  __("Clear log", "advanced-database-cleaner"),
  __("Next run", "advanced-database-cleaner"),
  __("Action", "advanced-database-cleaner"),
  __("Interval", "advanced-database-cleaner"),
  __("Belongs to", "advanced-database-cleaner"),
  __(
    "The plugin or theme the item belongs to, determined after running a scan. If uncertain, an estimated likelihood (%) is shown, the higher the percentage, the more likely the item belongs to that plugin/theme.",
    "advanced-database-cleaner"
  ),
  __("Bulk actions", "advanced-database-cleaner"),
  __("Scan", "advanced-database-cleaner"),
  __(
    "The scan allows the identification of the plugins or themes to which the items belong.",
    "advanced-database-cleaner"
  ),
  __("Edit -Belongs to-", "advanced-database-cleaner"),
  __(
    "You can edit the scan results to correctly associate items with their respective plugins or themes if you are certain of their belonging.",
    "advanced-database-cleaner"
  ),
  __("Delete the selected items.", "advanced-database-cleaner"),
  __("Posts", "advanced-database-cleaner"),
  __("Comments", "advanced-database-cleaner"),
  __("Meta / relations", "advanced-database-cleaner"),
  __("Unused post meta", "advanced-database-cleaner"),
  __("Duplicated post meta", "advanced-database-cleaner"),
  __("Unused comment meta", "advanced-database-cleaner"),
  __("Duplicated comment meta", "advanced-database-cleaner"),
  __("Unused user meta", "advanced-database-cleaner"),
  __("Duplicated user meta", "advanced-database-cleaner"),
  __("Unused term meta", "advanced-database-cleaner"),
  __("Duplicated term meta", "advanced-database-cleaner"),
  __("Action Scheduler", "advanced-database-cleaner"),
  __("Database", "advanced-database-cleaner"),
  __("Run selected cleanups", "advanced-database-cleaner"),
  __("Cleanup completed successfully!", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: item label in lowercase */
    __("Clean all %s?", "advanced-database-cleaner"),
    variable
  ),
  __("Once cleaned, this action cannot be undone!", "advanced-database-cleaner"),
  __("Clean up", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d: number of selected items */
    __("Clean %d selected items?", "advanced-database-cleaner"),
    variable
  ),
  __("items to clean up", "advanced-database-cleaner"),
  __("You can save:", "advanced-database-cleaner"),
  __("Calculating...", "advanced-database-cleaner"),
  __("All items cleaned!", "advanced-database-cleaner"),
  __("Auto count", "advanced-database-cleaner"),
  __(
    "When enabled, the plugin automatically counts cleanable items and estimates the potential space savings. You can disable it for items with a large number of entries to improve performance.",
    "advanced-database-cleaner"
  ),
  __("Count", "advanced-database-cleaner"),
  __("Lost space", "advanced-database-cleaner"),
  __("View", "advanced-database-cleaner"),
  __(
    "Keep data from the last X days from being displayed or cleaned. The plugin will only show and clean data older than the number of days or items you specify.",
    "advanced-database-cleaner"
  ),
  __("0 KB", "advanced-database-cleaner"),
  __("N/A", "advanced-database-cleaner"),
  __("Unlock the Action Scheduler Cleanup", "advanced-database-cleaner"),
  __(
    "Upgrade to the Premium to clean up old or unused Action Scheduler data.",
    "advanced-database-cleaner"
  ),
  __("Loading settings...", "advanced-database-cleaner"),
  __("Enter number", "advanced-database-cleaner"),
  __("of data", "advanced-database-cleaner"),
  __("per post", "advanced-database-cleaner"),
  __("in total", "advanced-database-cleaner"),
  __("No items are kept with this configuration.", "advanced-database-cleaner"),
  __("Apply this to all other items (if applicable)", "advanced-database-cleaner"),
  __("Saving...", "advanced-database-cleaner"),
  __("Save", "advanced-database-cleaner"),
  __("The retention by items is available in the premium version.", "advanced-database-cleaner"),
  __("System info", "advanced-database-cleaner"),
  __("Errors log", "advanced-database-cleaner"),
  __("WP debug", "advanced-database-cleaner"),
  __("Clear the log?", "advanced-database-cleaner"),
  __("Are you sure you want to clear the log?", "advanced-database-cleaner"),
  __("Empty!", "advanced-database-cleaner"),
  __("Download file", "advanced-database-cleaner"),
  __("Copied", "advanced-database-cleaner"),
  __("Copy", "advanced-database-cleaner"),
  __(
    "(All dates/times in the logs are shown in your server's time zone)",
    "advanced-database-cleaner"
  ),
  __("Option name", "advanced-database-cleaner"),
  __(
    "Indicates whether an option is autoloaded or not. Values to autoload are: yes, on, auto, auto-on. Values to not autoload are: no, off, auto-off",
    "advanced-database-cleaner"
  ),
  __(
    "The plugin or theme the item belongs to, determined after running a scan. If uncertain, an estimated likelihood (%) is shown, the higher the percentage, the more likely the item belongs to that plugin/theme.",
    "advanced-database-cleaner"
  ),
  __(
    "The scan allows the identification of the plugins or themes to which the items belong.",
    "advanced-database-cleaner"
  ),
  __(
    "You can edit the scan results to correctly associate items with their respective plugins or themes if you are certain of their belonging.",
    "advanced-database-cleaner"
  ),
  __("Set autoload to Yes", "advanced-database-cleaner"),
  __(
    "Setting autoload to Yes can decrease the performance of your website if you have a lot of options set to autoload.",
    "advanced-database-cleaner"
  ),
  __("Set autoload to No", "advanced-database-cleaner"),
  __(
    "Setting autoload to No can improve the performance of your website by reducing the number of options loaded on each page.",
    "advanced-database-cleaner"
  ),
  __("Post type", "advanced-database-cleaner"),
  __("Posts count", "advanced-database-cleaner"),
  __("Visibility", "advanced-database-cleaner"),
  __(
    "Whether a post type is intended for use publicly either via the admin interface or by front-end users",
    "advanced-database-cleaner"
  ),
  __("Delete posts", "advanced-database-cleaner"),
  __("Delete all posts of the selected post types.", "advanced-database-cleaner"),
  __("19 standard cleanup tools", "advanced-database-cleaner"),
  __(
    "Revisions, Auto drafts, unused postmeta, unused usermeta, expired transients, and more.",
    "advanced-database-cleaner"
  ),
  __("Preview before you clean", "advanced-database-cleaner"),
  __(
    "Inspect revisions, unused post meta, and other items with full context prior to deletion.",
    "advanced-database-cleaner"
  ),
  __("Retention by date", "advanced-database-cleaner"),
  __("Keep the latest items per day and delete older ones.", "advanced-database-cleaner"),
  __("6 data managers", "advanced-database-cleaner"),
  __(
    "Manage and deeply clean Tables, Options, Post Meta, User Meta, Transients, and Cron Jobs.",
    "advanced-database-cleaner"
  ),
  __("Autoload health check", "advanced-database-cleaner"),
  __(
    "Check autoloaded options and spot heavy offenders. Then flip autoload on/off as needed.",
    "advanced-database-cleaner"
  ),
  __("Basic filters", "advanced-database-cleaner"),
  __(
    "Quickly locate items to clean, optimize, or manage with preset filters.",
    "advanced-database-cleaner"
  ),
  __("Retention by count", "advanced-database-cleaner"),
  __("Keep the last N items per post or entity and delete the rest.", "advanced-database-cleaner"),
  __("Advanced filters", "advanced-database-cleaner"),
  __("Filter items by name patterns, metadata, and precise criteria.", "advanced-database-cleaner"),
  __("Local scan", "advanced-database-cleaner"),
  __(
    "Scan options, tables, post meta, and more to identify their plugin/theme owners and detect orphans.",
    "advanced-database-cleaner"
  ),
  __("Remote SmartScan™", "advanced-database-cleaner"),
  __(
    "Cross-check your items with a curated cloud database for improved ownership accuracy.",
    "advanced-database-cleaner"
  ),
  __("Who uses this item?", "advanced-database-cleaner"),
  __(
    "After a remote scan, see which plugins/themes rely on a given option, table, etc.",
    "advanced-database-cleaner"
  ),
  __("Assign specific items to the correct plugin/theme when needed.", "advanced-database-cleaner"),
  __("Contribute corrections", "advanced-database-cleaner"),
  __(
    "Submit your verified corrections to improve global scan accuracy.",
    "advanced-database-cleaner"
  ),
  __("Action Scheduler cleaners", "advanced-database-cleaner"),
  __(
    "7 dedicated tools for canceled, failed, and completed actions and logs.",
    "advanced-database-cleaner"
  ),
  __("Create scheduled tasks", "advanced-database-cleaner"),
  __("Automate routine cleanups exactly when you want them.", "advanced-database-cleaner"),
  __("Up to 5 tasks", "advanced-database-cleaner"),
  __("Unlimited", "advanced-database-cleaner"),
  __("Execution logs for scheduled tasks", "advanced-database-cleaner"),
  __("View detailed logs for every scheduled task you create.", "advanced-database-cleaner"),
  __("Database analytics overview", "advanced-database-cleaner"),
  __(
    "Track total DB size and table count over time; drill into daily or monthly changes.",
    "advanced-database-cleaner"
  ),
  __("Tables growth analytics", "advanced-database-cleaner"),
  __(
    "Monitor per-table size and row count trends to spot issues early.",
    "advanced-database-cleaner"
  ),
  __("Add-ons activity monitor", "advanced-database-cleaner"),
  __(
    "See when plugins are activated, deactivated, or uninstalled, then filter the timeline.",
    "advanced-database-cleaner"
  ),
  __("Multisite support", "advanced-database-cleaner"),
  __("Designed for networks, manage cleanup across sites.", "advanced-database-cleaner"),
  __("Limited", "advanced-database-cleaner"),
  __("Full", "advanced-database-cleaner"),
  __("Multisite: filter by site", "advanced-database-cleaner"),
  __("Target specific sub-sites when reviewing or cleaning data.", "advanced-database-cleaner"),
  __("Advanced scan settings & CPU control", "advanced-database-cleaner"),
  __("Tune scan depth and resource usage to fit your hosting limits.", "advanced-database-cleaner"),
  __("Priority email support", "advanced-database-cleaner"),
  __("Skip the queue and get help from the developers faster.", "advanced-database-cleaner"),
  __("You're using the Free version", "advanced-database-cleaner"),
  __("You're using the Premium version", "advanced-database-cleaner"),
  __(
    "Upgrade to Premium for the most accurate, safe database cleanup",
    "advanced-database-cleaner"
  ),
  __("FAQ", "advanced-database-cleaner"),
  __("Pre-sale Question", "advanced-database-cleaner"),
  __("Features", "advanced-database-cleaner"),
  __("Installed", "advanced-database-cleaner"),
  __("Upgrade now", "advanced-database-cleaner"),
  __("All premium plans include a 30-day money-back guarantee", "advanced-database-cleaner"),
  __("License", "advanced-database-cleaner"),
  __("Manage your license from this section.", "advanced-database-cleaner"),
  __("Menu placement", "advanced-database-cleaner"),
  __("Select where to display the plugin menu.", "advanced-database-cleaner"),
  __("Hide/show tabs", "advanced-database-cleaner"),
  __("Select which tabs to hide or show in the plugin menu.", "advanced-database-cleaner"),
  __("Performance settings", "advanced-database-cleaner"),
  __(
    "Configure performance-related settings for database cleanup operations and optimization.",
    "advanced-database-cleaner"
  ),
  __("Scan settings", "advanced-database-cleaner"),
  __(
    "The scan process identifies to which plugin/theme a table, option, or other element belongs. You can customize this process using the scan settings below.",
    "advanced-database-cleaner"
  ),
  __("Other settings", "advanced-database-cleaner"),
  __("Other settings to configure the plugin behavior.", "advanced-database-cleaner"),
  __("Remote scan credits", "advanced-database-cleaner"),
  __(
    "Enhancing local scan accuracy via remote server analysis. Each credit enables one request to improve detection of tables, options, and other elements linked to specific plugins and themes.",
    "advanced-database-cleaner"
  ),
  __("Table name", "advanced-database-cleaner"),
  __("Type", "advanced-database-cleaner"),
  __("Overhead", "advanced-database-cleaner"),
  __(
    "Total disk space wasted by table overhead, which can be recovered by optimizing the table.",
    "advanced-database-cleaner"
  ),
  __("Optimize", "advanced-database-cleaner"),
  __(
    "The optimization reorganizes the physical storage of table data to reduce storage space and improve efficiency when accessing the table",
    "advanced-database-cleaner"
  ),
  __("Repair", "advanced-database-cleaner"),
  __(
    "Repair a possibly corrupted table (for certain storage engines only).",
    "advanced-database-cleaner"
  ),
  __("Convert to InnoDB", "advanced-database-cleaner"),
  __("Convert the table storage engine to InnoDB.", "advanced-database-cleaner"),
  __("Refresh the statistics for the selected tables.", "advanced-database-cleaner"),
  __("Empty rows", "advanced-database-cleaner"),
  __(
    "Delete all data from a table without deleting the table itself.",
    "advanced-database-cleaner"
  ),
  __("Delete the table with all its data.", "advanced-database-cleaner"),
  __("Transient name", "advanced-database-cleaner"),
  __(
    "Indicates whether an transient is autoloaded or not. Values to autoload are: yes, on, auto, auto-on. Values to not autoload are: no, off, auto-off",
    "advanced-database-cleaner"
  ),
  __("Expired", "advanced-database-cleaner"),
  __(
    "Setting autoload to Yes can decrease the performance of your website if you have a lot of transients set to autoload.",
    "advanced-database-cleaner"
  ),
  __(
    "Setting autoload to No can improve the performance of your website by reducing the number of transients loaded on each page.",
    "advanced-database-cleaner"
  ),
  __("User ID", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: cleanup item label. */
    __("Purge %s?", "advanced-database-cleaner"),
    variable
  ),
  __("This action cannot be undone!", "advanced-database-cleaner"),
  __(
    "All items will be permanently deleted. This action cannot be undone!",
    "advanced-database-cleaner"
  ),
  __("Purge", "advanced-database-cleaner"),
  __("Purge all item types?", "advanced-database-cleaner"),
  __(
    "All matching items will be permanently deleted. This action cannot be undone!",
    "advanced-database-cleaner"
  ),
  __("Purge all", "advanced-database-cleaner"),
  __("Purge completed successfully!", "advanced-database-cleaner"),
  __(
    "WooCommerce is not installed or activated. Please install and activate WooCommerce to use this feature.",
    "advanced-database-cleaner"
  ),
  __("Refresh all", "advanced-database-cleaner"),
  __("Purging…", "advanced-database-cleaner"),
  __("No count", "advanced-database-cleaner"),
  __("Scanning…", "advanced-database-cleaner"),
  sprintf(
    /* translators: 1: item count, 2: human-readable size. */
    __("View %1$s items (%2$s)", "advanced-database-cleaner"),
    variable,
    variable
  ),
  __("items", "advanced-database-cleaner"),
  __("n/a", "advanced-database-cleaner"),
  __("All clean", "advanced-database-cleaner"),
  __("Inactive", "advanced-database-cleaner"),
  __("Not installed", "advanced-database-cleaner"),
  __("Known plugins using this item", "advanced-database-cleaner"),
  __("Known themes using this item", "advanced-database-cleaner"),
  __("No data available!", "advanced-database-cleaner"),
  __("Name:", "advanced-database-cleaner"),
  __("Belongs to:", "advanced-database-cleaner"),
  __("Not scanned yet!", "advanced-database-cleaner"),
  __("[scan]", "advanced-database-cleaner"),
  __("upgrade", "advanced-database-cleaner"),
  __("Belongs to WordPress", "advanced-database-cleaner"),
  __("Cannot be deleted or edited!", "advanced-database-cleaner"),
  __("You manually corrected this items!", "advanced-database-cleaner"),
  __("More info", "advanced-database-cleaner"),
  __("No action selected!", "advanced-database-cleaner"),
  __("Please select an action to apply!", "advanced-database-cleaner"),
  __("No items selected!", "advanced-database-cleaner"),
  __("Please select at least one item to apply the action!", "advanced-database-cleaner"),
  sprintf(
    /* translators: %1s: number of selected items */
    __("Apply to selected (%1s)", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %1s: number of selected items */
    __("Apply (%1s)", "advanced-database-cleaner"),
    variable
  ),
  __(
    "Items below have invalid prefixes, indicating they likely originate from other WordPress installations or unrelated projects.",
    "advanced-database-cleaner"
  ),
  __(
    "Items below seem to be orphaned. However, please ensure you only delete entries you are certain are safe to remove.",
    "advanced-database-cleaner"
  ),
  __(
    "Upgrade to Premium to scan the items below and identify which plugin or theme they belong to.",
    "advanced-database-cleaner"
  ),
  __(
    "Items below have not been scanned yet. Click the button above to scan them and identify their associated plugin or theme.",
    "advanced-database-cleaner"
  ),
  __(
    "Items below are non-public post types with a high number of posts. Only delete them if you are sure they are safe to remove.",
    "advanced-database-cleaner"
  ),
  sprintf(
    /* translators: %1$s: autoload size, %2$s: autoload limit */
    __(
      "Your autoload size (%1$s) exceeds your WordPress recommendation (%2$s). Reducing it may improve performance.",
      "advanced-database-cleaner"
    ),
    variable,
    variable
  ),
  __(
    "Items below exceed 150KB, which is considered large! Please review them.",
    "advanced-database-cleaner"
  ),
  __("Transients below are expired. You can safely delete them.", "advanced-database-cleaner"),
  __(
    "Items below are duplicate post meta entries. You can safely delete them, as the original entries will be preserved automatically.",
    "advanced-database-cleaner"
  ),
  __(
    "Items below are unused post meta (linked to missing posts). You can safely delete them.",
    "advanced-database-cleaner"
  ),
  __(
    "Items below are duplicate user meta entries. You can safely delete them, as the original entries will be preserved automatically.",
    "advanced-database-cleaner"
  ),
  __(
    "Items below are unused user meta (linked to missing users). You can safely delete them.",
    "advanced-database-cleaner"
  ),
  __(
    "The cron jobs below have no registered actions. Review and delete those that are obsolete.",
    "advanced-database-cleaner"
  ),
  __("Note:", "advanced-database-cleaner"),
  __(
    "Transients with autoload enabled also increase the autoload size. Check the Transients tab as well.",
    "advanced-database-cleaner"
  ),
  __("Items per page", "advanced-database-cleaner"),
  __("Installed plugin", "advanced-database-cleaner"),
  __("Installed theme", "advanced-database-cleaner"),
  __("WordPress", "advanced-database-cleaner"),
  __("Orphan", "advanced-database-cleaner"),
  __("Don't assign to any category", "advanced-database-cleaner"),
  __("Assign to custom addon", "advanced-database-cleaner"),
  __("Select a plugin", "advanced-database-cleaner"),
  __("Select a theme", "advanced-database-cleaner"),
  __(
    "A scan is in progress. Please wait until it finishes before performing this action",
    "advanced-database-cleaner"
  ),
  __(
    "Use a maximum of 200 lowercase letters, numbers, hyphens, and underscores only.",
    "advanced-database-cleaner"
  ),
  __("Enter the addon's name using a maximum of 200 characters.", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d: number of selected items */
    __("Assign the %d selected item(s) to:", "advanced-database-cleaner"),
    variable
  ),
  __(
    "Items will not be assigned to any plugin/theme and will be marked as 'not scanned'",
    "advanced-database-cleaner"
  ),
  __("Assign items to a plugin or theme that is no longer installed.", "advanced-database-cleaner"),
  __("Saved custom addons", "advanced-database-cleaner"),
  __("Search saved custom addons", "advanced-database-cleaner"),
  __("No custom addons found.", "advanced-database-cleaner"),
  __("Addon type", "advanced-database-cleaner"),
  __("Plugin", "advanced-database-cleaner"),
  __("Theme", "advanced-database-cleaner"),
  __("Slug", "advanced-database-cleaner"),
  __("e.g. my-custom-plugin", "advanced-database-cleaner"),
  __("Addon's name", "advanced-database-cleaner"),
  __("e.g. My Custom Plugin", "advanced-database-cleaner"),
  __("This correction will be sent anonymously to our server", "advanced-database-cleaner"),
  __(
    "You have chosen to submit your manual corrections to the plugin server. To change this, please navigate to the plugin settings page",
    "advanced-database-cleaner"
  ),
  __("Send this correction anonymously to the plugin server?", "advanced-database-cleaner"),
  __("Post type:", "advanced-database-cleaner"),
  __("No data found.", "advanced-database-cleaner"),
  __("Show all", "advanced-database-cleaner"),
  __("Hide all", "advanced-database-cleaner"),
  __("No credits found associated with your license.", "advanced-database-cleaner"),
  __("Failed to sync balance.", "advanced-database-cleaner"),
  __("Synchronize balance", "advanced-database-cleaner"),
  __(
    "You must first activate your plugin license before synchronizing your balance.",
    "advanced-database-cleaner"
  ),
  __("Go to settings to activate license", "advanced-database-cleaner"),
  __("Synchronizing your balance...", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d: number of remaining credits */
    __("You have %d remaining credits to use.", "advanced-database-cleaner"),
    variable
  ),
  __("Your balance has been synchronized.", "advanced-database-cleaner"),
  __("OK", "advanced-database-cleaner"),
  __("Please enter a credit code.", "advanced-database-cleaner"),
  __("An error occurred.", "advanced-database-cleaner"),
  __("Failed to update balance.", "advanced-database-cleaner"),
  __("Redeem credit code", "advanced-database-cleaner"),
  __(
    "You must first activate your plugin license before redeeming a credit code.",
    "advanced-database-cleaner"
  ),
  __("Credit code", "advanced-database-cleaner"),
  __("Enter your credit code", "advanced-database-cleaner"),
  __("Redeeming...", "advanced-database-cleaner"),
  __("Redeem", "advanced-database-cleaner"),
  __("Where to find my credit codes?", "advanced-database-cleaner"),
  __("Done", "advanced-database-cleaner"),
  __("Updating balance...", "advanced-database-cleaner"),
  __("Update balance", "advanced-database-cleaner"),
  __(
    "Enhancing local scan accuracy via remote server analysis. Each credit enables one request to improve detection of tables, options, and other elements linked to specific plugins and themes.",
    "advanced-database-cleaner"
  ),
  __("Table:", "advanced-database-cleaner"),
  __("Table Rows", "advanced-database-cleaner"),
  __("Table Structure", "advanced-database-cleaner"),
  __("No structure data available.", "advanced-database-cleaner"),
  __("Indexes", "advanced-database-cleaner"),
  __("Table Status", "advanced-database-cleaner"),
  __("Create Statement", "advanced-database-cleaner"),
  __("No columns found.", "advanced-database-cleaner"),
  __("Primary key", "advanced-database-cleaner"),
  __("Index", "advanced-database-cleaner"),
  __("No indexes found.", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d is the number of columns in this index */
    __("%d column(s) in this index", "advanced-database-cleaner"),
    variable
  ),
  __("Unique", "advanced-database-cleaner"),
  __("No status data available.", "advanced-database-cleaner"),
  __("Property", "advanced-database-cleaner"),
  __("No create statement available.", "advanced-database-cleaner"),
  __(
    "The SQL statement used to create this table. Useful for documentation or recreating the table structure elsewhere.",
    "advanced-database-cleaner"
  ),
  __("Show original value", "advanced-database-cleaner"),
  __("Show formatted value", "advanced-database-cleaner"),
  __("Refresh data", "advanced-database-cleaner"),
  _n("item", "items", variable, "advanced-database-cleaner"),
  __("[Filter applied]", "advanced-database-cleaner"),
  __("of", "advanced-database-cleaner"),
  __("Public", "advanced-database-cleaner"),
  __("Non-public", "advanced-database-cleaner"),
  __("Invalid", "advanced-database-cleaner"),
  __("Disabled", "advanced-database-cleaner"),
  __("Invalid item ID", "advanced-database-cleaner"),
  __("Item name mismatch", "advanced-database-cleaner"),
  __("Site inactive", "advanced-database-cleaner"),
  __("Lifetime", "advanced-database-cleaner"),
  _x(
    "F j, Y",
    "License expiration date format (e.g. December 10, 2025)",
    "advanced-database-cleaner"
  ),
  __(
    "Your license has expired. Please renew it to continue receiving updates and support. Click the 'My Account' link below to access your account and renew your license.",
    "advanced-database-cleaner"
  ),
  __(
    "Your license is expiring soon. It should automatically renew if your payment method is valid.",
    "advanced-database-cleaner"
  ),
  __(
    "Your license is invalid. Please deactivate and enter a valid license key.",
    "advanced-database-cleaner"
  ),
  __(
    "Your license has been disabled. Please note that this license will no longer receive updates. (contact support if you think this is a mistake)",
    "advanced-database-cleaner"
  ),
  __(
    "Your license does not match this product. Please deactivate and enter a valid license key for Advanced Database Cleaner.",
    "advanced-database-cleaner"
  ),
  __(
    "Your license is inactive for this site. Please deactivate it and activate it again.",
    "advanced-database-cleaner"
  ),
  __("Starter plan", "advanced-database-cleaner"),
  __("Business plan", "advanced-database-cleaner"),
  __("Standard plan", "advanced-database-cleaner"),
  __("Agency plan", "advanced-database-cleaner"),
  __("Starter plan (lifetime)", "advanced-database-cleaner"),
  __("Standard plan (lifetime)", "advanced-database-cleaner"),
  __("Business plan (lifetime)", "advanced-database-cleaner"),
  __("Agency plan (lifetime)", "advanced-database-cleaner"),
  __("Unknown plan", "advanced-database-cleaner"),
  __("License key", "advanced-database-cleaner"),
  __("Deactivate license", "advanced-database-cleaner"),
  __("Activate license", "advanced-database-cleaner"),
  __("License status", "advanced-database-cleaner"),
  __("Expiration date", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s is the plan name for the license key*/
    __("You are on the %s.", "advanced-database-cleaner"),
    variable
  ),
  __(
    "If you need more activations, you can upgrade by paying the difference between plans.",
    "advanced-database-cleaner"
  ),
  __("Refreshing ..", "advanced-database-cleaner"),
  __("Refresh info", "advanced-database-cleaner"),
  __("My account", "advanced-database-cleaner"),
  __("[In main site]", "advanced-database-cleaner"),
  __("At least one menu position must be enabled.", "advanced-database-cleaner"),
  __("Network admin menu", "advanced-database-cleaner"),
  __(
    "Places the plugin menu on the left side of your WP Network Admin.",
    "advanced-database-cleaner"
  ),
  __("Left sidebar menu", "advanced-database-cleaner"),
  __("Places the plugin menu on the left side of your WP Admin", "advanced-database-cleaner"),
  __("Submenu under tools", "advanced-database-cleaner"),
  __("Places the plugin menu under the WP Tools menu", "advanced-database-cleaner"),
  __("Disable confirmation on cleanup actions?", "advanced-database-cleaner"),
  __(
    "If you disable this, all cleanup actions (like delete and empty) will be executed immediately after you click on the clean button, without showing a confirmation modal. This could lead to accidental data loss, as you won't have the chance to review your action. Are you sure?",
    "advanced-database-cleaner"
  ),
  __("Allow actions on WordPress items?", "advanced-database-cleaner"),
  __(
    "If you disable this, you will be able to take actions (like delete) on items that belong to WordPress core. This could break your site. Are you sure?",
    "advanced-database-cleaner"
  ),
  __("Enable analytics", "advanced-database-cleaner"),
  __(
    "If enabled, the plugin will run a daily task to analyze your database and tables locally, so you can view relevant statistics in the plugin dashboard.",
    "advanced-database-cleaner"
  ),
  __("Enable addons activity", "advanced-database-cleaner"),
  __(
    "If enabled, the plugin will track the activity of your plugins/themes when they get activated, deactivated or uninstalled. Providing a complete history of your addons activity.",
    "advanced-database-cleaner"
  ),
  __("Show tables with invalid prefix", "advanced-database-cleaner"),
  __(
    "If enabled, the plugin will list every table in your database, including those with invalid prefix, typically belonging to other WordPress installations or unrelated projects. If you're unsure, keep this option disabled.",
    "advanced-database-cleaner"
  ),
  __("Prevent taking action on WordPress items", "advanced-database-cleaner"),
  __(
    "If enabled, the plugin will prevent taking dangerous actions (like delete) on items that belong to WordPress core, in order to prevent breaking the site. We recommend keeping this enabled for safety reasons.",
    "advanced-database-cleaner"
  ),
  __("Always show confirmation on dangerous actions", "advanced-database-cleaner"),
  __(
    "If disabled, dangerous actions (like delete and empty) will be executed immediately after you click on the corresponding clean button, without showing a confirmation modal. This could lead to accidental data loss, as you won't have the chance to review your action. We recommend keeping this enabled for safety reasons.",
    "advanced-database-cleaner"
  ),
  __("Disable", "advanced-database-cleaner"),
  __("Please enter a valid number", "advanced-database-cleaner"),
  sprintf(
    /* translators: 1: minimum value, 2: maximum value */
    __("Value must be between %1$s and %2$s", "advanced-database-cleaner"),
    variable,
    // %1$s
    variable

    // %2$s
  ),
  __("Direct SQL queries", "advanced-database-cleaner"),
  __(
    "This method uses direct SQL queries for cleanup operations. This can be faster but may bypass some WordPress mechanisms. For example, when deleting trashed posts, this method will directly remove the posts from the database without triggering associated hooks and actions, which may leave related metadata, taxonomies, and other linked data intact and potentially lead to extra orphaned items.",
    "advanced-database-cleaner"
  ),
  __("Native WP functions", "advanced-database-cleaner"),
  __(
    "This method uses WordPress native functions for cleanup operations. This ensures compatibility with WordPress but may be slower. For example, when deleting trashed posts, this method will trigger all associated hooks and actions to delete related metadata, taxonomies, and other linked data, ensuring a thorough cleanup.",
    "advanced-database-cleaner"
  ),
  __(
    "Choose the method used for database cleanup operations. SQL offers direct database manipulation, while Native uses WordPress functions.",
    "advanced-database-cleaner"
  ),
  __("Cleanup method", "advanced-database-cleaner"),
  __("Database rows batches", "advanced-database-cleaner"),
  __(
    "Specifies the number of rows to process in each batch when reading database tables. Reducing this value can help optimize performance and memory usage when dealing with large tables.",
    "advanced-database-cleaner"
  ),
  __("Save settings", "advanced-database-cleaner"),
  __("Today's usage", "advanced-database-cleaner"),
  __("Total credits used:", "advanced-database-cleaner"),
  sprintf(
    // translators: %s is the number of remaining credits
    __("(You have %s credits left)", "advanced-database-cleaner"),
    variable
  ),
  __("(You have used all your credits)", "advanced-database-cleaner"),
  __("Credits used today:", "advanced-database-cleaner"),
  __("Daily limit will reset in:", "advanced-database-cleaner"),
  __("Last info refresh:", "advanced-database-cleaner"),
  __("Refreshing...", "advanced-database-cleaner"),
  __("Refresh credits info", "advanced-database-cleaner"),
  __("Buy credits", "advanced-database-cleaner"),
  __(
    "You have reached your daily limit for remote server requests. Please wait for your credits to reset.",
    "advanced-database-cleaner"
  ),
  __(
    "You have reached your daily limit for remote server requests. Please wait for your credits to reset or upgrade your license for a higher limit.",
    "advanced-database-cleaner"
  ),
  __("Upgrade my license", "advanced-database-cleaner"),
  __(
    "You have used all your total credits. Buy more credits or redeem a credit code to continue using the remote scan.",
    "advanced-database-cleaner"
  ),
  __(
    "No credits found for your Pro license. Please buy or redeem credits to use the remote scan feature.",
    "advanced-database-cleaner"
  ),
  __("Where to track my credits usage?", "advanced-database-cleaner"),
  sprintf(
    /* translators: 1: minimum value, 2: maximum value */
    __("Value must be between %1$s and %2$s", "advanced-database-cleaner"),
    variable,
    // %1$s
    variable

    // %2$s
  ),
  sprintf(
    /* translators: 1: minimum value, 2: maximum value */
    __("Value must be between %1$s and %2$s", "advanced-database-cleaner"),
    variable,
    // %1$s
    variable

    // %2$s
  ),
  sprintf(
    /* translators: 1: minimum value, 2: maximum value */
    __("Value must be between %1$s and %2$s", "advanced-database-cleaner"),
    variable,
    // %1$s
    variable

    // %2$s
  ),
  sprintf(
    /* translators: 1: minimum value, 2: maximum value */
    __("Value must be between %1$s and %2$s", "advanced-database-cleaner"),
    variable,
    // %1$s
    variable

    // %2$s
  ),
  sprintf(
    /* translators: 1: minimum value, 2: maximum value */
    __(
      "Value must be between %1$s and %2$s, or 0 for the default value.",
      "advanced-database-cleaner"
    ),
    variable,
    // %1$s
    variable

    // %2$s
  ),
  __("Use full CPU power", "advanced-database-cleaner"),
  __(
    "When enabled, the plugin will utilize the full CPU power during scans, which may lead to faster scan times but could impact server performance. Disabling this option will make the plugin use less CPU, potentially reducing performance impact on your server during scans.",
    "advanced-database-cleaner"
  ),
  __("CPU work time", "advanced-database-cleaner"),
  __(
    "Specifies the duration (in milliseconds) for which the plugin can utilize the CPU before pausing. Try decreasing this value if you notice performance issues during scans, especially on shared hosting environments.",
    "advanced-database-cleaner"
  ),
  __("CPU pause time", "advanced-database-cleaner"),
  __(
    "Specifies the duration (in milliseconds) for which the plugin pauses to allow other processes to utilize the CPU. Try increasing this value if you notice performance issues during scans, especially on shared hosting environments.",
    "advanced-database-cleaner"
  ),
  __("File line batches", "advanced-database-cleaner"),
  __(
    "Specifies the number of lines to process in each batch when reading files. Adjusting this value can help optimize performance and memory usage.",
    "advanced-database-cleaner"
  ),
  __("File content chunks (in KB)", "advanced-database-cleaner"),
  __(
    "Specifies the size (in kilobytes) of the content chunk read from a file in each iteration. Reducing this value may help prevent memory issues when processing large files.",
    "advanced-database-cleaner"
  ),
  __("Max execution time", "advanced-database-cleaner"),
  __(
    "Specifies the maximum execution time for scan operations in seconds. Adjusting this value can help prevent long-running scans from causing timeout issues. Default is 0, which means the plugin will decide the best value.",
    "advanced-database-cleaner"
  ),
  __("Orphans", "advanced-database-cleaner"),
  __("Unknown", "advanced-database-cleaner"),
  __("Not scanned", "advanced-database-cleaner"),
  __("Show manual corrections only", "advanced-database-cleaner"),
  __("Local scan only", "advanced-database-cleaner"),
  __("Less accurate", "advanced-database-cleaner"),
  __(
    "The plugin will only scan your items against your local files; no data will be sent to the remote database.",
    "advanced-database-cleaner"
  ),
  __("Local & remote scan", "advanced-database-cleaner"),
  __("More accurate", "advanced-database-cleaner"),
  __(
    "The plugin will begin by scanning items against your local files, then it will anonymously send the scan results to a secure remote database for more accurate results.",
    "advanced-database-cleaner"
  ),
  __("Buy V4 bundled with remote scan", "advanced-database-cleaner"),
  __("Use credits", "advanced-database-cleaner"),
  __("Selected", "advanced-database-cleaner"),
  __(
    "A scan is already in progress. Please reload the page to see the current scan status.",
    "advanced-database-cleaner"
  ),
  __("Select the scan configuration", "advanced-database-cleaner"),
  __(
    "The scan will identify which plugins or themes your items are associated with",
    "advanced-database-cleaner"
  ),
  __("Scan type", "advanced-database-cleaner"),
  __("Recommended", "advanced-database-cleaner"),
  __(
    "The remote scan is a powerful feature that enhances largely the accuracy of the scan results. Since it costs ongoing charges to maintain, it is not included in the lifetime plan. There are two ways to use it:",
    "advanced-database-cleaner"
  ),
  __(
    "You can upgrade to the full Version 4, which includes the Remote Scan and Cloud features, available on an annual subscription.",
    "advanced-database-cleaner"
  ),
  __(
    "As a lifetime license holder, you'll receive a permanent 50% discount, applied to your first purchase and all future renewals.",
    "advanced-database-cleaner"
  ),
  __("Get the full version 4", "advanced-database-cleaner"),
  __(
    "Buy credits to use the Remote Scan feature and pay only when you need it. Or simply redeem a credit code if you already have one.",
    "advanced-database-cleaner"
  ),
  __(
    "Please activate your plugin license first to perform a remote scan.",
    "advanced-database-cleaner"
  ),
  __("Items to scan", "advanced-database-cleaner"),
  __("Override manual corrections?", "advanced-database-cleaner"),
  __(
    "This will override any manual 'belongs to' corrections you have made. If you are unsure, leave this option disabled.",
    "advanced-database-cleaner"
  ),
  __("Start the scan", "advanced-database-cleaner"),
  __(
    "By starting this scan, you agree to anonymously send the local scan results to our secure remote database to improve your scan accuracy. No personal data is collected.",
    "advanced-database-cleaner"
  ),
  __("Preparing items to scan", "advanced-database-cleaner"),
  __("Collecting PHP files to scan", "advanced-database-cleaner"),
  __("Regex scan (skipped)", "advanced-database-cleaner"),
  __("Exact match scan", "advanced-database-cleaner"),
  __("Partial match scan", "advanced-database-cleaner"),
  __("skipped", "advanced-database-cleaner"),
  __("Preparing local scan results", "advanced-database-cleaner"),
  __("Requesting remote scan", "advanced-database-cleaner"),
  __("Remote scan", "advanced-database-cleaner"),
  __("corrected items:", "advanced-database-cleaner"),
  __("The scan cannot start!", "advanced-database-cleaner"),
  __("check logs", "advanced-database-cleaner"),
  __("Scan started", "advanced-database-cleaner"),
  __(
    "Too many items to send due to your server limitation. Trimmed items:",
    "advanced-database-cleaner"
  ),
  __("Timeouts occurred:", "advanced-database-cleaner"),
  __("Forced timeouts occurred:", "advanced-database-cleaner"),
  __("Retry sending the request", "advanced-database-cleaner"),
  __("Max retries reached", "advanced-database-cleaner"),
  __("Request failed", "advanced-database-cleaner"),
  __("Show balance", "advanced-database-cleaner"),
  sprintf(
    /* translators: %d: queue position number */
    __("You are at position %d in the queue.", "advanced-database-cleaner"),
    variable
  ),
  __("The server is scanning your items...", "advanced-database-cleaner"),
  __("Info: the remote scan is taking more than expected!", "advanced-database-cleaner"),
  __("Scan completed", "advanced-database-cleaner"),
  __("Scan stopped", "advanced-database-cleaner"),
  __("Scan timeout occurred!", "advanced-database-cleaner"),
  __(
    "Server is under maintenance. Please retry again after few minutes",
    "advanced-database-cleaner"
  ),
  __(
    "An error occurred while getting the results from the server. Check the logs for more details",
    "advanced-database-cleaner"
  ),
  __("Scan in progress", "advanced-database-cleaner"),
  __("Stopping scan...", "advanced-database-cleaner"),
  __("Stop the scan", "advanced-database-cleaner"),
  __("Hide scan process details", "advanced-database-cleaner"),
  __("Scan progress", "advanced-database-cleaner"),
  __("Step 1", "advanced-database-cleaner"),
  __("Collecting files", "advanced-database-cleaner"),
  __(
    "Collecting the PHP files to be scanned. This process may take some time, depending on the number of plugin and theme files you have.",
    "advanced-database-cleaner"
  ),
  __("Step 2", "advanced-database-cleaner"),
  __("Exact match", "advanced-database-cleaner"),
  __(
    "The scan will attempt to find exact matches for items names in the previously collected files.",
    "advanced-database-cleaner"
  ),
  __("Step 3", "advanced-database-cleaner"),
  __("Partial match", "advanced-database-cleaner"),
  __(
    "For any remaining items from the previous step, the scan will attempt to find partial matches for items names.",
    "advanced-database-cleaner"
  ),
  __("Step 4", "advanced-database-cleaner"),
  __(
    "The local scan results will be transmitted to a remote database to enhance the accuracy of results and provide more detailed information about the items. No sensitive data is sent.",
    "advanced-database-cleaner"
  ),
  __("Scan details", "advanced-database-cleaner"),
  __("Retrying...", "advanced-database-cleaner"),
  __("Retry", "advanced-database-cleaner"),
  __("Total timeouts:", "advanced-database-cleaner"),
  __("Total forced timeouts:", "advanced-database-cleaner"),
  __("files", "advanced-database-cleaner"),
  __("Show filters", "advanced-database-cleaner"),
  __("Hide filters", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: number of cron jobs with no action */
    _n(
      "%s cron job with no action",
      "%s cron jobs with no action",
      variable,
      "advanced-database-cleaner"
    ),
    variable
  ),
  sprintf(
    /* translators: %s: number of cron jobs not scanned */
    _n(
      "%s cron job not scanned",
      "%s cron jobs not scanned",
      variable,
      "advanced-database-cleaner"
    ),
    variable
  ),
  __("Search in", "advanced-database-cleaner"),
  __("Loading ...", "advanced-database-cleaner"),
  __("Interval (secs)", "advanced-database-cleaner"),
  __("Has action", "advanced-database-cleaner"),
  __("Unlock Advanced Filters:", "advanced-database-cleaner"),
  __("Term ID", "advanced-database-cleaner"),
  __("Size big than", "advanced-database-cleaner"),
  __("Unlock Advanced Filters", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: autoload size */
    __("Autoload size is good (%s)", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: autoload size */
    __("Autoload size is big (%s)", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of big options */
    _n("%s big option detected", "%s big options detected", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of options not scanned */
    _n("%s option not scanned", "%s options not scanned", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of unused post meta */
    __("%s unused post meta", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of duplicated post meta */
    __("%s duplicated post meta", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of big post meta */
    __("%s big post meta detected", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of post meta not scanned */
    __("%s post meta not scanned", "advanced-database-cleaner"),
    variable
  ),
  __("Unused", "advanced-database-cleaner"),
  __("Duplicated", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: number of post types not scanned */
    __("%s post types not scanned", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of non-public post types */
    __("%s non-public post types with many posts", "advanced-database-cleaner"),
    variable
  ),
  __("Posts count >", "advanced-database-cleaner"),
  __("To optimize", "advanced-database-cleaner"),
  __("To repair", "advanced-database-cleaner"),
  __("Valid prefix", "advanced-database-cleaner"),
  __("Invalid prefix", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: number of tables to optimize */
    _n("%s table to optimize", "%s tables to optimize", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of tables to repair */
    _n("%s table to repair", "%s tables to repair", variable, "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of tables with invalid prefix */
    _n(
      "%s table with invalid prefix",
      "%s tables with invalid prefix",
      variable,
      "advanced-database-cleaner"
    ),
    variable
  ),
  sprintf(
    /* translators: %s: number of tables not scanned */
    _n("%s table not scanned", "%s tables not scanned", variable, "advanced-database-cleaner"),
    variable
  ),
  __("Table status", "advanced-database-cleaner"),
  __("Prefix status", "advanced-database-cleaner"),
  sprintf(
    /* translators: %s: number of expired transients */
    _n(
      "%s expired transient detected",
      "%s expired transients detected",
      variable,
      "advanced-database-cleaner"
    ),
    variable
  ),
  sprintf(
    /* translators: %s: number of big transients */
    _n(
      "%s big transient detected",
      "%s big transients detected",
      variable,
      "advanced-database-cleaner"
    ),
    variable
  ),
  sprintf(
    /* translators: %s: number of transients not scanned */
    _n(
      "%s transient not scanned",
      "%s transients not scanned",
      variable,
      "advanced-database-cleaner"
    ),
    variable
  ),
  sprintf(
    /* translators: %s: number of unused user meta */
    __("%s unused user meta", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of duplicated user meta */
    __("%s duplicated user meta", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of big user meta */
    __("%s big user meta detected", "advanced-database-cleaner"),
    variable
  ),
  sprintf(
    /* translators: %s: number of user meta not scanned */
    __("%s user meta not scanned", "advanced-database-cleaner"),
    variable
  )
];

export default translations;
