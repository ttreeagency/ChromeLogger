A TYPO3 Flow Logger Backend to send log the the Chrome Console
==============================================================

The Logger Backend can display your application in the Chrome Console, with the extension "[Chrome Logger](http://craig.is/writing/chrome-logger)".

![Sample image based on the TYPO3 Neos Demo Site](https://dl.dropboxusercontent.com/s/s41213dfjnscsu5/2015-01-20%20at%2019.33%202x.png?dl=0)

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

By default the package use a grouped output to show log on mulitple lines:

![Default Output](https://dl.dropboxusercontent.com/s/hzct8864ch943k2/2015-01-20%20at%2023.05%202x.png?dl=0)

Use it in your own package
--------------------------

You can use the default system logger provided by Flow, but you can also inject the ```ChomeLoggerServive``` in your own class. 

Check the following code for some example:

```php
$this->chromeLoggerInstance->log('Foo');
$this->chromeLoggerInstance->info('Foo');
$this->chromeLoggerInstance->warn('Foo');
$this->chromeLoggerInstance->error('Foo');

// You can group your log entry, groupCollapsed method for a more compact rendering
$this->chromeLoggerInstance->group('String');
$this->chromeLoggerInstance->log('Foo');
$this->chromeLoggerInstance->log(array('Foo'));
$this->chromeLoggerInstance->groupEnd();

$this->chromeLoggerInstance->group('Table');
$this->chromeLoggerInstance->table('Foo');
$this->chromeLoggerInstance->table(array('Foo'));
$this->chromeLoggerInstance->groupEnd();

$this->chromeLoggerInstance->group('Object');
$this->chromeLoggerInstance->log($this->request->getHttpRequest());
$this->chromeLoggerInstance->log(new \DateTime());
$this->chromeLoggerInstance->groupEnd();
```

**Warning**: HTTP Header are limited to 256kb, currently if you hit the limit, you wont see any logs in the console.

![Sample image with more advanced log values](https://dl.dropboxusercontent.com/s/slscnvv0wqrryql/2015-01-20%20at%2021.36%202x.png?dl=0)


TODO
----

Feel free to open issue if you need a specific feature and better send a pull request. Here are some idea for future 
improvements:

* Support for exception and backtrace
* Add support for Header compression (gzip) to mitigate the 256kb limit (need change in the Chrome extension too)
* Better reflexion, maybe
	
Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

License
-------

Licensed under GPLv3+, see [LICENSE](LICENSE)