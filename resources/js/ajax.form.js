$('#formId').on('submit', function(event) {
    event.preventDefault();

    let first_name  = $('#first_name').val();
    let second_name = $('#second_name').val();
    let phone  = $('#phone').val();
    let email = $('#email').val();
    let age  = $('#age').val();
    let gender = $('input[name="gender"]:checked').val();

    
    let requestData = {
    first_name : first_name,
    second_name: second_name,
    phone      : phone,
    email      : email,
    age        : age,
    gender     : gender
    };

    $.ajax({
    url: "/entity",
    type: "POST",
    headers: {
        "Content-Type": "application/json",
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    data: JSON.stringify(requestData),
        success: function(response) {
        alert(response.message);
        location.reload();
        },
        error: function(xhr, status, error) {
        // Handle error response
        if (xhr.status === 422) {
            var responseErrors = xhr.responseJSON.errors;
            if (responseErrors) {
                Object.entries(responseErrors).forEach(function([fieldName, fieldErrors]) {
                    if(fieldName == 'first_name') {
                        let greeting = document.querySelector('#first_name_error');
                        greeting.innerHTML = fieldErrors
                    }
                    if(fieldName == 'second_name') {
                        let greeting = document.querySelector('#second_name_error');
                        greeting.innerHTML = fieldErrors
                    }
                    if(fieldName == 'phone') {
                        let greeting = document.querySelector('#phone_error');
                        greeting.innerHTML = fieldErrors
                    }
                    if(fieldName == 'email') {
                        let greeting = document.querySelector('#email_error');
                        greeting.innerHTML = fieldErrors
                    }
                    if(fieldName == 'age') {
                        let greeting = document.querySelector('#age_error');
                        greeting.innerHTML = fieldErrors
                    }
                });
            }
        } else {
            alert('An error occurred: ' + xhr.status + ' ' + error);
        }
    }
    });
});
  