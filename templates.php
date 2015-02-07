<?php
$toWatch = array();

$loadBalancerTemplate = array(
    'namespace'     => 'AWS/ELB',
    'metrics'       => array(
        'RequestCount'          => array('Sum'),
        'HTTPCode_Backend_2XX'  => array('Sum'),
        'HTTPCode_Backend_4XX'  => array('Sum'),
        'HTTPCode_Backend_5XX'  => array('Sum'),
        'UnHealthyHostCount'    => array('Average'),
        'HealthyHostCount'      => array('Average'),
        'Latency'               => array('Average', 'Maximum'),
    ),
);

if (isset($loadBalancers) && is_array($loadBalancers)) {
    foreach ($loadBalancers as $to => $lb) {
        $thisConfig = $loadBalancerTemplate;
        $thisConfig['to']           = $to;
        $thisConfig['creds']        = $lb['creds'];
        $thisConfig['region']       = $lb['region'];
        $thisConfig['dimensions']   = array('LoadBalancerName' => $lb['name']);
        $toWatch[$to] = $thisConfig;
    }
}
