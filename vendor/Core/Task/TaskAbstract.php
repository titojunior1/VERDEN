<?php

/**
 * Core_Task_TaskAbstract
 *
 * Classe para ser usada na implementação de tarefas executadas no cron.
 *
 * @name Core_Task_TaskAbstract
 * @author Humberto dos Reis Rodrigues <humberto.rodrigues_assertiva@totalexpress.com.br>
 *
 */
abstract class Core_Task_TaskAbstract {

    /**
     *
     * @var integer
     */
    private $_pid;

    /**
     *
     * @var DateTime
     */
    private $_dateLastExecution;

    /**
     *
     * @var mixed
     */
    private $_params;

    /**
     *
     * @var Core_Logger_LogLoggerInterface
     */
    private $_log;

    public function __construct($pid = null, DateTime $dateLastExecution = null, $params = null, Core_Logger_LogLoggerInterface $log = null) {
        $this->_pid = $pid;
        $this->_dateLastExecution = $dateLastExecution;
        $this->_params = $params;

        if (null === $log) {
            $this->_createLog();
        }

        $this->_init();
    }

    /**
     * Realiza a executação da tarefa
     */
    abstract public function execute();

    /**
     *
     * @return the $_pid
     */
    public function getPid() {
        return $this->_pid;
    }

    /**
     *
     * @param number $pid
     */
    public function setPid($pid) {
        $this->_pid = $pid;
    }

    /**
     *
     * @return the $_dateLastExecution
     */
    public function getDateLastExecution() {
        return $this->_dateLastExecution;
    }

    /**
     *
     * @param DateTime $date
     */
    public function setDateLastExecution($date) {
        $this->_dateLastExecution = $date;
    }

    /**
     *
     * @return the $_params
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     *
     * @param mixed $params
     */
    public function setParams($params) {
        $this->_params = $params;
    }

    /**
     *
     * @return Core_Logger_LogLoggerInterface
     */
    public function getLogger() {
        return $this->_log;
    }

    /**
     *
     * @param Core_Logger_LogLoggerInterface $log
     * @return Core_Task_TaskAbstract
     */
    public function setLogger(Core_Logger_LogLoggerInterface $log) {
        $this->_log = $log;

        return $this;
    }

    /**
     * Alias for getLogger.
     *
     * @return Core_Logger_LogLoggerInterface
     */
    public function log() {
        return $this->getLogger();
    }

    /**
     * Verifica se existe a data da última execução.
     *
     * @return boolean
     */
    public function hasLastExecution() {
        return ! is_null($this->_dateLastExecution);
    }

    /**
     * Método para fazer a inicialização sem a necessidade de sobreescrer o construtor.
     */
    protected function _init() {}

    protected function _createLog() {
        $config = array(
            'rootLogger' => array(
                'appenders' => array(
                    'default'
                )
            ),
            'appenders' => array(
                'default' => array(
                    'class' => 'LoggerAppenderConsole',
                    'layout' => array(
                        'class' => 'LoggerLayoutPattern',
                        'params' => array(
                            'conversionPattern' => '%date %logger [%level] %message%newline%ex'
                        )
                    )
                )
            )
        );

        $this->_log = new Core_Logger('cron', $config);
    }
}