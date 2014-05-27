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
	 * Test that $customheaders is an array.
	 * If truthy but not an array, throw an exception
	 *
	 * Check keys for cc and bcc fields for case-sensitivity issues
	 *
	 * @param mixed false|array
	 */
	public function cleanCustomHeaders(&$customheaders) {
		if($customheaders && !is_array($customheaders)) {
			throw new Exception('Could not send email, $customheaders must be falsey or an array');
		}
		$customheaders = (array) $customheaders;

		// the carbon copy headers are case sensitive
		// Ensure only 'Cc' and 'Bcc' are used
		$toCapitalise = array(
			'BCC', 'bcc', 'CC', 'cc'
		);
		foreach ($toCapitalise as $val) {
			if (isset($headers[$val])) {
				$headers[ucfirst(strtolower($val))] = $headers[$val];
				unset($headers[$val]);
			}
		}
	}

	/**
	 * Prepare subject string for email including possibly base64 encoding
	 *
	 * @param  string $subject
	 * @return string
	 */
	public function cleanSubject($subject) {
		// If the subject line contains extended characters, we must encode it
		$subject = Convert::xml2raw($subject);
		if ($this->isUnicode($subject)) {
			$subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
		}

		return $subject;
	}

	/**
	 * If the constant BOUNCE_EMAIL and the custom header X-SilverStripeMessageID has been set
	 * Silverstripe can process and track bounced messages
	 *
	 * @param string      $from          The email from address
	 * @param false|array $headers
	 */
	public function getBounceAddress($from, $headers) {
		if (defined('BOUNCE_EMAIL') && !empty($customheaders["X-SilverStripeMessageID"])) {
			$bounceAddress = BOUNCE_EMAIL;
		}
		else {
			$bounceAddress = $from;
		}

		// Strip the human name from the bounce address
		if (preg_match('/^([^<>]*)<([^<>]+)> *$/', $bounceAddress, $parts)) {
			$bounceAddress = $parts[2];
		}

		return $bounceAddress;
	}

	public function getContentTypeHeader($html = false) {
		return 'text/' . ($html ? 'html' : 'plain') . '; charset="utf-8"';
	}

	/**
	 * Return the base header tags needed to send the email
	 *
	 * @param string      The email from address
	 * @param bool        Is this an HTML or plaintext email?
	 * @param bool|string Is the content base64 encoded?
	 */
	public function getStandardHeaders($from, $html = false, $base64 = false) {
		return array(
			'Content-Type' => $this->getContentTypeHeader($html),
			'Content-Transfer-Encoding' => $base64 ? 'base64' : 'quoted-printable',
			'From' => $from,
			'X-Mailer' => X_MAILER,
			'X-Priority' => 3
		);
	}

	public function isUnicode($str) {
		return strpos($str,"&#") !== false;
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