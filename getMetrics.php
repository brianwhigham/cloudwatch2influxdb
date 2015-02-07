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

$awsAccount = array(
    'name'      => 'RoundSphere AWS',
    'accessKey' => 'AKIAJSFVEUG6VOGXZ6HA',
    'secretKey' => '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'
);

$region = 'us-west-1';

use Aws\CloudWatch\CloudWatchClient;

$client = CloudWatchClient::factory(array(
    'key'       => $awsAccount['accessKey'],
    'secret'    => $awsAccount['secretKey'],
    'region'    => $region,
));

$nextToken = true;

$namespace = 'AWS/ELB';
$metricName = 'Latency';

$params['Namespace'] = 'AWS/ELB';
$params['MetricName'] = 'Latency';

/*
if (($dimensionName = requestValue('dimensionName')) && ($dimensionValue = requestValue('dimensionValue'))) {
    $params['Dimensions'][] = array(
        'Name'      => $dimensionName,
        'Value'     => $dimensionValue,
    );
// Another way of doing it
            array(
                'Name'  => 'AutoScalingGroupName',
                'Value' => 'My Auto Scaling Group Name',
            ),
}
*/


/*
while ($nextToken) {
    $iterator = $client->getIterator('ListMetrics', $params);
    foreach ($iterator as $metric) {
        echo "{$metric['Namespace']} - {$metric['MetricName']}<br />\n";
        foreach ($metric['Dimensions'] as $dimension) {
            echo "&nbsp;&nbsp;{$dimension['Name']} = {$dimension['Value']}<br />\n";
        }
    }
    $nextToken = false;

}
*/


$getMetricStatisticsParams = $params;
$getMetricStatisticsParams['StartTime']     = date('Y-m-d H:i:s', time() - 14 * 86400);
$getMetricStatisticsParams['EndTime']       = date('Y-m-d H:i:s', time());
$getMetricStatisticsParams['Period']        = 3600;
$getMetricStatisticsParams['Statistics']    = array('Average');
print_r($getMetricStatisticsParams);

try {
    $stats = $client->getMetricStatistics($getMetricStatisticsParams);
    print_r($stats);
} catch (Exception $e) {
    echo "EXCEPTION: ".$e->getMessage();
}
