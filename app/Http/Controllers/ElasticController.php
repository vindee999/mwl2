<?php

namespace App\Http\Controllers;

use Elasticsearch\Client;
use Illuminate\Http\Request;

class ElasticController extends Controller
{
    protected $elasticsearch;
    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    protected function getSumAgg(array $getTerms){  // SUM Aggregation
        $sumTerms = [];
        foreach($getTerms as $k=>$terms){
           $sumTerms[$k] = [
                   "sum" => [
                       "field" => $terms
                   ]
               ];
        }
        return $sumTerms;
    }

    protected function getAvgAgg(array $getTerms){  // AVG Aggregation
        $avgTerms = [];
        foreach($getTerms as $k=>$terms){
           $avgTerms[$k] = [
                   "avg" => [
                       "field" => $terms
                   ]
               ];
        }
        return $avgTerms;
    }

    protected function getSumScriptHoursAgg(array $getTerms){  // SUM Aggreagation for Hour Script
        $newTerms = [];
       foreach($getTerms as $k=>$terms){
           $newTerms[$k] = [
                   "sum" => [
                       "script" => [
                           "source" => "doc['$terms'].value / 60"
                       ]
                   ]
               ];
       }
       return $newTerms;
    }

    protected function getScriptHoursTerms(array $getTerms){  // Terms Aggregation for Hour Script
        $newTerms = [];
       foreach($getTerms as $k=>$terms){
           $newTerms[$k] = [
                   "terms" => [
                       "script" => [
                           "source" => "doc['$terms'].value / 60"
                       ]
                   ]
               ];
       }
       return $newTerms;
    } 

    protected function getAgeScriptSource($term){
        $source = " (doc['$term'].length > 0 ) ? Period.between(doc['$term'].value.toLocalDate() , Instant.ofEpochMilli(System.currentTimeMillis()).atZone(doc['$term'].value.getZone()).toLocalDate()  ).getYears() : 0 ";
        return $source;
    }
}
