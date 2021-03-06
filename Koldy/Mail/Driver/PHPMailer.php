<?php namespace Koldy\Mail\Driver;

use Koldy\Exception;

/**
 * This is only driver class that uses PHPMailer. You need to set the include
 * path the way that PHP can include it. We recommend that you set that path
 * in config/application.php under additional_include_path. Path defined there
 * must be the path where class.phpmailer.php is located.
 * 
 * @link http://koldy.net/docs/mail/phpmailer
 */
class PHPMailer extends AbstractDriver {

	/**
	 * @var \PHPMailer
	 */
	private $mailer = null;

	/**
	 * Construct the object
	 *
	 * @param array $config
	 *
	 * @throws Exception
	 */
	public function __construct(array $config) {
		parent::__construct($config);

		if (!class_exists('PHPMailer', false)) {

			if (($path = stream_resolve_include_path('class.phpmailer.php')) !== false) {
				require_once $path;

			} else if (($path = stream_resolve_include_path('PHPMailer/class.phpmailer.php')) !== false) {
				require_once $path;

			}

			if (!class_exists('PHPMailer', false)) {
				throw new Exception('PHPMailer class doesn\'t exists or can\'t be found. Please define the include path in config/application.php under additional_include_paths');
			}
		}

		$this->mailer = new \PHPMailer(true);
		$this->mailer->CharSet = isset($config['charset']) ? $config['charset'] : 'UTF-8';
		$this->mailer->Host = $config['host'];
		$this->mailer->Port = $config['port'];

		if (isset($config['username']) && $config['username'] !== null) {
			$this->mailer->Username = $config['username'];
		}

		if (isset($config['password']) && $config['password'] !== null) {
			$this->mailer->Password = $config['password'];
		}

		switch($config['type']) {
			default:
			case 'smtp':
				$this->mailer->IsSMTP();

				if (isset($config['username']) && $config['username'] !== null && isset($config['password']) && $config['password'] !== null) {
					$this->mailer->SMTPAuth = true;
				}

				if (($path = stream_resolve_include_path('class.smtp.php')) !== false) {
					require_once $path;

				} else if (($path = stream_resolve_include_path('PHPMailer/class.smtp.php')) !== false) {
					require_once $path;

				}

				break;

			case 'mail':
				$this->mailer->IsMail();
				break;
		}
	}

	/**
	 * Set email's "from"
	 *
	 * @param string $email
	 * @param string $name
	 *
	 * @return $this
	 */
	public function from($email, $name = null) {
		$this->mailer->SetFrom($email, $name);
		return $this;
	}

	/**
	 * Set email's "to"
	 *
	 * @param string $email
	 * @param string $name
	 *
	 * @return $this
	 */
	public function to($email, $name = null) {
		$this->mailer->AddAddress($email, $name === null ? '' : $name);
		return $this;
	}

	/**
	 * Set email's subject
	 *
	 * @param string $subject
	 *
	 * @return $this
	 */
	public function subject($subject) {
		$this->mailer->Subject = $subject;
		return $this;
	}

	/**
	 * @param string $body
	 * @param bool $isHTML
	 * @param string $alternativeText
	 *
	 * @return $this
	 */
	public function body($body, $isHTML = false, $alternativeText = null) {
		$this->mailer->Body = $body;

		if ($isHTML) {
			$this->mailer->IsHTML();
		}

		if ($alternativeText !== null) {
			$this->mailer->AltBody = $alternativeText;
		}

		return $this;
	}

	/**
	 * @param string $filePath
	 * @param string $name
	 *
	 * @return $this
	 */
	public function attachFile($filePath, $name = null) {
		$this->mailer->AddAttachment($filePath, ($name === null ? '' : $name));
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return $this
	 */
	public function header($name, $value) {
		$this->mailer->AddCustomHeader($name, $value);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function send() {
		try {

			if (!$this->mailer->Send()) {
				$this->setErrorMessage($this->mailer->ErrorInfo);
				return false;
			}

			return true;

		} catch (phpmailerException $e) {
			$this->setErrorMessage($e->getMessage());
			$this->setErrorException($e);

		} catch (\Exception $e) {
			$this->setErrorMessage($e->getMessage());
			$this->setErrorException($e);

		}

		return false;
	}

	/**
	 * Get the PHP mailer instance for fine tuning
	 * 
	 * @return \PHPMailer
	 */
	public function getPHPMailer() {
		return $this->mailer;
	}

}
