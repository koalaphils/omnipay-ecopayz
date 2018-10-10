<?php

 namespace DbBundle\Entity\Interfaces;

 interface GatewayInterface
 {
     const OPERATION_ADD = 'add';
     const OPERATION_SUB = 'sub';

     public function getAccount();

     public function getAccountTo();

     public function getGatewayCurrency();

     public function getIdentifier();

     public function getGatewayPaymentOption();

     public function getReferenceNumber();

     public function getTransactionDetails();

     public function getFinalAmount($to = false);

     public function processGateway();

     public function getOperation($to = false);
 }