<?php

#$container->setParameter('IRURL', 'http://localhost:8181/ldp/platform/uir');
$container->setParameter('IRURL', getenv('IR_URL') . ':8181/ldp/platform/uir');
 