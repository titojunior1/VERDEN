<?php
/**
 * Controla a execução de scripts agendados via CRON.
 * @author Tito Junior 
 *
 */
class Controller_Cron {

	/**
	 * Array com os elementos que indicam ajuda.
	 * @var array
	 */
	protected static $_array_elementos_ajuda = array ('h', '/h', '?', '/?' );

	/**
	 * Exibe a ajuda básica do controller.
	 */
	public static function ajuda() {
		echo 'Parametros de execucao do Script:' . PHP_EOL;
		echo 'Uso: php -f cron.php nome_job [parametros]' . PHP_EOL;
		echo PHP_EOL;
		echo PHP_EOL;
	}

	/**
	 * Executa um determiando cron job.
	 * @todo Tratar parâmetro de ajuda.
	 * @param array $arg
	 */
	public static function executar($arg) {

		echo PHP_EOL;
		echo str_repeat ( '-', 70 ) . PHP_EOL;
		echo 'Execucao de Cron Jobs' . PHP_EOL;
		echo str_repeat ( '-', 70 ) . PHP_EOL;

		if (! is_array ( $arg )) {
			throw new Exception ( 'Argumentos de chamada não informados' );
		}

		$ajuda = false;
        $metodo = null;

		switch (count ( $arg )) {
			case 4 :
				$classe = $arg [1];
				$metodo = $arg [2];
				$ajuda = true;
				break;
			case 3 :
				$classe = $arg [1];
				$metodo = $arg [2];
				break;
			case 2 :
				$classe = $arg [1];
				break;
			default :
				$ajuda = true;
		}
		
		/*
		// verificar no banco de dados
		$db = Db_Factory::getDbWms ();

		// verificar se o cron existe no banco de dados
		$sql = "SELECT cron_id, cron_pid, cron_ultima_execucao, cron_campo1 FROM cron_scripts WHERE cron_classe = '{$classe}'";
		if (! empty ( $metodo )) {
		    $sql .= " AND cron_metodo = '" . $db->EscapeString ( $metodo ) . "'";
		} else {
		    $sql .= ' AND cron_metodo IS NULL';
		}

		$res = $db->Execute ( $sql );

		if (! $res || $db->NumRows ( $res ) == 0) {
		    throw new Exception ( 'Servico nao cadastrado no banco de dados.' );
		}

		$row = $db->FetchAssoc ( $res );
		$cron_id = $row ['cron_id'];
		$pid = $row ['cron_pid'];
		$ultimaExecucao = $row['cron_ultima_execucao'];
		$parametros = $row['cron_campo1'];
		*/

		if (empty ( $metodo )) {
		    $metodo = 'executar';
		}

		if (in_array ( $metodo, self::$_array_elementos_ajuda ) || in_array ( $classe, self::$_array_elementos_ajuda )) {
			$ajuda = true;
		}
		
		// verificar se é ajuda
		if ($ajuda && empty ( $classe )) {
			self::ajuda ();
			exit ();
		}

		// verificar se o cronjob existe
		$cronjob = array_shift ( $arg );

		$classe_completa = 'Model_Verden_Cron_' . $classe;
		
        if(!class_exists($classe_completa)) {
            $classe_completa = $classe;

            if (! class_exists ( $classe_completa )) {
                throw new Exception ( 'Classe do Cron Job Inexistente!' );
            }
        }

		// criar o model
		if(is_subclass_of($classe_completa, 'Core_Task_TaskAbstract')) {
		    $dataUltimaExecucao = empty($ultimaExecucao)? null: new DateTime($ultimaExecucao);

		    $model = new $classe_completa($pid, $dataUltimaExecucao, $parametros);
		} else {
            $model = new $classe_completa();
		}

		if (in_array ( $metodo, self::$_array_elementos_ajuda ) || (empty ( $metodo ) && method_exists ( $model, 'ajuda' ))) {
			$model->ajuda ();
			echo PHP_EOL;
			exit ();
		}

		// verificar se foi chamada tela de ajuda
		if ($ajuda) {
			$model->ajuda ();
			echo PHP_EOL;
			exit ();
		}		

		try {
			
			// chamar método
			$model->$metodo ();		

		} catch ( Exception $e ) {

			echo 'Erro com a execução: ' . date ( 'Y-m-d H:i:s' ) . PHP_EOL;
			// gravar log de erro
			echo $e->getMessage ();

			echo PHP_EOL;
			echo $e->getTraceAsString ();

		}

		echo PHP_EOL;

	}

	/**
	 * Verifica se já existe a execução desse Cron Script, para evitar concorrência de processos.
	 * @param string $arquivo_lock
	 * @param int $pid
	 */
	private static function verificaExecucao($pid) {

		if (empty ( $pid )) {
			return;
		}

		// verificar se o pid antigo está em curso
		echo "Existe um lockfile, verificando se o PID {$pid} está ativo... " . PHP_EOL;
		echo "$ ps {$pid}" . PHP_EOL;

		ob_start ();
		passthru ( "ps {$pid}", $txt );
		$var = ob_get_contents ();
		ob_end_clean ();
		$tmp = explode ( "\n", $var );

		// quanto o comando 'ps' não encontra o processo, ele retorna 2 linhas; quando encontra, retorna 3 linhas
		if (count ( $tmp ) == 2) {
			echo PHP_EOL . PHP_EOL . "Processo anterior " . $pid . " já encerrado!" . PHP_EOL;
		} else {
			// processo recente, pode estar em andamento
			throw new Exception ( "O processo de PID {$pid} ainda está em curso... Abortanto." );
		}

	}

}
