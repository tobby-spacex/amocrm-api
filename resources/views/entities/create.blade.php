<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite('resources/css/app.css')
</head>
<body>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <div  class="grid place-items-center h-screen">
                <div class="min-h-screen p-6 bg-gray-100 flex items-center justify-center">

                    <div class="absolute top-0 right-0 mt-5 mr-7"><a href="{{ route('auth.user') }}" class="bg-blue-400 text-white py-2 px-3 rounded">Authorization</a></div>
                    <div class="container max-w-screen-lg mx-auto">
                    <div>
                        <h2 class=" flex font-semibold text-xl"><p class="text-sky-600 mr-1">amoCRM </p> - create an entity </h2>
                        <p class="text-gray-500 mb-6">All data will be sent to AmoCrm account. Give it a try.</p>
                
                        <div class="bg-white rounded shadow-lg p-4 px-4 md:p-8 mb-6">
                        <div class="grid gap-4 gap-y-2 text-sm grid-cols-1 lg:grid-cols-3">
                            <div class="text-gray-600">
                            <p class="font-medium text-lg">Entity Details</p>
                            <p>Please fill out all the fields.</p>
                            </div>
                
                            <div class="lg:col-span-2">
                             <form method="post" action="{{ route('entity.store') }}" id="formId"  enctype="multipart/form-data">
                                @csrf
                                <div class="grid gap-4 gap-y-2 text-sm grid-cols-1 md:grid-cols-5">
                                <div class="md:col-span-2">
                                    <label for="first_name">First Name</label>
                                    <input type="text" name="first_name" id="first_name" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" value=""/>
                                    
                                    @error('first_name')
                                    <p class="text-red-600 text-xs">{{$message}}</p>
                                    @enderror
                                </div>
                    
                                <div class="md:col-span-3">
                                    <label for="second_name">Second Name</label>
                                    <input type="text" name="second_name" id="second_name" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" value=""/>
                                    @error('second_name')
                                    <p class="text-red-600 text-xs">{{$message}}</p>
                                    @enderror    
                                </div>
                    
                                <div class="md:col-span-2">
                                    <label for="phone">Phone</label>
                                    <input type="text" name="phone" id="phone" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" value="" placeholder="+777 852 58 96"/>
                                    @error('phone')
                                    <p class="text-red-600 text-xs">{{$message}}</p>
                                    @enderror       
                                </div>
                    
                                <div class="md:col-span-2">
                                    <label for="city">Email</label>
                                    <input type="email" name="email" id="email" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" value="" placeholder="email@domain.com"/>
                                    @error('email')
                                    <p class="text-red-600 text-xs">{{$message}}</p>
                                    @enderror    
                                </div>

                                <div class="md:col-span-1">
                                    <label for="age">Age</label>
                                    <input type="number" name="age" id="age" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" value=""/>
                                    @error('age')
                                    <p class="text-red-600 text-xs">{{$message}}</p>
                                    @enderror 
                                </div>
                    
                                <div class="md:col-span-5">
                                    <label for="address">Gender</label>
                                    <div class="flex">
                                        <div class="flex items-center mr-4">
                                            <input checked id="inline-radio" type="radio" value="" name="inline-radio-group" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="inline-radio" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Male</label>
                                        </div>
                                        <div class="flex items-center mr-4">
                                            <input id="inline-2-radio" type="radio" value="" name="inline-radio-group" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="inline-2-radio" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Female</label>
                                        </div>
                                        <div class="flex items-center mr-4">
                                            <input id="inline-checked-radio" type="radio" value="" name="inline-radio-group" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="inline-checked-radio" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Something</label>
                                        </div>
                                    </div>
                                </div>
                            
                                <div class="md:col-span-5 text-right">
                                    <div class="inline-flex items-end">
                                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Submit</button>
                                    </div>
                                    </div>
                                </div>
                            </form>
                            </div>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</body>
</html>