<?php namespace App\Repositories;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use App\Repositories\Repository;
use App\User;
use Illuminate\Support\Facades\DB;

class UserRepository extends BaseRepository
{
    protected $model;
    
    public function __construct(User $user) {
        $this->model = $user;
    }
    
    public function getByCustomerID($cid){
        $row = DB::table("user as a")->join("customer as b", "a.user_id", "=" ,"b.customer_user_id")
                ->where("b.customer_id", $cid)
                ->first();
        return $row;
    }
}