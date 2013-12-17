<?php
/**
 * Store tags to be associated with emails sent via Mandrill
 */
class MandrillTag extends DataObject {
	public static $db = array (
		'Title' => 'Varchar'
	);
}