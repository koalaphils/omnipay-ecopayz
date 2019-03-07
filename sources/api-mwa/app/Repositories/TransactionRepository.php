<?php namespace App\Repositories;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use App\Repositories\Repository;
use App\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionRepository extends BaseRepository
{
    protected $model;
    
    const TRANSACTION_STATUS_START = 1;
    const TRANSACTION_STATUS_END = 2;
    const TRANSACTION_STATUS_DECLINE = 3;
    const TRANSACTION_STATUS_ACKNOWLEDGE = 4;
    
    public function __construct(Transaction $model) {
        $this->model = $model;
    }
    
    public function getListPaymentProcessed($cid){
        $rows = $this->model->select("transaction_payment_option_type")
                ->where("transaction_customer_id", $cid)
                ->where("transaction_status", self::TRANSACTION_STATUS_END)
                ->whereNotNull("transaction_payment_option_id")
                ->distinct()->get();
        return $rows;
    }
}