A TYPO3 Flow Logger Backend to send log the the Chrome Console
==============================================================

The Logger Backend can display your application in the Chrome Console, with the extension "Chrome Logger".

How to use it ?
---------------

You need to configure the Logger Backend in ``Settings.yaml``::

```yaml
TYPO3:
  Flow:
    log:
      systemLogger:
        backend:
          0: 'TYPO3\Flow\Log\Backend\FileBackend'
          1: 'Ttree\ChromeLogger\Log\Backend\ChromeConsoleBackend'
        backendOptions:
          0:
            logFileURL: '%FLOW_PATH_DATA%Logs/System_Development.log'
            createParentDirectories: TRUE
            severityThreshold: '%LOG_INFO%'
            maximumLogFileSize: 10485760
            logFilesToKeep: 1
            logMessageOrigin: FALSE
          1:
            severityThreshold: '%LOG_CRIT%'
```

TODO
----

Feel free to open issue if you need a specific feature and better send a pull request. Here are some idea for future 
improvements:

* More complete log output
* Support for exception and backtrace
	
Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

License
-------

Licensed under GPLv3+, see [LICENSE](LICENSE)