<?php

/**
 * Core_Stdlib_ArrayUtils
 *
 * Métodos utilitários para trabalhar com array.
 *
 * @name Core_Stdlib_ArrayUtils
 *
 */
final class Core_Stdlib_ArrayUtils {

	/**
	 * Retorna um array com os valores da coluna $columnValue do $array.
	 * Opcionalmente pode ser informado $columnKey para indexado o array pelo valor em $columnKey.
	 *
	 * @param array $array
	 * @param string $columnKey
	 * @param string $columnValue
	 * @return multitype:unknown
	 */
	public static function map(array $array, $columnValue, $columnKey=null) {
		$newArray = array();

		foreach($array as $value) {
			if(array_key_exists($columnValue, $value)) {
				$val = $value[$columnValue];

				if(is_null($columnKey)) {
					$newArray[] = $val;
				} elseif(array_key_exists($columnKey, $value)) {
					$key = $value[$columnKey];
					$newArray[$key] = $val;
				}
			}
		}

		return $newArray;
	}

}