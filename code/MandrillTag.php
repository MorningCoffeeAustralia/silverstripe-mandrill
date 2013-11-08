<?php
/**
 * Store tags to be associated with emails sent via Mandrill
 */
class MandrillTag extends DataObject {
	static $db = array (
		'Title' => 'Varchar'
	);
}