# cloudwatch2influxdb
## Overview
This php-based tool will pull metrics from Amazon Cloudwatch and insert them into InfluxDB.
It is meant to be scheduled in cron.

## Create A Special IAM User For Stats

<pre>
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
</pre>
