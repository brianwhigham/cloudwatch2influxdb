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

        $latest = time() - 14 * 86400;
        try {

            $results = $influxClient->$influxCreds['database']->query("
                SELECT  *
                FROM    {$columnName}
                LIMIT 1
            ");
            foreach ($results as $row) {
                $latest = $row->time;
            }
        } catch (Exception $e) {
            echo "Exception - maybe series doesn't exist for {$columnName}\n";
        }

        echo "Looking for data since {$latest}\n";

        $remaining = ceil((time() - $latest) / 60);
        $page = 0;
        $influxDb = $influxClient->$influxCreds['database'];

        do {
            echo "Requesting page {$page} with remaining={$remaining}\n";

            $params['StartTime']     = date('Y-m-d H:i:s', $latest);
            $params['EndTime']       = date('Y-m-d H:i:s', $latest + 60 * 1440);
            $params['Period']        = 60;
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

                if ($datapoints) {
                    $influxDb->insert($columnName, $datapoints);
                }
                $remaining -= 1440;
                echo "Bottom of loop with remaining={$remaining}\n";
                $latest += 60*1440;
                $page++;

            } catch (Exception $e) {
                echo "EXCEPTION: ".$e->getMessage();
            }
        } while ($remaining >= 0);
    }
}
