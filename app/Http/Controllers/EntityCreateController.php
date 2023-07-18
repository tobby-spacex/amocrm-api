<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ContactService;

class EntityCreateController extends Controller
{
    
    /**
     * Display the form for creating a new entity.
     *
     * @return \Illuminate\View\View
     */
    public function create() {
        return view('entities.create', ['date' => date("Y-m-d")]);
    }

    /**
     * Entity creating with the given params
     *
     * @param AmoCRM\Client\AmoCRMApiClient $apiClient  The AmoCRM API client.
     * @param Request $request
     * @return
     */
    public function store(Request $request)
    {
        $validatedFormData = $request->validate([
            'first_name'  => 'required|string|max:155',
            'second_name' => 'required|string|max:255',
            'phone'       => 'required|string',
            'email'       => ['required', 'email', 'max:255'],
            'age'         => 'required|numeric',
            'gender'      => 'required'  
        ]);

        $contactService = new ContactService();
        $newCustomerCreated = $contactService->creatNewContactEntity($validatedFormData);

        if ($newCustomerCreated) {
            return $newCustomerCreated;
        }

        return response()->json(['message' => 'Something went wrong.']);

    }
}
