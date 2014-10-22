<?php

//use Symfony\Component\DependencyInjection\Reference;

//$container->setParameter('IRURL', 'http://localhost:8181/ldp/ir');
$container->setParameter('IRURL', 'http://sandbox.fusepool.info:8181/ldp/user-interaction-requests');
$container->setParameter('TransformerUrl', 'http://punditTransformer.netseven.it/');
//$container->setParameter('TransformerUrl', 'http://punditTransformer.local/');
/*
parameters:
#    net7_pundit_transformer.example.class: Net7\PunditTransformerBundle\Example
IRURL: http://localhost:8181/ldp/ir-ldpc
TransformerUrl: http://punditTransformer.local/

services:
#    net7_pundit_transformer.example:
#        class: %net7_pundit_transformer.example.class%
#        arguments: [@service_id, "plain_value", %parameter%]
*/