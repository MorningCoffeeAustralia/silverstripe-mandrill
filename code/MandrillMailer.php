<?php

/*
 * class MandrillMailer
 * Mailer class for sending email using Mandrill api https://mandrillapp.com/api/docs/
 */

class MandrillMailer extends Mailer {

	public function __construct ($apiKey) {
		Mandrill::set_api_key ($apiKey);
	}

	/**
	 * Return an array of attached files in the format required by Mandrill
	 *
	 * @param  array
	 * @return array
	 */
	public function prepareFilesForSend($origFiles)
	{
		$newFiles = array();
		foreach ($origFiles as $file) {
			$mimeType = $file['mimetype'] ?: $this->getMimeType($file['filename']);
			if(!$mimeType) {
				$mimeType = "application/unknown";
			}

			$newFiles[] = array(
				'type' => $mimeType,
				'name' => basename($file['filename']),
				'content' => base64_encode($file['contents'])
			);
		}
		return $newFiles;
	}

	public function sendPlain ($to, $from, $subject, $plainContent,
						$attachedFiles = false, $customheaders = false, $tags = false)
	{
		$vars = $this->preparePlaintextEmail($to, $from, $subject, $body, $attachedFiles, $customheaders);

		$args = array (
			'message' => array(
				'html' => $vars['fullBody'],
				'subject' => $vars['subject'],
				'from_email' => $vars['from'],
				'to' => array (
					array ('email' => $vars['to'])
				)
			)
		);
		if ($tags) {
			$vars['tags'] = $tags;
		}

		return $this->sendEmail($args) ? $args : false;
	}

	public function sendHTML ($to, $from, $subject, $htmlContent, $attachedFiles = false,
						$customheaders = false, $plainContent = false, $inlineImages = false, $tags = false)
	{
		$this->cleanCustomHeaders($customheaders);

		if (!$plainContent) {
			$plainContent = Convert::xml2raw($htmlContent);
		}

		$from = $this->validEmailAddr($from);
		$to = $this->validEmailAddr($to);

		$base64Encoded = $this->isUnicode($htmlContent);
		$bounceAddress = $this->getBounceAddress($from, $customheaders);
		$headers = $this->getStandardHeaders($from, true, $base64Encoded);
		$subject = $this->cleanSubject($subject);

		if ($inlineImages) {
			$htmlPart = $this->wrapImagesInline($htmlContent);
		} else {
			$headers['Content-Transfer-Encoding'] = 'quoted-printable';
		}

		$args = array(
			'message' => array(
				'html' => $htmlContent,
				'subject' => $subject,
				'from_email' => $from,
				'to' => array (
					array ('email' => $to)
				)
			)
		);
		if ($attachedFiles) {
			$args['message']['attachments'] = $this->prepareFilesForSend($attachedFiles);
		}
		if ($tags) {
			$args['message']['tags'] = $tags;
		}

		return $this->sendEmail($args) ? $args : false;
	}

	public function sendEmail($args) {
		return Mandrill::request('messages/send', $args);
	}
}