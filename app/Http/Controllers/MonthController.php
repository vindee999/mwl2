<?php

namespace App\Http\Controllers;

use AH;
use Elasticsearch\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MonthController extends Controller
{
    private $elasticsearch;
    private $index = "payslip_latest";

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;

    }

    public function index(){
    	$data['staff'] = $this->staff();
    	$data['work'] = $this->work();
    	$data['payslip'] = $this->payslip();
    	
    	return response()->success('Success',$data);
    }

    public function graph1(){
    	$data['staff'] = $this->staff();
    	//$data['work'] = $this->graphWork();
    	
    	return response()->success('Success',$data);
    }

    public function graph(){
    	$query = [ 
			"bool"=> [
			      "must"=> [
			        [
			          "range"=> [
			            "start_date"=> [
			              "lt"=> "2018",
			              "gte"=> "2017",
			              "format"=> "yyyy"
			            ]
			          ]
			        ],
			        [
			          "term"=> [
			            "staff_id"=> 1377
			          ]
			        ]
			      ]
			    ]
		  ];

		$aggTerms = [	
			"workingDays" => "working_days" , 
			"paidHolidays" =>  'paid_leave_used_days' , 
			"holiday" => 'leagal_holiday_days',
			"absence" => 'absense_days',
			"totalSalary" => 'fixed_working_cost',
            "totalDeduction"=> 'deduction',
            "totalProceeds"=> 'labour_cost',
		];

		$scriptHoursTerms = [	
				"legalWorkingHours"=> 'legal_working_time_month',
	            "workingHours"=> 'work_time',
	            "overtime"=> 'total_overtime',
	            "averageWorkingHours"=> 'average_work_time'
			];

		$getSumAgg = $this->getSumAgg($aggTerms);
		$getSumScriptTerms = $this->getSumScriptHoursTerms($scriptHoursTerms);
		
		$getAllSumTerms = array_merge($getSumAgg,$getSumScriptTerms);

		$aggregation = [
		    "work"=> [
		      "terms"=> [
		        "script"=> [ "source" => "doc['start_date'].value.getMonthValue()" ],
		        "order"=> [
		          "_key"=> "asc"
		        ],
		        "min_doc_count"=> 1
		      ],
		      "aggregations"=> $getAllSumTerms
			]
		];

		$items = $this->elasticsearch->search([
            'index' => "month_work",
            'body' => [
                'query' => $query,
                'size' => 0,
                "aggs" => $aggregation
            ]
        ]);

        $aggregationRecords = AH::getAgg($items);   // Get Aggregation Result

        $workAggregation = $aggregationRecords['work'] ? $aggregationRecords['work'] : [];
        $mergeRecords = AH::addMissingMonthData($workAggregation); // Add Missing Months
        ksort($mergeRecords);   // Sort by Key

        $work = [];
        foreach($mergeRecords as $aggKey => $aggItem){    // Change Key Values
        	$work[AH::getMonthKey($aggKey)] = $aggItem; 
        }

        $data['work'] = $work ? $work : (object)[];    	
    	return response()->success('Success',$data);
    }

    

    private function staff(){
    	$query = [ 
        			"bool"=> [
					      "must"=> [
					        [
					          "term"=> [
					            "id"=> 1377
					          ]
					        ]
					      ]
					    ]
				  ];

		$items = $this->elasticsearch->search([
            'index' => "staff_latest",
            'body' => [
                'query' => $query,
            ]
        ]);
		$totalRecords = AH::getResultCount($items);
		$items = AH::getSource($items);

		$data = $totalRecords > 0 ? current($items) : (object)[];
		return $data;
    }

    private function work(){

    	$query = [ 
        			"bool"=> [
					      "must"=> [
					        [
					          "range"=> [
					            "start_date"=> [
					              "lt"=> "2018",
					              "gte"=> "2017",
					              "format"=> "yyyy"
					            ]
					          ]
					        ],
					        [
					          "term"=> [
					            "staff_id"=> 1377
					          ]
					        ]
					      ]
					    ]
				  ];

		$aggTerms = [	
				"workingDays" => "working_days" , 
				"paidHolidays" =>  'paid_leave_used_days' , 
				"holiday" => 'leagal_holiday_days',
				"absence" => 'absense_days',
				"basicSalary" => 'fixed_working_cost',
	            "overtimeLaborCosts"=> 'total_overtime_cost',
	            "totalAllowance"=> 'allowance',
	            "totalDeduction"=> 'deduction',
			];

		$scriptHoursTerms = [	
				"legalWorkingHours"=> 'legal_working_time_month',
	            "actualWorkingHours"=> 'actual_working_time',
	            "workingHours"=> 'work_time',
	            "overtime"=> 'total_overtime',
	            "averageWorkingHours"=> 'average_work_time'
			];

		$getTerms = $this->getAggTerms($aggTerms); 
		$getScriptTerms = $this->getScriptHoursTerms($scriptHoursTerms);
		$getAllTerms = array_merge($getTerms,$getScriptTerms);

		$getSumAgg = $this->getSumAgg($aggTerms);
		$getSumScriptTerms = $this->getSumScriptHoursTerms($scriptHoursTerms);
		$getAllSumTerms = array_merge($getSumAgg,$getSumScriptTerms);

		$aggregation = [
		    "work"=> [
		      "terms"=> [
		        "script"=> [ "source" => "doc['start_date'].value.getMonthValue()" ],
		        "order"=> [
		          "_key"=> "asc"
		        ],
		        "min_doc_count"=> 1
		      ],
		      "aggregations"=> $getAllTerms
			],
			"total_hist_agg"=> [
		      "terms"=> [
		        "script"=> "doc['staff_id'].value",
		        "order"=> [
		          "_key"=> "asc"
		        ],
		        "min_doc_count"=> 1
		      ],
		      "aggregations" => $getAllSumTerms
			]
		];


		$items = $this->elasticsearch->search([
            'index' => "month_work",
            'body' => [
                'query' => $query,
                'size' => 0,
                "aggs" => $aggregation
            ]
        ]);
        $aggregationRecords = AH::getAgg($items);   // Get Aggregation Result
        $totalAggregation = isset($aggregationRecords['total_hist_agg']) ? $aggregationRecords['total_hist_agg'] : []; 
        $workAggregation = isset($aggregationRecords['work']) ? $aggregationRecords['work'] : [];
        $mergeRecords = AH::addMissingMonthData($workAggregation); // Add Missing Months
        ksort($mergeRecords);   // Sort by Key

        $work = [];
        foreach($mergeRecords as $aggKey => $aggItem){    // Change Key Values
        	$work[AH::getMonthKey($aggKey)] = $aggItem; 
        }

        $data = $work ? $work : (object)[];

        if($totalAggregation){
        	$data['total'] = current($totalAggregation);
        }
        
    	return $data;
    }

    private function payslip()
    {
        $query = [ 
        			"bool"=> [
					      "must"=> [
					        [
					          "range"=> [
					            "work_start"=> [
					              "lt"=> "2018",
					              "gte"=> "2017",
					              "format"=> "yyyy"
					            ]
					          ]
					        ],
					        [
					          "term"=> [
					            "staff_id"=> 1377
					          ]
					        ]
					      ]
					    ]
					];

		$aggTerms = [	
				"incomeTax" => "taxable_payment" , 
				"socialInsurance" =>  'insurance_payment' , 
				"unemployementInsurance" => 'retroactive_employee_insurance',
				"longTermCareInsurance" => 'retroactive_care_insurance',
				"pension" => 'retroactive_welfare_pension',

			];

		$getTerms = $this->getAggTerms($aggTerms); 
		$getSumAgg = $this->getSumAgg($aggTerms);
		$aggregation = [
		    "date_hist_agg"=> [
		      "terms"=> [
		        "script"=> [ "source" => "doc['work_start'].value.getMonthValue()" ],
		        "order"=> [
		          "_key"=> "asc"
		        ],
		        "min_doc_count"=> 1
		      ],
		      "aggregations"=> $getTerms
			],
			"total_hist_agg"=> [
		      "terms"=> [
		        "script"=> "doc['staff_id'].value",
		        "order"=> [
		          "_key"=> "asc"
		        ],
		        "min_doc_count"=> 1
		      ],
		      "aggregations" => $getSumAgg
			]
		];


        $items = $this->elasticsearch->search([
            'index' => $this->index,
            'body' => [
                'query' => $query,
                'size' => 0,
                "aggs" => $aggregation
            ]
        ]);

        $aggregationRecords = AH::getAgg($items);   // Get Aggregation Result

        $totalAggregation = isset($aggregationRecords['total_hist_agg']) ? $aggregationRecords['total_hist_agg'] : []; 
        $monthAggregation = isset($aggregationRecords['date_hist_agg']) ? $aggregationRecords['date_hist_agg'] : [];

        $mergeRecords = AH::addMissingMonthData($monthAggregation); // Add Missing Months
        ksort($mergeRecords);   // Sort by Key

        $payslip = [];
        foreach($mergeRecords as $aggKey => $aggItem){    // Change Key Values
        	$payslip[AH::getMonthKey($aggKey)] = $aggItem; 
        }

        $data = $payslip ? $payslip : (object)[];

        if($totalAggregation){
        	$data['total'] = current($totalAggregation);
        }
        return $data;
    }

    private function getAggTerms($getTerms){
     	$newTerms = [];
		foreach($getTerms as $k=>$terms){
			$newTerms[$k] = [
		          "terms"=> [
		            "field"=> $terms,
		            "size"=> 1,
		            "order"=> [
		              "_count"=> "desc"
		            ]
		          ]
		        ];
		}
		return $newTerms;
     }

     private function getSumAgg($getTerms){
     	$newTerms = [];
		foreach($getTerms as $k=>$terms){
			$newTerms[$k] = [
					"sum" => [
						"field" => $terms
					]
				];
		}
		return $newTerms;
     }

     private function getScriptHoursTerms($getTerms){
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

     private function getSumScriptHoursTerms($getTerms){
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

     private function getAvgScriptHoursTerms($getTerms){
     	$newTerms = [];
		foreach($getTerms as $k=>$terms){
			$newTerms[$k] = [
					"avg" => [
						"script" => [
							"source" => "doc['$terms'].value / 60"
						]
					]
				];
		}
		return $newTerms;
     } 

     
}
