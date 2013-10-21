Mandrill Mailer for Silverstripe
================================

Send email using [Mandrill API](https://mandrillapp.com/docs/ "Mandrill API Docs") from Silverstripe CMS

Usage
-----

Set globally in `_config.php`

`Email::set_mailer(
   new MandrillMailer( [apikey] );
);`

Extend the Silverstripe Email class and specify this Mailer for use with it.

`class MyEmail extends Email {
	__construct(){
		$mandrillMailer = new MandrillMailer( [apikey] );
		$this->set_mailer( $mandrillMailler );
	}

}`

