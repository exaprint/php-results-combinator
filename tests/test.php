<?php

require '../vendor/autoload.php';

function loadCSV($filename)
{
    $rows = [];

    if (($handle = fopen($filename, "r")) !== false) {

        $line = 0;
        $cols = array();

        while (($raw = fgetcsv($handle, 1000, ",")) !== false) {

            if ($line === 0) {
                $cols = $raw;
            } else {
                $row = array();
                for ($i = 0; $i < count($raw); $i++) {
                    $row[$cols[$i]] = $raw[$i];
                }
                $rows[] = $row;

            }
            $line++;
        }
        fclose($handle);
    }
    return $rows;
}
/*
 * "order.id",
"project.ati_amount",
"project.vat_amount",
"project.et_amount",
"client.id",
"client.company_name",
"client.email",
"client.address.line1",
"client.address.line2",
"client.address.line3",
"client.address.postcode",
"client.address.city",
"client.contact_name",
"client.contact_forename",
"order.reference",
"order.ati_amount",
"order.vat_amount",
"order.et_amount",
"product.name",
"product.subfamily",
"product.family",
"pboxer.company_name",
"pboxer.email",
"pboxer.address.line1",
"pboxer.address.line2",
"pboxer.address.line3",
"pboxer.address.postcode",
"pboxer.address.city",
"pboxer.contact_name",
"pboxer.contact_forename",
"order.fees.id",
"order.fees.quantity",
"order.fees.unit_amount",
"order.fees.ati_amount",
"order.fees.et_amount",
"order.fees.vat_amount",
"order.fees.type",
"product.options.id",
"product.options.label",
"product.options.value",
"product.options.unit"
 */

class Project {
    public $ati_amount;
    public $vat_amount;
    public $et_amount;
}

class Order {
    public $id;
    public $reference;
    public $ati_amount;
    public $vat_amount;
    public $et_amount;
    /** @var Fee[] */
    public $fees = [];
    public $client;
}

class Client {
    public $id;
    public $company_name;
    public $email;
    public $address;
    public $contact_name;
    public $contact_forename;
}

class Fee {
    public $id;
    public $quantity;
    public $unit_amount;
    public $type;
    public $ati_amount;
    public $vat_amount;
    public $et_amount;
}

class Option {
    public $id;
    public $label;
    public $value;
    public $unit;
}

class Address {
    public $line1;
    public $line2;
    public $line3;
    protected $_postcode;
    protected $_city;

    public function setLocation($city, $postcode){
        $this->_city = $city;
        $this->_postcode = $postcode;
    }
}

$rows = loadCSV(__DIR__ . '/dataset.csv');

$combinator = new \RBM\ResultsCombinator\ResultsCombinator();
$combinator->setIdentifier("id");
$combinator->setClass('Order');
$combinator->addSubClass("project", "Project");
$combinator->addSubClass("pboxer", "Client");
$combinator->addSubClass("pboxer.address", "Address");
$combinator->addSubClass("client", "Client");
$combinator->addSubClass("client.address", "Address");
$combinator->addMethod("client.address", "setLocation", ["client.address.city", "client.address.postcode"]);
$combinator->addGroup("fees", "id", "Fee");
$combinator->addGroup("options", "id", "Option");

$results = $combinator->process($rows);

print_r($results);