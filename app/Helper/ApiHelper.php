<?php

namespace App\Helper;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ApiHelper
{
    public static function getPage()
    {
        $page =  LengthAwarePaginator::resolveCurrentPage();
        return $page > 0 ? $page : 1;
    }

    public static function getPageSize()
    {
        $size = (request()->has('page_size')  &&  request()->page_size > 0 ) ? request()->page_size : 10;
        return $size;
    }

    public static function getPageFrom()
    {
        $from = ((self::getPage()-1) * self::getPageSize());
        return $from;
    }

    public static function getPreviousPage($total)
    {
        $page = self::getPage();
        $last = self::getLastPage($total);
        $previous = ($page == 1 || $page > $last ) ? $last : $page - 1;
        return $previous;
    }

    public static function getNextPage($total)
    {
        $page = self::getPage();
        $last = self::getLastPage($total);
        $next = ($page == $last || $page > $last) ? 1 : $page + 1;
        return $next;
    }

    public static function getLastPage($total)
    {
        $pages = ceil($total / self::getPageSize());
        return $pages;
    }

    public static function getSortBy()
    {
        $sort;
        if(request()->has('sort_by') && !empty(request()->sort_by)){
            $sort = [ request()->sort_by => ["order" => "asc"]];
        }else{
            $sort = [ "id" => ["order" => "asc"]];
        }
        return $sort;
    }

    public static function getSource(array $items)
    {
        $sourceItems = [];
        if(isset($items['hits']['hits'])){
            $sourceItems = Arr::pluck($items['hits']['hits'], '_source');
        }
        return $sourceItems;
    }

    public static function getResultCount(array $items)
    {
        $count = 1;
        if(isset($items['hits']['total'])){
            $count = $items['hits']['total']['value'];
        }
        return $count;
    }

    public static function getAggregation(array $items)
    {
        $aggItems = [];
        if(isset($items['aggregations'])) {
            foreach($items['aggregations'] as $key => $aggregation){
                
                if(isset($aggregation['buckets'])){
                    dd($aggregation['buckets']);
                }else{
                    $aggItems[$key] = $aggregation['doc_count'] ? $aggregation['doc_count'] : 0;
                }
            }
        }
        return $aggItems;
    }


    // New 
    public static function getAgg(array $items)
    {
        $aggItems = [];
        if(isset($items['aggregations'])) {
            foreach($items['aggregations'] as $key => $aggregation){
                
                if(isset($aggregation['buckets'])){
                    foreach($aggregation['buckets'] as $k1 => $bucket1){
                        if(count($bucket1) == 2 && array_key_exists("key", $bucket1) && array_key_exists("doc_count", $bucket1)){
                            $aggItems[$key][$bucket1['key']] = $bucket1['doc_count'];
                        }else{
                            $bkey = $bucket1['key'];
                            $bucket1_ag = self::removeKey($bucket1);
                            foreach($bucket1_ag as $keybucket => $aggBucket){
                                
                                if(isset($aggBucket['buckets'])) {
                                    if(isset($aggBucket['buckets'][0])){
                                        $aggItems[$key][$bkey][$keybucket] = $aggBucket['buckets'][0]['key'];
                                    }else{
                                        $aggItems[$key][$bkey][$keybucket] = 0;
                                    }
                                }else if(isset($aggBucket['value'])){
                                   $aggItems[$key][$bkey][$keybucket] = $aggBucket['value']; 
                                }else if(isset($aggBucket['hits']) && isset($aggBucket['hits']['hits']) ) {
                                    // Works for Size 1
                                    $bucket2_ag = self::getSource($aggBucket);
                                    foreach($bucket2_ag as $keybucket2 => $aggBucket2){
                                        foreach ($aggBucket2 as $keybucket3 => $aggBucket3) {
                                            $aggItems[$key][$bkey][$keybucket3] = $aggBucket3;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }else{
                    $aggItems[$key] = $aggregation['value'] ? $aggregation['value'] : 0;
                }
            }
        }
        //dd($aggItems);
        return $aggItems;
    }

    public static function removeKey(array $items)
    {
        unset($items['key']);
        unset($items['doc_count']);
        return $items; 
    }

    public static function getMonths()
    {
       $month = ['january','february','march','april','may','june','july','august','september','october','november','december'];
       return $month;  
    }

    public static function getMonthKey($key)
    {
       $month = self::getMonths();
       return isset($month[$key-1]) ? $month[$key-1] : null;   
    }
    
    
    public static function addMissingMonthData(array $items)
    {
        $range = range(1, 12);
        $arrKeys = array_keys($items);
        $missing =  array_diff($range, $arrKeys); 
        if(count($items) > 0){
            foreach($missing as $miss){
                $items[$miss] = array_fill_keys(array_keys(current($items)),0);
            }
        }
        return $items;
    }

    public static function getGenderType(array $items)
    {
       $gender = [1=>'male',2=>'female',3=>'others'];
       $range = range(1, 3);
       $arrKeys = array_keys($items);
       $missing =  array_diff($range, $arrKeys); 
       $genderType = [];

        foreach($missing as $miss){  // Adding Missing Gender
            $items[$miss] = 0;
        }

        foreach($items as $k => $i ){  // Add Keys
            if(array_key_exists($k,$gender)){   // Check Key exist
               $genderType[$gender[$k]] = is_array($i) ? current($i) : $i;
            }else{                              // Remove Unwanted values
                unset($i);
            } 
        }
       return $genderType;  
    }


    public static function getEmployementType(array $items)
    {
       $empType = [1=>'regular',2=>'irregular'];
       $range = range(1, 2);
       $arrKeys = array_keys($items);
       $missing =  array_diff($range, $arrKeys); 
       $employementType = [];

        foreach($missing as $miss){  // Adding Missing Gender
            $items[$miss] = 0;
        }

        foreach($items as $k => $i ){  // Add Keys
            if(array_key_exists($k,$empType)){   // Check Key exist
                $employementType[$empType[$k]] = is_array($i) ? current($i) : $i;
            }else{                              // Remove Unwanted values
                unset($i);
            } 
        }
       return $employementType;  
    }

    
}