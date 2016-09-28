<?php

class Core_Stdlib_Hydrator_ClassMethods implements Core_Stdlib_Hydrator_HydratorInterface {

    /*
     * (non-PHPdoc)
     * @see Core_Stdlib_Hydrator_HydratorInterface::hydrate()
     */
    public function hydrate(array $data, $object) {
        if (! is_object($object)) {
            throw new BadMethodCallException(sprintf(
                '%s expects the provided $object to be a PHP object)', __METHOD__));
        }

        foreach ($data as $property => $value) {
            $property = $this->_toCamelCase($property);
            $method = 'set' . ucfirst($property);


            if (is_callable(array($object, $method))) {
//                 $value = $this->hydrateValue($property, $value, $data);
                $object->$method($value);
            }
        }

        return $object;
    }

    /*
     * (non-PHPdoc)
     * @see Core_Stdlib_Hydrator_HydratorInterface::extract()
     */
    public function extract($object) {
        // TODO Auto-generated method stub
    }

    private function _toCamelCase($property) {
        $explode = explode('_',  $property);

        $toCamelCase = array_map(array($this, '_normalizeProperty'), $explode);

        return implode('', $toCamelCase);
    }

    private function _normalizeProperty($property) {
        return ucfirst($property);
    }
}