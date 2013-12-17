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
		$field = new ManyManyDataObjectManager($this->owner, 'Tags', 'MandrillTag');
		$field->setPermissions(array('add', 'only_related'));
		$fields->addFieldToTab(
			'Root.Main',
			$field
		);
	}
}