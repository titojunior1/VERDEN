<?php
/**
 *
 * Centralizador de configurações, autoload de classes, etc.
 *
 * @package WMS
 * @author Jonas Silveira <jonas.silveira@totalexpress.com.br>
 * @copyright Total Express - www.totalexpress.com.br
 */

// horário brasileiro
date_default_timezone_set('Brazil/East');

/***********************************************************
 * Constantes de configuração de ambiente
 **********************************************************/
$configFile = realpath(dirname(__FILE__)) . "/../configuration.php";

if (!file_exists($configFile)) {
    die('O arquivo de configuração do sistema não foi criado.');
}

require $configFile;

defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'production');

$registerGlobal = ini_get('register_globals');

if (empty($registerGlobal)) {
    // register_globals (GPCS) PHP 5.4 >
    @extract($_GET);
    @extract($_POST);

    if (session_status() != PHP_SESSION_NONE && is_array($_SESSION)) {
        @extract($_SESSION);
    }
}

/*
 * Controle para evitar que o usuário envie diversas requisições para uma mesma página em um curto espaço de tempo.
 */
if (strcmp(php_sapi_name(), 'cli') != 0 && strcmp(APPLICATION_ENV, 'development') != 0 && isset($_SERVER['REQUEST_URI'])) {
    @session_start();

    $uri = md5($_SERVER['REQUEST_URI']);
    $count = isset($_SESSION['f5_count']) ? $_SESSION['f5_count'] : 0;
    $currentUri = isset($_SESSION['f5_uri']) ? $_SESSION['f5_uri'] : null;
    $currentTime = isset($_SESSION['f5_time']) ? $_SESSION['f5_time'] : null;
    $now = time();

    if ($uri === $currentUri) {
        if (!is_null($currentTime)) {
            $time = $currentTime;
            $time += 2;

            if ($now > $time) {
                $currentTime = $now;
                $count = 0;
            }
        }

        $count++;
    } else {
        $currentUri = $uri;
        $count = 1;
        $currentTime = $now;
    }

    $_SESSION['f5_count'] = $count;
    $_SESSION['f5_uri'] = $currentUri;
    $_SESSION['f5_time'] = $currentTime;

    if ($count > 20) {
        // enviar email
        @mail(EMAIL_SUPORTE_TI, 'ACESSO INDEVIDO F5', 'ACESSO INDEVIDO EM - ' . $_SERVER ['PHP_SELF'] . ' - ' . date('Y-m-d H:i:s') . ' MAIS DE 20 TENTATIVAS DE F5: ' . $_SERVER['REMOTE_ADDR']);

        header('WWW-Authenticate: Basic realm="Requisição inválida"');
        header('HTTP/1.0 401 Unauthorized');

        @session_destroy();

        exit();
    } elseif ($count > 10) {
        // exibir aviso de acesso indevido
        echo "ERRO: $count tentativas de acesso nos últimos 2 segundos. ";
        echo "<html><head></head><body><p>Aguarde alguns segundos e clique <a href='{$_SERVER['REQUEST_URI']}'>aqui</p></body></html>";
        exit ();
    }
}


/*
 * Realiza o redirecionamento para conexão segura (SSL) somente das páginas de login.
 */
$sslEnabled = defined('SSL_ENABLED') ? SSL_ENABLED: false;

if ($sslEnabled && strcmp(php_sapi_name(), 'cli') != 0
    && isset($_SERVER['REQUEST_URI'])
    && isset($_SERVER['SCRIPT_FILENAME'])
) {

    $scriptFileName = trim($_SERVER['SCRIPT_NAME'], '/');
    $redirectSSL = false;
    $forceRedirect = false;

    // somente esta páginas serão redirecionadas para conexão SSL
    $pagesToSSL = array(
        'index.php', 'ppc/index.php'
    );

    if (in_array($scriptFileName, $pagesToSSL)) {
        if (!isset($_SERVER["HTTPS"]) ||
            ($_SERVER["HTTPS"] === "off" || $_SERVER["HTTPS"] === 0)
        ) {
            $forceRedirect = true;
            $redirectSSL = true;
        }
    } else {
        if (isset($_SERVER["HTTPS"]) &&
            ($_SERVER["HTTPS"] === "on" || $_SERVER["HTTPS"] === 1)
        ) {
            $forceRedirect = true;
            $redirectSSL = false;
        }
    }

    if ($forceRedirect) {
        $redirectUrl = ($redirectSSL ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        header("Location:$redirectUrl");
        exit();
    }
}

/***********************************************************
 * Arquivos de inclusão principais
 **********************************************************/
require_once(PATH_INCLUDES . 'classes.php'); // autoload das classes do sistema
//require_once(PATH_INCLUDES_ANTIGO . '/themes/blue/blue.php');

// Assegura que a pasta com as bibliotecas estão no include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(PATH_SISTEMA . '/vendor'),
    realpath(PATH_SISTEMA . '/module'),
    get_include_path()
)));

require_once 'SplClassLoader.php';

// Registra os autoload
spl_autoload_register('autoload');

/*
 * Carregamento das bibliotecas
 * */

// Core
$coreAutoLoader = new SplClassLoader('Core', PATH_SISTEMA . 'vendor');
$coreAutoLoader->setNamespaceSeparator('_');
$coreAutoLoader->register();

// Wms
$wmsAutoLoader = new SplClassLoader('Wms', PATH_SISTEMA . 'module');
$wmsAutoLoader->setNamespaceSeparator('_');
$wmsAutoLoader->register();

// autoload do pacotes do composer
require PATH_SISTEMA . 'vendor/autoload.php';

// define o encoding padrão para o escape
Core_View::setDefaultEncoding('ISO-8859-1');

//Carregar as Bibliotecas do Composer
require __DIR__ . '/../vendor/autoload.php';
