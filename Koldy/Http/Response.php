<?php namespace Koldy\Http;
/**
 * This will be the instance of the response created by \Koldy\Http\Request class
 *
 */
class Response {

	/**
	 * @var resource
	 */
	protected $ch = null;

	/**
	 * The response body from request
	 * 
	 * @var string
	 */
	protected $body = null;

	/**
	 * @var null
	 */
	protected $headersText = null;

	/**
	 * Response constructor.
	 *
	 * @param $ch
	 * @param $body
	 */
	public function __construct($ch, $body) {
		$this->ch = $ch;

		$headerSize = $this->headerSize();

		if ($headerSize == 0) {
			$this->body = $body;
		} else {
			$this->headersText = trim(substr($body, 0, $headerSize));
			$this->body = substr($body, $headerSize);
		}
	}

	public function __destruct() {
		curl_close($this->ch);
	}

	/**
	 * What was the request URL?
	 * 
	 * @return string
	 */
	public function url() {
		return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
	}

	/**
	 * Get the response body
	 * 
	 * @return string
	 */
	public function body() {
		return $this->body;
	}

	/**
	 * What is the response HTTP code?
	 * 
	 * @return int
	 */
	public function httpCode() {
		return (int) curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
	}

	/**
	 * Is response OK? (is HTTP response code 200)
	 * 
	 * @return boolean
	 */
	public function isOk() {
		return $this->httpCode() == 200;
	}

	/**
	 * Get the content type of response
	 * 
	 * @return string
	 */
	public function contentType() {
		return curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
	}

	/**
	 * Get the request's connect time in seconds
	 * 
	 * @return float
	 */
	public function connectTime() {
		return curl_getinfo($this->ch, CURLINFO_CONNECT_TIME);
	}

	/**
	 * Get the request's connect time in miliseconds
	 * 
	 * @return int
	 */
	public function connectTimeMs() {
		return round($this->connectTime() * 1000);
	}

	/**
	 * Get the request total time in seconds
	 * 
	 * @return float
	 */
	public function totalTime() {
		return curl_getinfo($this->ch, CURLINFO_TOTAL_TIME);
	}

	/**
	 * Get the request total time in miliseconds
	 * 
	 * @return int
	 */
	public function totalTimeMs() {
		return round($this->totalTime() * 1000);
	}

	/**
	 * @return int
	 */
	public function headerSize() {
		return curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
	}

	/**
	 * If you try to print the response object, you'll get response body
	 * 
	 * @return string
	 */
	public function __toString() {
		$msg = "HTTP Response {$this->httpCode()} of {$this->url()} IN {$this->totalTime()}s";

		if ($this->headersText != null) {
			$msg .= " with response HEADERS:\n";
			foreach (explode("\n", $this->headersText) as $line) {
				$msg .= "\t{$line}\n";
			}
		} else {
			$msg .= "\n";
		}

		$body = $this->body();
		if (strlen($body) > 120) {
			$body .= substr($body, 0, 120) . '...';
		}

		$msg .= "\nRESPONSE BODY:\n{$body}\n";
		$msg .= '--------------------';

		return $msg;
	}

}
