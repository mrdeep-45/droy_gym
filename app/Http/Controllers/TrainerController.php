<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrainerModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class TrainerController extends Controller
{
    //
    public function index()
    {
        $page_title = 'Trainer';
        $page_name = 'Trainer';

        return view(
            'company.master.trainer',
            compact('page_title', 'page_name')
        );
    }

    public function storeOrUpdate(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'trainer_name' => 'required',
                'trainer_phone' => 'required',
                'specialization' => 'required'
            ]);

            $trainer_id = $request->trainer_id;

            $exists = TrainerModel::where(
                    'trainer_phone',
                    $request->trainer_phone
                )
                ->where('status',0)
                ->when($trainer_id,function($q) use($trainer_id){
                    $q->where('trainer_id','!=',$trainer_id);
                })
                ->exists();

            if($exists){
                return response()->json([
                    'errors'=>[
                        'trainer_phone'=>[
                            'Trainer phone already exists.'
                        ]
                    ]
                ],422);
            }

            $photoName = null;

            if($request->hasFile('t_photo'))
            {
                $file = $request->file('t_photo');

                $photoName = time().'_'.$file->getClientOriginalName();

                $file->move(
                    public_path('uploads/trainers'),
                    $photoName
                );
            }

            if($trainer_id)
            {
                $trainer = TrainerModel::find($trainer_id);

                $data = [
                    'trainer_name' => $request->trainer_name,
                    'trainer_phone' => $request->trainer_phone,
                    'specialization' => $request->specialization,
                    'updated_by' => getUpdatedBy(),
                    'updated_at' => now()
                ];

                if($photoName){
                    $data['t_photo'] = $photoName;
                }

                $trainer->update($data);

                $message = 'Trainer updated successfully.';
            }
            else
            {
                TrainerModel::create([
                    'trainer_name' => $request->trainer_name,
                    'trainer_phone' => $request->trainer_phone,
                    'specialization' => $request->specialization,
                    't_photo' => $photoName,
                    'status' => 0,
                    'created_by' => getCreatedBy(),
                    'created_at' => now()
                ]);

                $message = 'Trainer added successfully.';
            }

            DB::commit();

            return response()->json([
                'status'=>'success',
                'message'=>$message
            ]);

        }
        catch (\Exception $e)
        {
            DB::rollBack();

            return response()->json([
                'status'=>'error',
                'message'=>$e->getMessage()
            ],500);
        }
    }

    public function getList()
    {
        $query = TrainerModel::select([
            'trainer_id',
            'trainer_name',
            'trainer_phone',
            'specialization',
            't_photo',
            'created_at'
        ])
        ->where('status',0)
        ->orderBy('trainer_id','desc');

        return DataTables::of($query)

            ->addColumn('photo',function($row){

                if($row->t_photo){
                    return '<img src="'.asset('uploads/trainers/'.$row->t_photo).'"
                                width="50">';
                }

                return '-';
            })

            ->addColumn('action',function($row){

                return '
                    <button
                        class="btn btn-primary btn-sm edit-trainer"
                        data-id="'.$row->trainer_id.'">
                        Edit
                    </button>

                    <button
                        class="btn btn-danger btn-sm delete-trainer"
                        data-id="'.$row->trainer_id.'">
                        Delete
                    </button>
                ';
            })

            ->rawColumns(['photo','action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function edit($id)
    {
        $trainer = TrainerModel::find($id);

        return response()->json([
            'status'=>'success',
            'data'=>$trainer
        ]);
    }

    public function destroy(Request $request)
    {
        $trainer = TrainerModel::find($request->trainer_id);

        $trainer->update([
            'status'=>1,
            'updated_by'=>getUpdatedBy(),
            'updated_at'=>now()
        ]);

        return response()->json([
            'status'=>'success',
            'message'=>'Trainer deleted successfully.'
        ]);
    }
}
