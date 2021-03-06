<?php
namespace Ttree\ChromeLogger\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ChromeLogger".    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * Server Side Chrome PHP logger class
 *
 * @Flow\Scope("singleton")
 */
class ChromeLoggerService {

	/**
	 * @var string
	 */
	const VERSION = '4.1.0';

	/**
	 * @var string
	 */
	const HEADER_NAME = 'X-ChromeLogger-Data';

	/**
	 * @var string
	 */
	const BACKTRACE_LEVEL = 'backtrace_level';

	/**
	 * @var string
	 */
	const LOG = 'log';

	/**
	 * @var string
	 */
	const LOG_WARN = 'warn';

	/**
	 * @var string
	 */
	const LOG_ERROR = 'error';

	/**
	 * @var string
	 */
	const GROUP = 'group';

	/**
	 * @var string
	 */
	const LOG_INFO = 'info';

	/**
	 * @var string
	 */
	const GROUP_END = 'groupEnd';

	/**
	 * @var string
	 */
	const GROUP_COLLAPSED = 'groupCollapsed';

	/**
	 * @var string
	 */
	const TABLE = 'table';

	/**
	 * @var string
	 */
	protected $phpVersion;

	/**
	 * @var int
	 */
	protected $timestamp;

	/**
	 * @var array
	 */
	protected $json = array(
		'version' => self::VERSION,
		'columns' => array('log', 'backtrace', 'type'),
		'rows' => array()
	);

	/**
	 * @var array
	 */
	protected $backtraces = array();

	/**
	 * @var bool
	 */
	protected $triggeredErrors = FALSE;

	/**
	 * @var array
	 */
	protected $settings = array(
		self::BACKTRACE_LEVEL => 1
	);

	/**
	 * @var array
	 */
	protected $processed = array();

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * Initilize Logger
	 *
	 * The logger is disabled is the current context is different from Development
	 */
	public function initializeLogger() {

		if ($this->initialized === TRUE) {
			return TRUE;
		}
		if (Bootstrap::$staticObjectManager instanceof ObjectManagerInterface) {
			$environment = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Utility\Environment');
			if (!$environment->getContext()->isDevelopment()) {
				return FALSE;
			}
			$bootstrap = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap');
			/* @var Bootstrap $bootstrap */
			$requestHandler = $bootstrap->getActiveRequestHandler();
			if ($requestHandler instanceof HttpRequestHandlerInterface) {
				$this->request = $requestHandler->getHttpRequest();
				$this->response = $requestHandler->getHttpResponse();
				$this->json['request_uri'] = (string)$this->request->getUri();
				$this->phpVersion = phpversion();
				$this->timestamp = $this->phpVersion >= 5.1 ? $_SERVER['REQUEST_TIME'] : time();
				$this->initialized = TRUE;
			}
		}

		return $this->initialized;
	}

	/**
	 * logs a variable to the console
	 */
	public function log() {
		if (!$this->initializeLogger()) {
			return;
		}
		$args = func_get_args();
		$this->buildLogMessage('', $args);
	}

	/**
	 * logs a warning to the console
	 */
	public function warn() {
		if (!$this->initializeLogger()) {
			return;
		}
		$args = func_get_args();
		$this->buildLogMessage(self::LOG_WARN, $args);
	}

	/**
	 * logs an error to the console
	 */
	public function error() {
		if (!$this->initializeLogger()) {
			return;
		}
		$args = func_get_args();
		$this->buildLogMessage(self::LOG_ERROR, $args);
	}

	/**
	 * sends a group log
	 */
	public function group() {
		if (!$this->initializeLogger()) {
			return;
		}
		$args = func_get_args();
		$this->buildLogMessage(self::GROUP, $args);
	}

	/**
	 * sends an info log
	 */
	public function info() {
		if (!$this->initializeLogger()) {
			return;
		}
		$args = func_get_args();
		$this->buildLogMessage(self::LOG_INFO, $args);
	}

	/**
	 * sends a collapsed group log
	 */
	public function groupCollapsed() {
		if (!$this->initializeLogger()) {
			return;
		}
		$args = func_get_args();
		$this->buildLogMessage(self::GROUP_COLLAPSED, $args);
	}

	/**
	 * ends a group log
	 */
	public function groupEnd() {
		if (!$this->initializeLogger()) {
			return;
		}
		$args = func_get_args();
		$this->buildLogMessage(self::GROUP_END, $args);
	}

	/**
	 * sends a table log
	 */
	public function table() {
		if (!$this->initializeLogger()) {
			return;
		}
		$args = func_get_args();
		$this->buildLogMessage(self::TABLE, $args);
	}

	/**
	 * internal logging call
	 *
	 * @param string $type
	 * @param array $args
	 */
	protected function buildLogMessage($type, array $args) {
		// nothing passed in, don't do anything
		if (count($args) == 0 && $type != self::GROUP_END) {
			return;
		}

		$this->processed = array();

		$logs = array();
		foreach ($args as $arg) {
			$logs[] = $this->convertObject($arg);
		}

		$backtrace = debug_backtrace(FALSE);
		$level = $this->getSetting(self::BACKTRACE_LEVEL);

		$backtrace_message = 'unknown';
		if (isset($backtrace[$level]['file']) && isset($backtrace[$level]['line'])) {
			$backtrace_message = $backtrace[$level]['file'] . ' : ' . $backtrace[$level]['line'];
		}

		$this->addRowToDataArray($logs, $backtrace_message, $type);
	}

	/**
	 * converts an object to a better format for logging
	 *
	 * @param Object
	 * @return array
	 */
	protected function convertObject($object) {
		if (!is_object($object)) {
			return $object;
		}

		$this->processed[] = $object;

		$objectAsArray = array();

		// first add the class name
		$objectAsArray['___class_name'] = get_class($object);

		// loop through object vars
		$properties = get_object_vars($object);
		foreach ($properties as $key => $value) {

			// same instance as parent object
			if ($value === $object || in_array($value, $this->processed, TRUE)) {
				$value = 'recursion - parent object [' . get_class($value) . ']';
			}
			$objectAsArray[$key] = $this->convertObject($value);
		}

		$reflection = new \ReflectionClass($object);

		// loop through the properties and add those
		foreach ($reflection->getProperties() as $property) {

			// if one of these properties was already added above then ignore it
			if (array_key_exists($property->getName(), $properties)) {
				continue;
			}
			$type = $this->getObjectPropertyKey($property);

			if ($this->phpVersion >= 5.3) {
				$property->setAccessible(TRUE);
			}

			try {
				$value = $property->getValue($object);
			} catch (\ReflectionException $e) {
				$value = 'only PHP 5.3 can access private/protected properties';
			}

			// same instance as parent object
			if ($value === $object || in_array($value, $this->processed, TRUE)) {
				$value = 'recursion - parent object [' . get_class($value) . ']';
			}

			$objectAsArray[$type] = $this->convertObject($value);
		}
		return $objectAsArray;
	}

	/**
	 * takes a reflection property and returns a nicely formatted key of the property name
	 *
	 * @param \ReflectionProperty
	 * @return string
	 */
	protected function getObjectPropertyKey(\ReflectionProperty $property) {
		$static = $property->isStatic() ? ' static' : '';
		if ($property->isPublic()) {
			return 'public' . $static . ' ' . $property->getName();
		}

		if ($property->isProtected()) {
			return 'protected' . $static . ' ' . $property->getName();
		}

		return 'private' . $static . ' ' . $property->getName();
	}

	/**
	 * adds a value to the data array
	 *
	 * @param array $logs
	 * @param mixed $backtrace
	 * @param string $type
	 */
	protected function addRowToDataArray(array $logs, $backtrace, $type) {
		// if this is logged on the same line for example in a loop, set it to NULL to save space
		if (in_array($backtrace, $this->backtraces)) {
			$backtrace = NULL;
		}

		// for group, groupEnd, and groupCollapsed
		// take out the backtrace since it is not useful
		if ($type == self::GROUP || $type == self::GROUP_END || $type == self::GROUP_COLLAPSED) {
			$backtrace = NULL;
		}

		if ($backtrace !== NULL) {
			$this->backtraces[] = $backtrace;
		}

		$row = array($logs, $backtrace, $type);

		$this->json['rows'][] = $row;
		$this->appendLogToResponseHeader($this->json);
	}

	protected function appendLogToResponseHeader($data) {
		$data = $this->encode($data);
		if ($data !== NULL) {
			$this->response->setHeader(self::HEADER_NAME, $data);
		}
	}

	/**
	 * encodes the data to be sent along with the request
	 *
	 * @param array $data
	 * @return string
	 */
	protected function encode($data) {
		$data = base64_encode(utf8_encode(json_encode($data)));
		$length = strlen($data);
		if(($length / 1024) > 256) {
			return NULL;
		}

		return $data;
	}

	/**
	 * adds a setting
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function addSetting($key, $value) {
		$this->settings[$key] = $value;
	}

	/**
	 * add ability to set multiple settings in one call
	 *
	 * @param array $settings
	 * @return void
	 */
	public function addSettings(array $settings) {
		foreach ($settings as $key => $value) {
			$this->addSetting($key, $value);
		}
	}

	/**
	 * gets a setting
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getSetting($key) {
		if (!isset($this->settings[$key])) {
			return NULL;
		}
		return $this->settings[$key];
	}
}