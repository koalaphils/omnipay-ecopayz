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
    const TRANSACTION_STATUS_VOIDED = 'voided';
    
    const TRANSACTION_TYPE_DEPOSIT = 1;
    const TRANSACTION_TYPE_WITHDRAW = 2;
    
    public function __construct(Transaction $model) {
        $this->model = $model;
    }
    
    public function getTypeList($key = null)
    {
        $list = [
            static::TRANSACTION_TYPE_DEPOSIT => 'deposit',
            static::TRANSACTION_TYPE_WITHDRAW => 'withdraw'
        ];
        
        return $key != null ? $list[$key] : $list;
    }
    
    public function getStatusList($key = null)
    {
        $list = [
            static::TRANSACTION_STATUS_START => 'requested',
            static::TRANSACTION_STATUS_END => 'processed',
            static::TRANSACTION_STATUS_DECLINE => 'declined',
            static::TRANSACTION_STATUS_ACKNOWLEDGE => 'acknowledged',
            static::TRANSACTION_STATUS_VOIDED => 'voided',            
        ];
        
        return $key != null ? $list[$key] : $list;
    }
    
    public function getListPaymentProcessed($cid){
        $rows = $this->model->select("transaction_payment_option_type")
                ->where("transaction_customer_id", $cid)
                ->where("transaction_status", self::TRANSACTION_STATUS_END)
                ->whereNotNull("transaction_payment_option_id")
                ->distinct()->get();
        return $rows;
    }
    
    public function getLastTransactionBitcoin($cid){
        $rows = $this->model->where("transaction_customer_id", $cid)
                ->where("transaction_payment_option_type", "BITCOIN")
                ->where("transaction_is_voided", 0)
                ->orderBy("transaction_id", "desc")
                ->first();
        return $rows;
    }
}