<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\ComponentController;
use App\Models\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\WordpressComponentController;
use Illuminate\Support\Facades\Http;

class CustomizeComponentController extends Controller
{
    public function fetchComponent()
    {
        $response = [
            "success" => false,
            "status"  => 400,
        ];
        $type = request()->input('type');
        if($type){
            $componentData = Component::where('type',$type)->where('status','active')->get();
            $componentDetail = [];
            foreach($componentData as $data){
               $component = [];
               $component['id'] = $data->id;
               $component['component_unique_id'] = $data->component_unique_id;
               $component['preview'] = '/storage/'.$data->preview;
               $component['type'] = $data->type;
               $component['category'] = $data->category;
               $componentDetail[] = $component;
            }
        }else{
             $componentData = Component::where('status','active')->get();
             $componentDetail = [];
             foreach($componentData as $data){
                $component = [];
                $component['id'] = $data->id;
                $component['component_unique_id'] = $data->component_unique_id;
                $component['preview'] = '/storage/'.$data->preview;
                $component['type'] = $data->type;
                $component['category'] = $data->category;
                $componentDetail[] = $component;
             }
        }
        if($componentDetail){
            $response = [
                "message" => "Result Fetched Successfully.",
                'component' => $componentDetail,
                "success" => true,
                "status"  => 200,
            ];
        }

        return $response;
    }
    public function updateComponent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'website_url' => 'required|url',
            'component_unique_id_old' => 'required',
            'component_unique_id_new' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $validated = $validator->valid();
        $website_url = $validated['website_url'];
        $oldComponentUniqueId['component_unique_id'] = $validated['component_unique_id_old'];
        $newComponentUniqueId = $validated['component_unique_id_new'];
        $deleteComponentResponse = WordpressComponentController::deleteComponent($website_url,$oldComponentUniqueId);
        if($deleteComponentResponse['success'] == true && $deleteComponentResponse['response']['status'] == 200 ){
            $componentPosition = $deleteComponentResponse['response']['data']['position'];
            $componentData = Component::where('component_unique_id',$newComponentUniqueId)->where('status','active')->first();
            $componentDependencies = $componentData->dependencies;
            $component = [
                'component_detail' => [
                    'component_name' => $componentData->component_name,
                    'path' => $componentData->path,
                    'type' => $componentData->type,
                    'position' => $componentPosition,
                    'component_unique_id' => $componentData->component_unique_id,
                    'status' =>  $componentData->status,
                ],
                'component_dependencies' => $componentDependencies ,
            ];
            $addComponentUrl = $website_url . 'wp-json/v1/component';
            $componentResponse = Http::post($addComponentUrl, $component);
            if ($componentResponse->successful()) {
                $response['response'] = $componentResponse->json();
                $response['status'] = $componentResponse->status();
            } else {
                $response['status'] = $componentResponse->status();
                $response['response']  = $componentResponse->json();
                $response['success'] = false;
            }
        }


        return $response;

    }
}
