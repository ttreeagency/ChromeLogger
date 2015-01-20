<?php
namespace Ttree\ChromeLogger\Log\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ChromeLogger".    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Ttree\ChromeLogger\Service\ChromeLoggerService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Log\Backend\AbstractBackend;
use TYPO3\Flow\Object\Exception\UnknownObjectException;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * Server Side Chrome PHP logger class
 *
 * @Flow\Scope("singleton")
 */
class ChromeConsoleBackend extends AbstractBackend {

	/**
	 * @Flow\Inject
	 * @var ChromeLoggerService
	 */
	protected $chromeLoggerService;

	/**
	 * @var array
	 */
	protected $severityMapping = array();


	/**
	 * @var array
	 */
	protected $severityLabels = array();

	/**
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * Carries out all actions necessary to prepare the logging backend, such as opening
	 * the log file or opening a database connection.
	 *
	 * @return void
	 * @api
	 */
	public function open() {
		$this->severityMapping = array(
			LOG_EMERG   => 'error',
			LOG_ALERT   => 'error',
			LOG_CRIT    => 'error',
			LOG_ERR     => 'error',
			LOG_WARNING => 'warn',
			LOG_NOTICE  => 'info',
			LOG_INFO    => 'info',
			LOG_DEBUG   => 'log'
		);

		$this->severityLabels = array(
			LOG_EMERG   => 'EMERGENCY',
			LOG_ALERT   => 'ALERT',
			LOG_CRIT    => 'CRITICAL',
			LOG_ERR     => 'ERROR',
			LOG_WARNING => 'WARNING',
			LOG_NOTICE  => 'NOTICE',
			LOG_INFO    => 'INFO',
			LOG_DEBUG   => 'DEBUG'
		);
	}

	/**
	 * Initialize Chrome Logger Service
	 */
	protected function initializeChromeLoggerService() {
		if (!$this->chromeLoggerService instanceof ChromeLoggerService) {
			if (Bootstrap::$staticObjectManager instanceof ObjectManagerInterface) {
				try {
					$this->chromeLoggerService = Bootstrap::$staticObjectManager->get('Ttree\ChromeLogger\Service\ChromeLoggerService');
					$this->initialized = TRUE;
				} catch (Exception $exception) {
					// Skip exception if the object manager is unable to initialize the service
					$this->initialized = FALSE;
				}
			}
		}

		return $this->initialized;
	}

	/**
	 * Appends the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity One of the LOG_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @param string $packageKey Key of the package triggering the log (determined automatically if not specified)
	 * @param string $className Name of the class triggering the log (determined automatically if not specified)
	 * @param string $methodName Name of the method triggering the log (determined automatically if not specified)
	 * @return void
	 * @api
	 */
	public function append($message, $severity = LOG_INFO, $additionalData = NULL, $packageKey = NULL, $className = NULL, $methodName = NULL) {
		if (!$this->initializeChromeLoggerService()) {
			return;
		}
		if (function_exists('posix_getpid')) {
			$processId = ' ' . posix_getpid();
		} else {
			$processId = ' ';
		}
		$ipAddress = ($this->logIpAddress === TRUE) ? str_pad((isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''), 15) : '';
		$severityLabel = (isset($this->severityLabels[$severity])) ? $this->severityLabels[$severity] : 'UNKNOWN';
		$output = strftime('%y-%m-%d %H:%M:%S', time()) . $processId . ' ' . $ipAddress . strtoupper($severityLabel) . ' ' . $packageKey;
		$method = $this->severityMapping[$severity];

		$this->chromeLoggerService->group($output);
		$this->chromeLoggerService->$method($message);
		$classNameWithMethod = '';
		if ($className) {
			$classNameWithMethod .= $className;
		}
		if ($methodName) {
			$classNameWithMethod .= '::' . $methodName . '()';
		}
		if ($classNameWithMethod !== '')
			$this->chromeLoggerService->log($classNameWithMethod);
		if ($additionalData) {
			$this->chromeLoggerService->log($additionalData);
		}
		$this->chromeLoggerService->groupEnd();
	}

	/**
	 * Carries out all actions necessary to cleanly close the logging backend, such as
	 * closing the log file or disconnecting from a database.
	 *
	 * @return void
	 * @api
	 */
	public function close() {

	}

}