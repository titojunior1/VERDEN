<?php
require_once 'includes/verden.php';


$result = base64_decode('dmQxMjA5ODY=');

$obj = new Model_Verden_Cron_MagentoCron();
$obj->CadastraPedidosSaidaMagento();

// $obj = new Model_Verden_Cron_KplCron();
// $obj->AtualizaEstoqueKpl();