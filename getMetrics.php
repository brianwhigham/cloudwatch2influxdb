<?php
if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    echo "Error: composer autoload file not found.  Run 'composer install'\n";
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

if (file_exists(__DIR__.'/awsCreds.php')) {
    include __DIR__.'/awsCreds.php';
}

if (!$awsAccount) {
    echo "Error: \$awsAccount not defined\n";
    exit;
}

if (file_exists(__DIR__.'/influxCreds.php')) {
    include __DIR__.'/influxCreds.php';
}

if (!$influxCreds) {
    echo "Error: \$influxCreds not defined\n";
    exit;
}

if (file_exists(__DIR__.'/toWatch.php')) {
    include __DIR__.'/toWatch.php';
}

require __DIR__.'/templates.php';

if (!$toWatch) {
    echo "nothing \$toWatch\n";
    exit;
}

use Aws\CloudWatch\CloudWatchClient;

$influxClient = new \crodas\InfluxPHP\Client(
    $influxCreds['host'],
    $influxCreds['port'],
    $influxCreds['user'],
    $influxCreds['password']
);

foreach ($toWatch as $watch) {

    $awsClient = CloudWatchClient::factory(array(
        'key'       => $awsAccount['accessKey'],
        'secret'    => $awsAccount['secretKey'],
        'region'    => $watch['region'],
    ));

    $params = array();
    echo "{$watch['namespace']} @ {$watch['region']} ";
    if (!empty($params['dimensions'])) {
        foreach ($params['dimensions'] as $name => $value) {
            echo "[{$name}={$value}] ";
        }
    }
    echo "\n";

    foreach ($watch['metrics'] as $metric => $statistics) {

        $columnName = "{$watch['to']}.{$metric}";
        echo "  metric: {$metric}  => {$columnName}\n";

        $params['Namespace'] = $watch['namespace'];
        $params['MetricName'] = $metric;

        if (!empty($watch['dimensions'])) {
            foreach ($watch['dimensions'] as $dimensionName => $dimensionValue) {
                $params['Dimensions'][] = array(
                    'Name'      => $dimensionName,
                    'Value'     => $dimensionValue,
                );
            }
        }
        $params['StartTime']     = date('Y-m-d H:i:s', time() - 14 * 86400);
        $params['EndTime']       = date('Y-m-d H:i:s', time());
        $params['Period']        = 3600;
        $params['Statistics']    = $statistics;

        try {
            $stats = $awsClient->getMetricStatistics($params);

            $datapoints = array();

            foreach ($stats['Datapoints'] as $point) {
                echo "    {$metric} @ {$point['Timestamp']}     \t";
                $thisPoint = array(
                    'time'  => strtotime($point['Timestamp']),
                );
                foreach ($statistics as $stat) {
                    $thisPoint[$stat] = (float)$point[$stat];
                    echo "\t{$stat} => {$point[$stat]}";
                }
                echo "\n";
                $datapoints[] = $thisPoint;
            }

            $influxDb = $influxClient->$influxCreds['database'];
            $influxDb->insert($columnName, $datapoints);

        } catch (Exception $e) {
            echo "EXCEPTION: ".$e->getMessage();
        }
    }
}
