<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ContactService;
use Validator;
class EntityCreateController extends Controller
{
    
    /**
     * Display the form for creating a new entity.
     *
     * @return \Illuminate\View\View
     */
    public function renderCreateForm() 
    {
        return view('entities.create', ['date' => date("Y-m-d")]);
    }

    /**
     * Entity creating with the given params
     *
     * @param Request $request  mixed The result of creating the contact entity or a JSON response in case of validation errors.
     * 
     * @return mixed The result of creating the contact entity or a JSON response in case of validation errors.
     */
    public function createContactIntity(Request $request)
    {
        $inputData = $request->json()->all();

        $validator = Validator::make($inputData, [
            'first_name'  => 'required|string|max:155',
            'second_name' => 'required|string|max:255',
            'phone'       => 'required|string',
            'email'       => ['required', 'email', 'max:255'],
            'age'         => 'required|numeric',
            'gender'      => 'required'  
        ]);
    
        if ($validator->fails()) {
			return response()->json(['status'=>'error','message'=>$validator->messages()], 422);
        }

        $contactService = new ContactService();
        $newContactCreated = $contactService->createNewContactEntity($inputData);

        if ($newContactCreated) {
            return $newContactCreated;
        }

        return response()->json(['message' => 'Something went wrong.']);

    }
}
