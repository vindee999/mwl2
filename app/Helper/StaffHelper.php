<?php

namespace App\Helper;

class StaffHelper
{
	public static function getFilter()
    {
    	$must = array();
    	foreach(request()->query() as $query => $value){
			$attribute = self::originalAttribute($query);
			if(isset($attribute,$value)){
				$must["must"][]["match"][$attribute] = $value;
			}
		}
		return $must;
    }

	public static function getSortBy()
    {
        $sort = [ "id" => ["order" => "asc"]];

        if(request()->has('sort_by') && !empty(request()->sort_by)){
        	$attribute = self::sortAttribute(request()->sort_by);
        	if($attribute){
        		$sort = [ request()->sort_by => ["order" => "asc"]];
        	}              
        }
        return $sort;
    }

	public static function originalAttribute($index)
    {
    	$attributes = [
	    	"firstName" => "firstName",
	        "id" => "id",
		    "gender" => "gender",
		    "employementType" => "employementType",
		    "birthDate" => "birthDate",
		    "tel2" => "tel2",
		    "middleName" => "middleName",
		    "companyId" => "companyId",
		    "createdTime" =>"createdTime",
		    "retirementDate" => "retirementDate",
		    "lastName" => "lastName",
		    "hireDate" => "hireDate",
		    "updatedTime" => "updatedTime",
		    "telCountry" => "telCountry",
		    "email" => "email",
		    "username" => "username",
		    "employeeCode" => "employeeCode",
		    "tel1" => "tel1"
		];

        return isset($attributes[$index]) ? $attributes[$index] : null; 
    }

    public static function sortAttribute($index)
    {
    	$attributes = [
	        "id" => "id",
		    "gender" => "gender",
		    "employementType" => "employementType",
		    "birthDate" => "birthDate",
		    "companyId" => "companyId",
		    "createdTime" =>"createdTime",
		    "retirementDate" => "retirementDate",
		    "lastName" => "lastName",
		    "hireDate" => "hireDate",
		    "updatedTime" => "updatedTime",
		    "employeeCode" => "employeeCode",
		];

        return isset($attributes[$index]) ? $attributes[$index] : null; 
    }
}