<?php

namespace QAAnalyticsVendor;

if (\class_exists('QAAnalyticsVendor\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['QAAnalyticsVendor\\Google\\Client' => 'Google_Client', 'QAAnalyticsVendor\\Google\\Service' => 'Google_Service', 'QAAnalyticsVendor\\Google\\AccessToken\\Revoke' => 'Google_AccessToken_Revoke', 'QAAnalyticsVendor\\Google\\AccessToken\\Verify' => 'Google_AccessToken_Verify', 'QAAnalyticsVendor\\Google\\Model' => 'Google_Model', 'QAAnalyticsVendor\\Google\\Utils\\UriTemplate' => 'Google_Utils_UriTemplate', 'QAAnalyticsVendor\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Google_AuthHandler_Guzzle6AuthHandler', 'QAAnalyticsVendor\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Google_AuthHandler_Guzzle7AuthHandler', 'QAAnalyticsVendor\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'Google_AuthHandler_Guzzle5AuthHandler', 'QAAnalyticsVendor\\Google\\AuthHandler\\AuthHandlerFactory' => 'Google_AuthHandler_AuthHandlerFactory', 'QAAnalyticsVendor\\Google\\Http\\Batch' => 'Google_Http_Batch', 'QAAnalyticsVendor\\Google\\Http\\MediaFileUpload' => 'Google_Http_MediaFileUpload', 'QAAnalyticsVendor\\Google\\Http\\REST' => 'Google_Http_REST', 'QAAnalyticsVendor\\Google\\Task\\Retryable' => 'Google_Task_Retryable', 'QAAnalyticsVendor\\Google\\Task\\Exception' => 'Google_Task_Exception', 'QAAnalyticsVendor\\Google\\Task\\Runner' => 'Google_Task_Runner', 'QAAnalyticsVendor\\Google\\Collection' => 'Google_Collection', 'QAAnalyticsVendor\\Google\\Service\\Exception' => 'Google_Service_Exception', 'QAAnalyticsVendor\\Google\\Service\\Resource' => 'Google_Service_Resource', 'QAAnalyticsVendor\\Google\\Exception' => 'Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \QAAnalyticsVendor\Google\Task\Composer
{
}
if (\false) {
    class Google_AccessToken_Revoke extends \QAAnalyticsVendor\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \QAAnalyticsVendor\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \QAAnalyticsVendor\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle5AuthHandler extends \QAAnalyticsVendor\Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \QAAnalyticsVendor\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \QAAnalyticsVendor\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \QAAnalyticsVendor\Google\Client
    {
    }
    class Google_Collection extends \QAAnalyticsVendor\Google\Collection
    {
    }
    class Google_Exception extends \QAAnalyticsVendor\Google\Exception
    {
    }
    class Google_Http_Batch extends \QAAnalyticsVendor\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \QAAnalyticsVendor\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \QAAnalyticsVendor\Google\Http\REST
    {
    }
    class Google_Model extends \QAAnalyticsVendor\Google\Model
    {
    }
    class Google_Service extends \QAAnalyticsVendor\Google\Service
    {
    }
    class Google_Service_Exception extends \QAAnalyticsVendor\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \QAAnalyticsVendor\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \QAAnalyticsVendor\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \QAAnalyticsVendor\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \QAAnalyticsVendor\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \QAAnalyticsVendor\Google\Utils\UriTemplate
    {
    }
}
