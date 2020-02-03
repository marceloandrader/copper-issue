<?php

require 'vendor/autoload.php';

use ProsperWorks\CRM;
use ProsperWorks\Config;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$email = getenv('COPPER_EMAIL');
$token = getenv('COPPER_TOKEN');

Config::set($email, $token);
Config::debugLevel(Config::DEBUG_COMPLETE);

$runNumber = intval(file_get_contents('/tmp/lastrun')) + 1;

//// Create company
$company = CRM::getResource(CRM::RES_COMPANY);
$companyB = $company->create([
   'name' => 'Company B ' . $runNumber,
   'details' => 'A company named B'
]);
echo 'Created Company B ', $companyB->id, PHP_EOL;

$companyC = $company->create([
   'name' => 'Company C ' . $runNumber,
   'details' => 'A company named C'
]);
echo 'Created Company C ', $companyC->id, PHP_EOL;

$person = CRM::getResource(CRM::RES_PERSON);
$personRelatedToCompanyB = $person->create([
    'title' => 'Dr.',
    'details' => 'A person named A',
    'name' => 'Person A ' . $runNumber,
    'relations' => [
        [
            'id' => $companyB->id, 'type' => strtolower(CRM::RES_COMPANY)
        ]
    ]
]);
echo 'Created Person A linked to Company B ', $personRelatedToCompanyB->id, PHP_EOL;

$personRelatedToCompanyC = $person->create([
    'title' => 'Dr.',
    'details' => 'A person named D',
    'name' => 'Person D ' . $runNumber,
    'relations' => [
        [
            'id' => $companyC->id, 'type' => strtolower(CRM::RES_COMPANY)
        ]
    ]
]);
echo 'Created Person D linked to Company C ', $personRelatedToCompanyC->id, PHP_EOL;

$opportunity = CRM::getResource(CRM::RES_OPPORTUNITY);
$opportunityC = $opportunity->create([
    'status' => 'Won',
    'name' => 'Opportunity C ' . $runNumber,
    'primary_contact_id' => $personRelatedToCompanyB->id,
    'monetary_value' => 10000,
    'company_id' => $companyC->id,
    'relations' => [
        ['id' => $companyC->id, 'type' => strtolower(CRM::RES_COMPANY)]
    ] // This is not respected, is taken from company B from the primary_contact_id personRelatedToCompanyB
]);
echo 'Created Opportunity A linked to Company B and primary contact the person A ', $opportunityC->id, PHP_EOL;

echo "Continue to delete all resources of this test? (Y/N) - ";

$stdin = fopen('php://stdin', 'r');
$response = fgetc($stdin);
if (strtoupper($response) != 'Y') {
    echo "Aborted.\n";
    exit;
}

$opportunity->delete($opportunityC->id);
$person->delete($personRelatedToCompanyB->id);
$person->delete($personRelatedToCompanyC->id);
$company->delete($companyB->id);
$company->delete($companyC->id);

file_put_contents('/tmp/lastrun', $runNumber);
