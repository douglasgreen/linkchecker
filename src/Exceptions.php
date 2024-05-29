<?php

declare(strict_types=1);

namespace DouglasGreen\Exceptions;

if (class_exists(\DouglasGreen\Exceptions\BaseException::class)) {
    return;
}

/**
 * Base class for program exceptions
 * This serves as the parent class for all custom exceptions in the application.
 */
abstract class BaseException extends \Exception {}

/**
 * Data-related exceptions for invalid or unrecognized data
 * Used for general issues related to data handling.
 */
class DataException extends BaseException {}

/**
 * Thrown when input has the wrong data type
 * Example: A function expects an integer, but a string is provided.
 */
class TypeException extends DataException {}

/**
 * Thrown when URL is badly formed or invalid
 * Example: A function receives a URL without the proper scheme (http/https).
 *
 * @see https://www.php.net/manual/en/ref.url.php
 */
class UrlException extends DataException {}

/**
 * Thrown when numeric input is out of range or not an accepted value
 * - A value for age is provided as -5, which is not valid.
 * - A value is not found on an enumerated list of accepted values.
 * - A value like an ID was duplicated when it should be unique.
 */
class ValueException extends DataException {}

/**
 * Thrown for XML-related errors
 * Example: Malformed XML input, failed XML parsing.
 *
 * @see https://www.php.net/manual/en/refs.xml.php
 */
class XmlException extends DataException {}

/**
 * Base class for all database-related exceptions.
 * Used for general database issues.
 */
class DatabaseException extends BaseException {}

/**
 * Thrown when a database connection fails.
 * Example: Unable to connect to the database due to incorrect credentials.
 */
class DatabaseConnectionException extends DatabaseException {}

/**
 * Base class for query-related errors.
 * Used for issues related to database queries.
 */
class DatabaseQueryException extends DatabaseException {}

/**
 * Transaction-related exceptions.
 * Example: Failure to start or commit a database transaction.
 */
class DatabaseTransactionException extends DatabaseException {}

/**
 * Thrown for issues related to file operations, such as:
 * - File not found
 * - File is not readable or writable
 * - File upload errors
 *
 * @see https://www.php.net/manual/en/ref.filesystem.php
 */
class FileException extends BaseException {}

/**
 * Thrown for failure of directory-related actions, such as:
 * - Directory is not empty and can't be deleted
 * - Path is not a directory, but a directory was expected
 * - Unable to change the directory
 */
class DirectoryException extends FileException {}

/**
 * Logic-related exceptions for failure to perform general actions
 * Used for issues related to logical operations and general action failures in
 * the program.
 */
class BadLogicException extends BaseException {}

/**
 * Exception for accessing an invalid key or index in an array or collection or
 * other invalid array operation.
 * Example: Attempting to access an array element with a non-existent key, or
 * trying to perform an invalid operation on an array.
 */
class ArrayException extends BadLogicException {}

/**
 * Exception for invalid arguments passed to a method.
 * Example: A method expects a non-null string argument, but receives null or an
 * integer.
 */
class BadArgumentException extends BadLogicException {}

/**
 * Thrown when operations such as function calls are done in the wrong order.
 * Example: Attempting to use a resource before it has been initialized, or
 * calling a function that depends on another function that hasn't been executed
 * yet.
 */
class OrderException extends BadLogicException {}

/**
 * Thrown when unable to parse a string.
 * Example: Failure to parse a date string, invalid JSON or XML string parsing.
 */
class ParseException extends BadLogicException {}

/**
 * Thrown when a regex returns false when applied due to being malformed.
 * Example: Providing an invalid regular expression pattern that causes a regex
 * function to fail.
 *
 * @see https://www.php.net/manual/en/ref.pcre.php
 */
class RegexException extends BadLogicException {}

/**
 * Base class for general program errors.
 * Used for miscellaneous errors that don't fit other categories.
 */
class ProgramException extends BaseException {}

/**
 * Thrown for errors related to executing commands or processes.
 * Example: Command execution failure, process not found.
 */
class CommandException extends ProgramException {}

/**
 * Thrown for errors related to configuration settings.
 * Example: Missing configuration file, invalid configuration value.
 */
class ConfigurationException extends ProgramException {}

/**
 * Thrown for issues related to missing or incorrect dependencies.
 * Example: Missing required PHP extension, class dependency not met.
 */
class DependencyException extends ProgramException {}

/**
 * Thrown when errors occur using proc_* functions
 * Example: Failure to execute a system process using proc_open().
 *
 * @see https://www.php.net/manual/en/ref.exec.php
 */
class ProcessException extends ProgramException {}

/**
 * Thrown for attempt to violate program security.
 * Example: Unauthorized access attempt, CSRF token mismatch.
 */
class SecurityException extends ProgramException {}

/**
 * Thrown for operations that exceed a time limit.
 * Example: Script execution timeout, network request timeout.
 */
class TimeoutException extends ProgramException {}

/**
 * Service-related exceptions related to PHP or custom services.
 * Example: Errors related to external APIs or internal service layers.
 *
 * @see https://www.php.net/manual/en/refs.remote.other.php
 */
class ServiceException extends BaseException {}

/**
 * Thrown for general API-related errors.
 * Example: API endpoint not found, invalid API response.
 */
class ApiException extends ServiceException {}

/**
 * Curl-related exceptions.
 * Example: Failed curl request, curl initialization error.
 *
 * @see https://www.php.net/manual/en/book.curl.php
 */
class CurlException extends ServiceException {}

/**
 * FTP-related exceptions.
 * Example: FTP connection failure, FTP file transfer error.
 *
 * @see https://www.php.net/manual/en/book.ftp.php
 */
class FtpException extends ServiceException {}

/**
 * Thrown for HTTP-related errors, such as 404 or 500 status codes.
 * Example: Resource not found, internal server error.
 */
class HttpException extends ServiceException {}

/**
 * LDAP-related exceptions.
 * Example: LDAP connection failure, LDAP search error.
 *
 * @see https://www.php.net/manual/en/book.ldap.php
 */
class LdapException extends ServiceException {}

/**
 * Network-related exceptions.
 * Example: Network connectivity issues, DNS resolution failures.
 *
 * @see https://www.php.net/manual/en/book.network.php
 */
class NetworkException extends ServiceException {}

/**
 * Socket-related exceptions.
 * Example: Socket connection failure, socket read/write error.
 *
 * @see https://www.php.net/manual/en/book.sockets.php
 */
class SocketException extends ServiceException {}

/**
 * SSH2-related exceptions.
 * Example: SSH2 connection failure, SSH2 authentication error.
 *
 * @see https://www.php.net/manual/en/book.ssh2.php
 */
class Ssh2Exception extends ServiceException {}
