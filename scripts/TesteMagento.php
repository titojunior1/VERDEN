<?php
/*Os produtos e seus respectivos campos, valores, imagens e associações serão automaticamente criados ou atualizados.*/

$client = new SoapClient ('http://www.widehomolog.biz/verden/index.php/api/v2_soap/?wsdl');

$session = $client->login ('erp_api', 'wpr@1020@');

// get attribute set
$attributeSets = $client->catalogProductAttributeSetList($session);
$attributeSet = current($attributeSets);

$result = $client->catalogProductCreate($session, 'simple', $attributeSet->set_id, '3637835584', array(
    'categories' => array(2),
    'websites' => array(1),
    'name' => 'Tito',
    'description' => 'Tito descricao',
    'short_description' => 'TIto Pequena Descricao',
    'weight' => '10',
    'status' => '1',
    'url_key' => 'product-url-key',
    'url_path' => 'product-url-path',
    'visibility' => '4',
    'price' => '100',
    'tax_class_id' => 1,
    'meta_title' => 'Product meta title',
    'meta_keyword' => 'Product meta keyword',
    'meta_description' => 'Product meta description'
));

$client->endSession ($session);