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
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Log\Backend\AbstractBackend;
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
	 * @var boolean
	 */
	protected $enabled = FALSE;

	/**
	 * @var array
	 */
	protected $severityLabels = array();

	/**
	 * Carries out all actions necessary to prepare the logging backend, such as opening
	 * the log file or opening a database connection.
	 *
	 * @return void
	 * @api
	 */
	public function open() {
		$this->severityLabels = array(
			LOG_EMERG   => 'error',
			LOG_ALERT   => 'error',
			LOG_CRIT    => 'error',
			LOG_ERR     => 'error',
			LOG_WARNING => 'warn',
			LOG_NOTICE  => 'info',
			LOG_INFO    => 'info',
			LOG_DEBUG   => 'log',
		);
	}

	/**
	 * Initialize Chrome Logger Service
	 */
	protected function initializeChromeLoggerService() {
		if (!$this->chromeLoggerService instanceof ChromeLoggerService) {
			if (Bootstrap::$staticObjectManager instanceof ObjectManagerInterface) {
				$bootstrap = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap');
				/* @var Bootstrap $bootstrap */
				$requestHandler = $bootstrap->getActiveRequestHandler();
				if ($requestHandler instanceof HttpRequestHandlerInterface) {
					$request = $requestHandler->getHttpRequest();
					$response = $requestHandler->getHttpResponse();
					$this->chromeLoggerService = new ChromeLoggerService($request, $response);
					$this->enabled = TRUE;
				}
			}
		}
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
		$this->initializeChromeLoggerService();
		if (!$this->enabled) {
			return;
		}
		if (function_exists('posix_getpid')) {
			$processId = ' ' . posix_getpid();
		} else {
			$processId = ' ';
		}
		$ipAddress = ($this->logIpAddress === TRUE) ? str_pad((isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''), 15) : '';
		$severityLabel = (isset($this->severityLabels[$severity])) ? $this->severityLabels[$severity] : 'UNKNOWN  ';
		$output = strftime('%y-%m-%d %H:%M:%S', time()) . $processId . ' ' . $ipAddress . strtoupper($severityLabel) . ' ' . $packageKey . " " . $message;
		$method = $this->severityLabels[$severity];
		$this->chromeLoggerService->$method($output);
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