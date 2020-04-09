<?php

namespace App\Http\Controllers;

use App\Stff;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class StffController extends Controller
{
    private $elasticsearch;
    private $tab = 'stff';

    public function __construct(Client $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function search(){
        $query = [
                    'match_all' => (object)[],
                ];

        $items = $this->elasticsearch->search([
            'index' => 'staff_latest',
            'body' => [
                'query' => $query
            ]
        ]);

        return $items;
    }

    public function search1(){

    	$model = new Stff;
    	$keyword = request()->q;

    	$query = [
                    'multi_match' => [
                        'fields' => ['company_id^5', 'last_name', 'first_name','last_kana_name','first_kana_name','username','nickname','gender'],
                        'query' => $keyword,
                    ],
                ];

        $items = $this->elasticsearch->search([
            'index' => $this->tab,
            'type' => $this->tab,
            'body' => [
                'query' => $query,
            ],
        ]);

        return $items;
    }

    public function searchyear(Request $request){
    	$model = new Stff;

        $gt = $request->year-1;
        $lt = $request->year+1;

        $query = [
                    'bool' => [
                        "must_not"=> [],
                        "filter"=> 
                            [
                             "range" => [
                                    "hire_date"=> [
                                        "gt"=> "{$gt}",
                                        "lt"=> "{$lt}",
                                        "format"=> "yyyy"
                                    ]
                                ]
                            ]
                        
                    ]
                ];


   		$items = $this->elasticsearch->search([
            'index' => $this->tab,
            'type' => $this->tab,
            'body' => [
                'query' => $query,
            ],
        ]);

		return $this->buildCollection($items);
        
    }

    public function searchYearArgs(Request $request){
        $gt = $request->year;
        $lt = $request->year-1;

        $query = [
                    "bool"=> [
				      "must"=> [
				        [
				          "range"=> [
				            "hire_date"=> [
				              "lt"=> "{$gt}",
				              "format"=> "yyyy"
				            ]
				          ]
				        ]
				      ],
				      "must_not"=> [
				        "range"=> [
				          "retirement_date"=> [
				            "lt"=> "{lt}",
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

        $aggs = [
        	"male" => [
		      "filter" => [
		        "term" => [
		          "gender" => 1
		        ]
		      ]
		    ],
		    "female" => [
		      "filter" => [
		        "term" => [
		          "gender" => 2
		        ]
		      ]
		    ],
		    "others" => [
		      "filter" => [
		        "term" => [
		          "gender" => 3
		        ]
		      ]
		    ],
		    "regular" => [
		      "filter" => [
		        "term" => [
		          "employement_type" => 1
		        ]
		      ]
		    ],
		    "irregular" => [
		      "filter" => [
		        "term" => [
		          "employement_type" => 2
		        ]
		      ]
		    ]
        ];


        $items = $this->elasticsearch->search([
            'index' => $this->tab,
            'type' => $this->tab,
            'body' => [
                'query' => $query,
		        'size' => 10,
		        'aggs' => $aggs
            ],
        ]);

		return $items;
        
    }

    private function buildCollection(array $items): Collection
    {
        $ids = Arr::pluck($items['hits']['hits'], '_id');
        return Stff::whereIn('id',$ids)->get()
            ->sortBy(function ($article) use ($ids) {
                return array_search($article->getKey(), $ids);
            });
    }
}
