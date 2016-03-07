<?php

require_once __DIR__.'/vendor/autoload.php';

use GraphAware\Neo4j\Client\ClientBuilder;
use GuzzleHttp\Client;
$host = getenv('DB_HOST');
$esHost = getenv('ES_HOST');
$client = ClientBuilder::create()
	->addConnection('default', $host)
	->build();

$esClient = new Client();

while (true) {
	try {
		$result = $client->run('CREATE (a:Test)-[:RELATES]->(b:EndTest)');
		$now = new \DateTime("NOW", new \DateTimeZone("UTC"));
		$ts = $now->format(DATE_ISO8601);
		$format = $now->format('Y-m-d');
		$indexUrl = $esHost . '/transactions-' . $format . '/logs';
		$data = [
			'@timestamp' => $ts,
			'nodes_created' => $result->summarize()->updateStatistics()->nodesCreated(),
			'rels_created' => $result->summarize()->updateStatistics()->relationshipsCreated()
		];
		$esClient->post($indexUrl, ['json' => $data]);
	} catch (\Exception $e) {
		echo $e->getMessage() . PHP_EOL;
		sleep(5);
	}
}