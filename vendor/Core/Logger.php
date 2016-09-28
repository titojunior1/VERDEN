<?php
/**
 * Core_Logger
 *
 * Classe para geração de log. Utiliza a biblioteca log4php
 *
 * @name Core_Logger
 * @author Humberto dos Reis Rodrigues <humberto.rodrigues_assertiva@totalexpress.com.br>
 *
 */
class Core_Logger implements Core_Logger_LogLoggerInterface {

    private $logger;

    public function __construct($logger = 'main', $config = null) {
        Logger::configure($config);
        $this->logger = Logger::getLogger($logger);
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function emergency($message, $context = null) {
        $this->logger->fatal($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function alert($message, $context = null) {
        $this->logger->fatal($message, $context);
    }

    /**
     * Critical conditions.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function critical($message, $context = null) {
        $this->logger->fatal($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function error($message, $context = null) {
        $this->logger->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function warning($message, $context = null) {
        $this->logger->warn($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function notice($message, $context = null) {
        $this->logger->info($message, $context);
    }

    /**
     * Interesting events.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function info($message, $context = null) {
        $this->logger->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function debug($message, $context = null) {
        $this->logger->debug($message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, $context = null) {
        $this->log($level, $message, $context);
    }
}
