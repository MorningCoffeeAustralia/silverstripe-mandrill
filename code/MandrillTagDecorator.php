<?php
/**
 * Support for adding tags to emails
 */
class MandrillTagDecorator extends DataObjectDecorator {
	public function extraStatics() {
		return array(
			'many_many' => array(
		 		'Tags' => 'MandrillTag'
			)
		);
	}

	public function updateCMSFields(FieldSet $fields) {
		$fields->addFieldToTab(
			'Root.Main',
			new ManyManyDataObjectManager($this->owner, 'Tags', 'MandrillTag')
		);
	}
}