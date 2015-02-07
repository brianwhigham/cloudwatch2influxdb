<?php
$awsAccount = array(
    'name'      => 'An optional name',
    'accessKey' => 'Your AWS Access Key',
    'secretKey' => 'Your AWS Secret Key',
);

/*
Use this IAM Policy to create a new user

{ "Version": "2015-02-06",
  "Statement": [ {
      "Sid": "Stmt1406686005000",
      "Effect": "Allow",
      "Action": [
        "cloudwatch:GetMetricStatistics",
        "cloudwatch:ListMetrics"
      ],
      "Resource": [ "*" ]
    } ] }

*/





