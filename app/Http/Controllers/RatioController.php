<?php

namespace App\Http\Controllers;

use AH;
use Illuminate\Http\Request;
use App\Http\Controllers\ElasticController;

class RatioController extends ElasticController
{
    
    private $index = "payslip_latest";

    public function index1(){
    	$data['staff'] = $this->staff();
    	$data['work'] = $this->work();
    	$data['payslip'] = $this->payslip();
    	
    	return response()->success('Success',$data);
	}	

    public function index(){

		$company_id = 183;
		$year = 2017;
		$hireDate = $year+1;

		$query = [
			"bool" => [
			  "must" => [
				// [
				//   "term" => [
				// 	"company_id" => $company_id
				//   ]
				// ],
				[
				  "range" => [
					"hire_date" => [
					  "lt" => $hireDate,
					  "format" => "yyyy"
					]
				  ]
				]
			  ],
			  "must_not" => [
				"range" => [
				  "retirement_date" => [
					"lt" => $year,
					"format" => "yyyy"
				  ]
				]
			  ],
			  "should" => [
				[
				  "exists" => [
					"field" => "hire_date"
				  ]
				]
			  ]
			  
			]
		];

		$aggregation =[
				"genderType"=>[
				  "terms"=>[
					"field"=>"gender"
				  ]
				],
				"employementType"=>[
				  "terms"=>[
					"field"=>"employement_type"
				  ]
				],
				"genderAge"=>[
				  "terms"=>[
					"field"=> "gender"
				  ],
				  "aggs"=> [
				   "age"=> [
					  "avg"=> [
						 "script"=> [ "lang"=> "painless",
						   "source" =>   $this->getAgeScriptSource('birth_date')
						   ]
					  ]
				   	]
				  ]
				],
				"empTypeAge"=>[
				  "terms"=>[
					"field"=> "employement_type"
				  ],
				  "aggs"=> [
					"age"=> [
					   "avg"=> [
						  "script"=> [ "lang"=> "painless",
							"source" =>  $this->getAgeScriptSource('birth_date')
							]
					   ]
						]
				   ]
				]
		];

		$items = $this->elasticsearch->search([
            'index' => "staff_latest",
            'body' => [
                'query' => $query,
                'size' => 0,
                "aggs" => $aggregation
            ]
		]);

		$aggregationRecords = AH::getAgg($items);   // Get Aggregation Result

		$genderType = isset($aggregationRecords['genderType']) ? AH::getGenderType($aggregationRecords['genderType']) : [];
		$employementType = isset($aggregationRecords['employementType']) ? AH::getEmployementType($aggregationRecords['employementType']) : [];
		$genderAge = isset($aggregationRecords['genderAge']) ? AH::getGenderType($aggregationRecords['genderAge']) : [];
		$empTypeAge = isset($aggregationRecords['empTypeAge']) ? AH::getEmployementType($aggregationRecords['empTypeAge']) : [];

		$data[$year]['ratio'] = array_merge($genderType,$employementType);
		$data[$year]['averageAge'] = array_merge($genderAge,$empTypeAge); 
    	return response()->success('Success',$data);
    }
}
