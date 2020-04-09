<?php

namespace App\Http\Controllers;

use App\Staff;
use App\Staffs\StaffsRepository;
use Elasticsearch\Client;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use AH;
use SH;

class StaffController extends Controller
{
	private $elasticsearch;
    private $index = "staff_latest";

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;

    }

    public function staff()
    {
        $filter = SH::getFilter();
        $query = [ "bool"=> ( count($filter) > 0 ) ? $filter : ([ "must" => [ "match_all" => (object)[] ] ]) ];  

        $items = $this->elasticsearch->search([
            'index' => $this->index,
            'body' => [
                'query' => $query,
                'sort'  => [
                    SH::getSortBy()
                ],
                'from'=> AH::getPageFrom(),
                'size' => AH::getPageSize()
            ]
        ]);

        $totalRecords = AH::getResultCount($items);

        $items = AH::getSource($items);

        $collection = new Collection();
        foreach($items as $item){
            $collection->push(collect($item));
        }
        $data['current'] = AH::getPage();
        $data['previous'] = AH::getPreviousPage($totalRecords);
        $data['next'] = AH::getNextPage($totalRecords);
        $data['last'] = AH::getLastPage($totalRecords);

        $data['staff'] = $collection;
        return response()->success('Success',$data);
    }

    public function staffYear(Request $request){
        // $rules = [
        //     'year' => 'required|integer',
        // ];

        // $this->validate($request,$rules);

        $gender = [
            'male' => 10,
            'female' => 10,
            'others' => 5,
            'regular' => 13,
            'irregular' => 12
        ];
        $age = [
            'male' => 40,
            'female' => 30,
            'others' => 25,
            'regular' => 40,
            'irregular' => 30
        ]; 
        $labour = [
            'totalProceeds'=>10000,
            'incomeTax'=>4000,
            'localTax'=>1000,
            'socialInsurance'=>1000,
            'unemployementInsurance'=>1000,
            'longTermCareInsurance'=>1000,
            'pension'=>2000
        ];
        $company = [
            'socialInsurance' => 5000,
            'accidentInsurance' => 5000
        ];
        $salary = [
            'totalSalary' => 20000,
            'totalDeduction' => 10000,
            'totalProceeds' => 10000
        ];  

        $res = [
            'ratio' => $gender,
            'averageAge' => $age,
            'labourCosts' => $labour,
            'companyShare' => $company, 
            'salary' => $salary
        ];

        $ratio[2018] = $res;
        return response()->success('Success',$ratio);
    }

    public function staffDetail(Request $request){
        $working = [
            'workingDays' => 20,
            'paidHolidays'=> 2,
            'holiday'=> 2,
            'absence'=> 2,
            'legalWorkingHours'=> 200,
            'actualWorkingHours'=> 250,
            'workingHours'=> 200,
            'averageWorkingHours'=> 10,
            'overtime'=> 5
        ];

        $workingMonth['january'] = $working;
        $workingMonth['february'] = $working;
        $workingMonth['march'] = $working;
        $workingMonth['april'] = $working;
        $workingMonth['may'] = $working;
        $workingMonth['june'] = $working;
        $workingMonth['july'] = $working;
        $workingMonth['august'] = $working;
        $workingMonth['september'] = $working;
        $workingMonth['october'] = $working;
        $workingMonth['november'] = $working;
        $workingMonth['december'] = $working;

        $salary = [
            'basicSalary' => 20000,
            'overtimeLaborCosts'=> 5000,
            'totalAllowance'=> 2000,
            'totalDeduction'=> 2000,
            'totalProceeds'=> 2000,
            'incomeTax'=> 2500,
            'localTax'=> 2000,
            'socialInsurance'=> 1000,
            'unemployementInsurance'=> 5000,
            'longTermCareInsurance' => 5000,
            'pension' => 2500
        ];

        $salaryMonth['january'] = $salary;
        $salaryMonth['february'] = $salary;
        $salaryMonth['march'] = $salary;
        $salaryMonth['april'] = $salary;
        $salaryMonth['may'] = $salary;
        $salaryMonth['june'] = $salary;
        $salaryMonth['july'] = $salary;
        $salaryMonth['august'] = $salary;
        $salaryMonth['september'] = $salary;
        $salaryMonth['october'] = $salary;
        $salaryMonth['november'] = $salary;
        $salaryMonth['december'] = $salary;

        $staff = [
            "firstName" => "利***",
            "id" => 84,
            "gender" => 1,
            "employementType" => 1,
            "birthDate" => null,
            "tel2" => null,
            "middleName" => null,
            "companyId" => 15,
            "createdTime" => "2016-02-05T05:13:16.000Z",
            "retirementDate" => null,
            "lastName" => "能***",
            "hireDate" => null,
            "updatedTime" => "2016-03-18T04:41:58.000Z",
            "telCountry" => null,
            "email" => null,
            "username" => "7120801371",
            "employeeCode" => null,
            "tel1" => null
          ];

        $workCollection = new Collection();
        $salaryCollection = new Collection(); 
        $res = [
            'staff' => $staff,
            'work' => $workCollection->push($workingMonth),
            'salary' => $salaryCollection->push($salaryMonth)
        ];

        return response()->success('Success',$res);
    }




    public function staffRatio1(){
        $query = [
                    'bool' => 
                        [
                            "must" => [
                                "range"=> [
                                    "hire_date"=> [
                                      "lt"=> "2018",
                                      "format"=> "yyyy"
                                    ]
                                  ]
                            ],
                            "must_not"=> [
                                "range"=> [
                                  "retirement_date"=> [
                                    "lt"=> "2017",
                                    "format"=> "yyyy"
                                  ]
                                ]
                            ],
                            "should"=> [
                                [
                                  "exists"=> [
                                    "field"=> "hire_date"
                                  ]
                                ]
                            ]
                        ]
                ];

        $aggregation = [
                "male"=> [
                  "filter"=> [
                    "term"=> [
                      "gender"=> 1
                    ]
                  ]
                ],
                "female"=> [
                  "filter"=> [
                    "term"=> [
                      "gender"=> 2
                    ]
                  ]
                ],
                "others"=> [
                  "filter"=> [
                    "term"=> [
                      "gender"=> 3
                    ]
                  ]
                ],
                "regular"=> [
                  "filter"=> [
                    "term"=> [
                      "employement_type"=> 1
                    ]
                  ]
                ],
                "irregular"=> [
                  "filter"=> [
                    "term"=> [
                      "employement_type"=> 2
                    ]
                  ]
                ]
        ];

        $items = $this->elasticsearch->search([
            'index' => $this->index,
            'body' => [
                'query' => $query,
                'size' => 0,
                'aggs' => $aggregation
            ]
        ]);

        $items = AH::getAggregation($items);
        return response()->success('Success',$items);
    }
}