<?php
/**
 * Supports the adding of extra tags to an email at runtime.
 */
class MandrillEmailDecorator extends DataObjectDecorator {
	// Tags programatically added at run-time
	protected $extraTags = array();

	public function addExtraTag($tag) {
		$this->extraTags[] = $tag;
	}

	/**
	 * Helper for adding many tags to the extraTags array
	 *
	 * @param mixed Any array-like item including DataObjectSet
	 */
	public function addExtraTags($tags) {
		foreach ($tags as $tag) {
			$this->extraTags[] = $tag;
		}
	}

	/**
	 * Mandrill supports tags as any un-broken string.
	 * For consistency, we supply tags as lowercase letters separated by dashes.
	 * Remove any other characters and replace spaces with dashes.
	 *
	 * @param  array $tags
	 * @return array
	 */
	public function cleanTags($tags) {
		foreach ($tags as $k => $t) {
			$t = strtolower($t);
			$t = str_replace(' ', '-', $t);
			$t = preg_replace('/-{2,}/', '-', $t);
			$t = preg_replace('/[^A-Za-z\-]/', '', $t);
			$tags[$k] = $t;
		}
		return $tags;
	}

	public function getExtraTags() {
		return $this->extraTags;
	}

	public function getTags() {
		$tags = array();
		if ($this->owner->hasMethod('Tags')) {
			if ($associatedTags = $this->owner->Tags()) {
				$tags = $associatedTags->map();
			}
		}
		$tags = array_merge($tags, $this->getExtraTags());
		$tags = $this->cleanTags($tags);
		return $tags ?: null;
	}

	/**
	 * Overwrites the extraTags variable.
	 *
	 * @param mixed A string or any array-like item including DataObjectSet
	 */
	public function setExtraTags($tags) {
		$this->extraTags = array();

		switch(gettype($tags)) {
			case 'array':
			case 'object':
				$this->addExtraTags($tags);
				break;
			case 'string':
				$this->addExtraTag($tags);
				break;
			default:
				$error  = 'setExtraTags() expects a string or any array-like item including DataObjectSet, ';
				$error .= gettype($tags) . ' given';
				throw new InvalidArgumentException($error);
		}
		foreach ($tags as $tag) {
			$this->addExtraTag($tag);
		}
	}

	/**
	 * Jumps in before the call to send() and sends the email with required tags
	 */
	public function handleSend() {
		$sendVars = $this->owner->prepareSend();
		$className = get_class($this->owner);
		$mailer = $className::mailer();
		if (get_class($mailer) === 'MandrillMailer') {
			return $mailer->sendHTML(
					$sendVars['to'],
					$this->owner->from,
					$sendVars['subject'],
					$this->owner->body,
					$this->owner->attachments,
					$sendVars['headers'],
					$this->owner->plaintext_body,
					false,
					$this->getTags()
			);
		}
		// If the Mandrill mailer is not in use, it is not our place to manage the send
		return false;
	}
}