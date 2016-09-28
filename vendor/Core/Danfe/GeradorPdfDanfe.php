<?php

/**
 * Core_Danfe_GeradorPdfDanfe
 *
 * Gera o PDF da DANFE.
 *
 * @package Core
 * @name Core_Danfe_GeradorPdfDanfe
 * @author Humberto dos Reis Rodrigues <humberto.rodrigues_assertiva@totalexpress.com.br>
 *
 */
class Core_Danfe_GeradorPdfDanfe {

	/**
	 * Gerar o PDF da DANFE a partir de um XML da DANFE.
	 *
	 * @param string $arquivo
	 * @param string $chave
	 * @param string $diretorio
	 * @throws RuntimeException
	 */
	public static function exportarXmlParaPdf($arquivo, $chave, $diretorio) {
	    $diretorio = self::_getCaminhoCompleto($diretorio);

	    // necessário converter para UTF-8 para evitar problemas com a classe DomDocument
	    $arquivo = utf8_encode($arquivo);

	    $exportadorDanfe = new DanfeNFePHP($arquivo);
	    $chave = $exportadorDanfe->montaDANFE();
	    $filename = $diretorio . DIRECTORY_SEPARATOR . "NFe{$chave}.pdf";

	    $exportadorDanfe->printDANFE($filename,'F');

	    unset($exportadorDanfe);

	    if(!@file_exists($filename)) {
	        throw new RuntimeException("Falha ao tentar gerar o arquivo NFe{$chave}.pdf em $diretorio. Verifique as permissões do diretório ou se o diretório existe.");
	    }

		return $filename;
	}

	/**
	 * Gerar o PDF da danfe a partir do conteúdo em base64.
	 *
	 * @param string $arquivo
	 * @param string $chave
	 * @param string $diretorio
	 * @throws RuntimeException
	 * @see http://php.net/manual/pt_BR/function.base64-decode.php, http://php.net/manual/pt_BR/function.base64-encode.php
	 */
	public static function exportarBase64ParaPdf($arquivo, $chave, $diretorio) {
	    $diretorio = self::_getCaminhoCompleto($diretorio);
	    $filename = $diretorio . DIRECTORY_SEPARATOR . "NFe{$chave}.pdf";

	    $handle = @fopen($filename, "w");

	    if(!$handle) {
	        throw new RuntimeException('Falha ao tentar gerar o arquivo. Verifique as permissões do diretório.');
	    }

	    $stream = base64_decode($arquivo);

	    if(!$stream) {
	        throw new RuntimeException('Falha ao tentar decodificar os dados. Verifique se o conteúdo não contém espaços.');
	    }

	    @fwrite($handle, $stream);
	    @fclose($handle);

	    if(!@file_exists($filename)) {
	        throw new RuntimeException("Falha ao tentar gerar o arquivo NFe{$chave}.pdf em $diretorio. Verifique as permissões do diretório ou se o diretório existe.");
	    }

		return $filename;
	}

	/**
	 *
	 * @param string $diretorio
	 * @throws RuntimeException
	 * @return string
	 */
	private static function _getCaminhoCompleto($diretorio) {
	    $caminho = realpath($diretorio);

	    if(false === $caminho) {
	        throw new RuntimeException('O diretório para gravar a DANFE não existe ou não está sem permissão de escrita.');
	    }

	    return $caminho;
	}
}