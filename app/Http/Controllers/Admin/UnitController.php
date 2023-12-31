<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\Translation;
use Rap2hpoutre\FastExcel\FastExcel;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $units = Unit::paginate(config('default_pagination'));
        return view('admin-views.unit.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'unit'=>'required|unique:units,unit',
            'unit.0' => 'required',
        ],[
            'unit.0.required'=>translate('default_unit_is_required'),
        ]);

        $unit = new Unit;
        $unit->unit = $request->unit[array_search('default', $request->lang)];
        $unit->save();

        $data = [];
        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if($default_lang == $key && !($request->unit[$index])){
                if ($key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\Unit',
                        'translationable_id' => $unit->id,
                        'locale' => $key,
                        'key' => 'unit',
                        'value' => $unit->unit,
                    ));
                }
            }else{
                if ($request->unit[$index] && $key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\Unit',
                        'translationable_id' => $unit->id,
                        'locale' => $key,
                        'key' => 'unit',
                        'value' => $request->unit[$index],
                    ));
                }
            }
        }

        Translation::insert($data);

        Toastr::success(translate('messages.unit_added_successfully'));
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $unit = Unit::withoutGlobalScope('translate')->findOrFail($id);

        return view('admin-views.unit.edit', compact('unit'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'unit'=>'required|unique:units,unit,'.$id,
            'unit.0' => 'required',
        ],[
            'unit.0.required'=>translate('default_unit_is_required'),
        ]);
        $unit = Unit::findOrFail($id);
        $unit->unit = $request->unit[array_search('default', $request->lang)];
        $unit->save();

        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if($default_lang == $key && !($request->unit[$index])){
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\Unit',
                            'translationable_id' => $unit->id,
                            'locale' => $key,
                            'key' => 'unit'
                        ],
                        ['value' => $unit->unit]
                    );
                }
            }else{

                if ($request->unit[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\Unit',
                            'translationable_id' => $unit->id,
                            'locale' => $key,
                            'key' => 'unit'
                        ],
                        ['value' => $request->unit[$index]]
                    );
                }
            }
        }

        Toastr::success(translate('messages.unit_updated_successfully'));
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();

        Toastr::success(translate('messages.unit_deleted_successfully'));
        return back();
    }

    public function search(Request $request){
        $key = explode(' ', $request['search']);
        $units=Unit::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('unit', 'like', "%{$value}%");
            }
        })->limit(50)->get();
        return response()->json([
            'view'=>view('admin-views.unit.partials._table',compact('units'))->render()
        ]);
    }

    public function export($type){
        $collection = Unit::all();

        if($type == 'excel'){
            return (new FastExcel(Helpers::export_units($collection)))->download('Units.xlsx');
        }elseif($type == 'csv'){
            return (new FastExcel(Helpers::export_units($collection)))->download('Units.csv');
        }
    }
}
