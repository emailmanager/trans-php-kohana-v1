<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Emailmanager library. Handles delivery of email. Based on http://github.com/Znarkus/emailmanager-php/blob/master/Emailmanager.php
*
 *
 * @package    Emailmanager
 * @author     Regis Freyd
 */
class Emailmanager_Core {

	// Configuration
	protected $config;
	
	// Email default values
	private $_fromName;
	private $_fromAddress;
	private $_tag;
	private $_toName;
	private $_toAddress;
	private $_replyToName;
	private $_replyToAddress;
	private $_cc = array();
	private $_bcc = array();
	private $_subject;
	private $_messagePlain;
	private $_messageHtml;
	private $_debugMode;

	// API
	private $_emailmanagerapp_api_key;
	private $_emailmanagerapp_mail_from_address;
	private $_emailmanagerapp_mail_from_name;

	 /**
	* Initialize
	*/
	public function __construct()
	{
		// Loads default config file
		$this->_emailmanagerapp_api_key = Kohana::config('emailmanager.api_key');
		
		// For the future: we need to localize those things as well
		$this->_emailmanagerapp_mail_from_address = Kohana::config('emailmanager.mail_from_address');
		$this->_emailmanagerapp_mail_from_name = Kohana::config('emailmanager.mail_from_name');
		
		// Initialize the class
		$this->from($this->_emailmanagerapp_mail_from_address,$this->_emailmanagerapp_mail_from_name)->messageHtml(null)->messagePlain(null);
		Kohana::log('debug', 'Emailmanager Library loaded');
	}

	 /**
	* New e-mail
	* @return Mail_Emailmanager
	*/
	public static function compose()
	{
		return new self();
	}
	
	 /**
	* Turns debug output on
	* @param int $mode One of the debug constants
	* @return Mail_Emailmanager
	*/
	public function debug($mode = self::DEBUG_VERBOSE)
	{
		$this->_debugMode = $mode;
		return $this;
	}
	
	 /**
	* Specify sender. Overwrites default From.
	* @param string $address E-mail address used in From
	* @param string $name Optional. Name used in From
	* @return Mail_Emailmanager
	*/
	public function from($address, $name = null)
	{
		$this->_fromAddress = $address;
		$this->_fromName = $name;
		return $this;
	}
	
	 /**
	* Specify sender name. Overwrites default From name, but doesn't change address.
	* @param string $name Name used in From
	* @return Mail_Emailmanager
	*/
	public function fromName($name)
	{
		$this->_fromName = $name;
		return $this;
	}
	
	 /**
	* You can categorize outgoing email using the optional Tag property.
	* If you use different tags for the different types of emails your
	* application generates, you will be able to get detailed statistics
	* for them through the Emailmanager user interface.
	* Only 1 tag per mail is supported.
	*
	* @param string $tag One tag
	* @return Mail_Emailmanager
	*/
	public function tag($tag)
	{
		$this->_tag = $tag;
		return $this;
	}
	
	 /**
	* Specify receiver
	* @param string $address E-mail address used in To
	* @param string $name Optional. Name used in To
	* @return Mail_Emailmanager
	*/
	public function to($address, $name = null)
	{
		$this->_toAddress = $address;
		$this->_toName = $name;
		return $this;
	}
	
	
	/**
	* Specify reply-to
	* @param string $address E-mail address used in To
	* @param string $name Optional. Name used in To
	* @return Mail_Emailmanager
	*/
	public function replyTo($address, $name = null)
	{
		$this->_replyToAddress = $address;
		$this->_replyToName = $name;
		return $this;
	}

	 /**
	* Add a CC address
	* @param string $address E-mail address used in CC
	* @param string $name Optional. Name used in CC
	* @return Mail_Emailmanager
	*/
	public function addCC($address, $name = null)
	{
		$this->_cc[] = (is_null($name) ? $address : "$name <$address>");
		return $this;
	}

	/**
	* Add a BCC address
	* @param string $address E-mail address used in BCC
	* @param string $name Optional. Name used in BCC
	* @return Mail_Emailmanager
	*/
	public function addBCC($address, $name = null)
	{
		$this->_bcc[] = (is_null($name) ? $address : "$name <$address>");
		return $this;
	}
	
	 /**
	* Specify subject
	* @param string $subject E-mail subject
	* @return Mail_Emailmanager
	*/
	public function subject($subject)
	{
	$this->_subject = $subject;
	return $this;
	}

	/**
	* Add plaintext message. Can be used in conjunction with messageHtml()
	* @param string $message E-mail message
	* @return Mail_Emailmanager
	*/
	public function messagePlain($message)
	{
		$this->_messagePlain = $message;
		return $this;
	}

	/**
	* Add HTML message. Can be used in conjunction with messagePlain()
	* @param string $message E-mail message
	* @return Mail_Emailmanager
	*/
	public function messageHtml($message)
	{
		$this->_messageHtml = $message;
		return $this;
	}
	
	 /**
	* Sends the e-mail. Prints debug output if debug mode is turned on
	* @return Mail_Emailmanager
	*/
	public function &send()
	{
		if (is_null($this->_emailmanagerapp_api_key)) {
			throw new Exception('Emailmanager API key is not set');
		}

		if (is_null($this->_fromAddress)) {
			throw new Exception('From address is not set');
		}

		if (!isset($this->_toAddress)) {
			throw new Exception('To address is not set');
		}

		if (1 + count($this->_cc) + count($this->_bcc) > 20) {
			throw new Exception("Too many email recipients");
		}

		$data = $this->_prepareData();
		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'X-Emailmanager-Server-Token: ' . $this->_emailmanagerapp_api_key
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://trans.emailmanager.com/email');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$return = curl_exec($ch);

		/*if ($this->_debugMode == self::DEBUG_VERBOSE) 
		{
			echo "JSON: " . json_encode($data) . "\nHeaders: \n\t" . implode("\n\t", $headers) . "\nReturn:\n$return";
		} 
		else if ($this->_debugMode == self::DEBUG_RETURN) 
		{
			return array(
				'json' => json_encode($data),
				'headers' => $headers,
				'return' => $return
			);
		}*/

		if (curl_error($ch) != '') {
			throw new Exception(curl_error($ch));
		}

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (!$this->_isTwoHundred($httpCode)) 
		{
			$message = json_decode($return)->Message;
			throw new Exception("Error while mailing. Emailmanager returned HTTP code $httpCode with message \"$message\"");
		}

		return $this;
	}
	
	 /**
	* Prepares the data array
	*/
	private function _prepareData()
	{
		$data = array(
			'Subject' => $this->_subject
		);

		$data['From'] = is_null($this->_fromName) ? $this->_fromAddress : "{$this->_fromName} <{$this->_fromAddress}>";
		$data['To'] = is_null($this->_toName) ? $this->_toAddress : "{$this->_toName} <{$this->_toAddress}>";

		if (!is_null($this->_messageHtml)) {
			$data['HtmlBody'] = $this->_messageHtml;
		}

		if (!is_null($this->_messagePlain)) {
			$data['TextBody'] = $this->_messagePlain;
		}

		if (!is_null($this->_tag)) {
			$data['Tag'] = $this->_tag;
		}

		if (!is_null($this->_replyToAddress)) {
			$data['ReplyTo'] = is_null($this->_replyToName) ? $this->_replyToAddress : "{$this->_replyToName} <{$this->_replyToAddress}>";
		}

		if (!empty($this->_cc)) {
			$data['Cc'] = implode(',',$this->_cc);
		}

		if (!empty($this->_bcc)) {
			$data['Bcc'] = implode(',',$this->_bcc);
		}

		return $data;
	}

	/**
	* If a number is 200-299
	*/
	private function _isTwoHundred($value)
	{
		return intval($value / 100) == 2;
	}

	/**
	* Defines a constant, if it isn't defined
	*/
	private function _default($name, $default)
	{
		if (!defined($name)) {
			define($name, $default);
		}
	}

} // End Emailmanager