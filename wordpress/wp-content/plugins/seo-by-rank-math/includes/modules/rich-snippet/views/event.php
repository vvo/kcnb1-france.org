<?php
/**
 * Metabox - Event Rich Snippet
 *
 * @package    RankMath
 * @subpackage RankMath\RichSnippet
 */

$event = [ [ 'rank_math_rich_snippet', 'event' ] ];

$cmb->add_field([
	'id'      => 'rank_math_snippet_event_type',
	'type'    => 'select',
	'name'    => esc_html__( 'Event Type', 'rank-math' ),
	'desc'    => esc_html__( 'Type of the event.', 'rank-math' ),
	'options' => [
		'Event'            => esc_html__( 'Event', 'rank-math' ),
		'BusinessEvent'    => esc_html__( 'Business Event', 'rank-math' ),
		'ChildrensEvent'   => esc_html__( 'Childrens Event', 'rank-math' ),
		'ComedyEvent'      => esc_html__( 'Comedy Event', 'rank-math' ),
		'DanceEvent'       => esc_html__( 'Dance Event', 'rank-math' ),
		'DeliveryEvent'    => esc_html__( 'Delivery Event', 'rank-math' ),
		'EducationEvent'   => esc_html__( 'Education Event', 'rank-math' ),
		'ExhibitionEvent'  => esc_html__( 'Exhibition Event', 'rank-math' ),
		'Festival'         => esc_html__( 'Festival', 'rank-math' ),
		'FoodEvent'        => esc_html__( 'Food Event', 'rank-math' ),
		'LiteraryEvent'    => esc_html__( 'Literary Event', 'rank-math' ),
		'MusicEvent'       => esc_html__( 'Music Event', 'rank-math' ),
		'PublicationEvent' => esc_html__( 'Publication Event', 'rank-math' ),
		'SaleEvent'        => esc_html__( 'Sale Event', 'rank-math' ),
		'ScreeningEvent'   => esc_html__( 'Screening Event', 'rank-math' ),
		'SocialEvent'      => esc_html__( 'Social Event', 'rank-math' ),
		'SportsEvent'      => esc_html__( 'Sports Event', 'rank-math' ),
		'TheaterEvent'     => esc_html__( 'Theater Event', 'rank-math' ),
		'VisualArtsEvent'  => esc_html__( 'Visual Arts Event', 'rank-math' ),
	],
	'default' => 'Event',
	'classes' => 'cmb-row-33',
	'dep'     => $event,
]);

$cmb->add_field([
	'id'      => 'rank_math_snippet_event_venue',
	'type'    => 'text',
	'name'    => esc_html__( 'Venue Name', 'rank-math' ),
	'desc'    => esc_html__( 'The venue name.', 'rank-math' ),
	'classes' => 'cmb-row-50',
	'dep'     => $event,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_event_venue_url',
	'type'       => 'text_url',
	'name'       => esc_html__( 'Venue URL', 'rank-math' ),
	'desc'       => esc_html__( 'Website URL of the venue', 'rank-math' ),
	'classes'    => 'rank-math-validate-field',
	'attributes' => [ 'data-rule-url' => 'true' ],
	'dep'        => $event,
]);

$cmb->add_field([
	'id'   => 'rank_math_snippet_event_address',
	'type' => 'address',
	'name' => esc_html__( 'Address', 'rank-math' ),
	'dep'  => $event,
]);

$cmb->add_field([
	'id'      => 'rank_math_snippet_event_performer_type',
	'type'    => 'radio_inline',
	'name'    => esc_html__( 'Performer', 'rank-math' ),
	'options' => [
		'Person'       => esc_html__( 'Person', 'rank-math' ),
		'Organization' => esc_html__( 'Organization', 'rank-math' ),
	],
	'classes' => 'cmb-row-33 nob',
	'default' => 'Person',
	'dep'     => $event,
]);

$cmb->add_field([
	'id'      => 'rank_math_snippet_event_performer',
	'type'    => 'text',
	'name'    => esc_html__( 'Performer Name', 'rank-math' ),
	'desc'    => esc_html__( 'A performer at the event', 'rank-math' ),
	'classes' => 'cmb-row-50',
	'dep'     => $event,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_event_performer_url',
	'type'       => 'text',
	'name'       => esc_html__( 'Performer URL', 'rank-math' ),
	'attributes' => [
		'data-rule-url' => 'true',
	],
	'classes'    => 'rank-math-validate-field',
	'dep'        => $event,
]);

$cmb->add_field([
	'id'      => 'rank_math_snippet_event_status',
	'type'    => 'select',
	'name'    => esc_html__( 'Event Status', 'rank-math' ),
	'desc'    => esc_html__( 'Current status of the event (optional)', 'rank-math' ),
	'options' => [
		''                 => esc_html__( 'None', 'rank-math' ),
		'EventScheduled'   => esc_html__( 'Scheduled', 'rank-math' ),
		'EventCancelled'   => esc_html__( 'Cancelled', 'rank-math' ),
		'EventPostponed'   => esc_html__( 'Postponed', 'rank-math' ),
		'EventRescheduled' => esc_html__( 'Rescheduled', 'rank-math' ),
	],
	'classes' => 'cmb-row-33',
	'dep'     => $event,
]);

$cmb->add_field([
	'id'          => 'rank_math_snippet_event_startdate',
	'type'        => 'text_datetime_timestamp',
	'date_format' => 'Y-m-d',
	'name'        => esc_html__( 'Start Date', 'rank-math' ),
	'desc'        => esc_html__( 'Date and time of the event.', 'rank-math' ),
	'classes'     => 'cmb-row-33',
	'dep'         => $event,
]);

$cmb->add_field([
	'id'          => 'rank_math_snippet_event_enddate',
	'type'        => 'text_datetime_timestamp',
	'date_format' => 'Y-m-d',
	'name'        => esc_html__( 'End Date', 'rank-math' ),
	'desc'        => esc_html__( 'End date and time of the event.', 'rank-math' ),
	'classes'     => 'cmb-row-33',
	'dep'         => $event,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_event_ticketurl',
	'type'       => 'text',
	'name'       => esc_html__( 'Ticket URL', 'rank-math' ),
	'desc'       => esc_html__( 'A URL where visitors can purchase tickets for the event.', 'rank-math' ),
	'classes'    => 'cmb-row-33 rank-math-validate-field',
	'attributes' => [
		'data-rule-url' => 'true',
	],
	'dep'        => $event,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_event_price',
	'type'       => 'text',
	'name'       => esc_html__( 'Entry Price', 'rank-math' ),
	'desc'       => esc_html__( 'Entry price of the event (optional)', 'rank-math' ),
	'classes'    => 'cmb-row-33',
	'dep'        => $event,
	'attributes' => [ 'type' => 'number' ],
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_event_currency',
	'type'       => 'text',
	'name'       => esc_html__( 'Currency', 'rank-math' ),
	'desc'       => esc_html__( 'ISO 4217 Currency code. Example: EUR', 'rank-math' ),
	'classes'    => 'cmb-row-33 rank-math-validate-field',
	'attributes' => [
		'data-rule-regex'       => 'true',
		'data-validate-pattern' => '^[A-Z]{3}$',
		'data-msg-regex'        => esc_html__( 'Please use the correct format. Example: EUR', 'rank-math' ),
	],
	'dep'        => $event,
]);

$cmb->add_field([
	'id'      => 'rank_math_snippet_event_availability',
	'type'    => 'select',
	'name'    => esc_html__( 'Availability', 'rank-math' ),
	'desc'    => esc_html__( 'Offer availability', 'rank-math' ),
	'options' => [
		''         => esc_html__( 'None', 'rank-math' ),
		'InStock'  => esc_html__( 'In Stock', 'rank-math' ),
		'SoldOut'  => esc_html__( 'Sold Out', 'rank-math' ),
		'PreOrder' => esc_html__( 'Preorder', 'rank-math' ),
	],
	'classes' => 'cmb-row-33 nob',
	'dep'     => $event,
]);

$cmb->add_field([
	'id'          => 'rank_math_snippet_event_availability_starts',
	'type'        => 'text_datetime_timestamp',
	'date_format' => 'Y-m-d',
	'name'        => esc_html__( 'Availability Starts', 'rank-math' ),
	'desc'        => esc_html__( 'Date and time when offer is made available. (optional)', 'rank-math' ),
	'classes'     => 'cmb-row-33 nob',
	'dep'         => $event,
]);

$cmb->add_field([
	'id'         => 'rank_math_snippet_event_inventory',
	'type'       => 'text',
	'name'       => esc_html__( 'Stock Inventory', 'rank-math' ),
	'desc'       => esc_html__( 'Number of tickets (optional)', 'rank-math' ),
	'classes'    => 'cmb-row-33 nob',
	'dep'        => $event,
	'attributes' => [ 'type' => 'number' ],
]);
