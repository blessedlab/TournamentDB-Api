document.getElementById('tournamentForm').addEventListener('submit', function(e) {
    e.preventDefault();

    function showError(message, input) {
        formMessage.textContent = message;
        formMessage.className = 'form-message error';
        formMessage.style.display = 'block';
        if (input) {
            input.parentNode.classList.add('form-group-error');
            
            if(input.nextElementSibling) {
                input.nextElementSibling.textContent = message;
            }
        }
    }

    const emailRegex = /.+@tm1\.edu\.pl$/;
    var has_error = false;
    const inputIds = [
        'p1Email',
        'p2Email',
        'p1Nickname',
        'p2Nickname',
        'teamName'
    ];


    inputIds.forEach(function(id) {

        const input = document.getElementById(id);

        input.parentNode.classList.remove('form-group-error');
        
        if(input.nextElementSibling) {
            input.nextElementSibling.textContent = '';
        }

        if (input.value.trim() === '') {
            showError('ERROR: This field cannot be empty.', input);
            has_error = true;
        }
    });


    if (!has_error) {
        [
            'p1Email',
            'p2Email',
        ].forEach(function(id) {
            if (!emailRegex.test(document.getElementById(id).value.trim())) {
                showError('ERROR: Only @tm1.edu.pl emails are allowed.', document.getElementById(id));
                has_error = true;
            }
        });
    }

    if (!has_error) {
        let duplicateCheck = [];
        duplicateCheck['p1Nickname'] = 'p2Nickname';
        duplicateCheck['p1Email'] = 'p2Email';
        //console.log(duplicateCheck, typeof duplicateCheck);
        Object.keys(duplicateCheck).forEach(function(key) {
            field1 = document.getElementById(key).value.trim().toLowerCase();
            field2 = document.getElementById(duplicateCheck[key]).value.trim().toLowerCase();
            if (field1 === field2) {
                showError('ERROR: Players must use different email addresses.', document.getElementById(duplicateCheck[key]));
                has_error = true;
            }
        });
    }

    formMessage.className = 'form-message';
    formMessage.style.display = 'none';
    if (has_error) {
        showError('ERROR: Please fill in all fields.');
        return;
    }


    const payload = {
        team_name: document.getElementById('teamName').value.trim(),
        participants: [
            { nickname: document.getElementById('p1Nickname').value.trim(), email: document.getElementById('p1Email').value.trim() },
            { nickname: document.getElementById('p2Nickname').value.trim(), email: document.getElementById('p2Email').value.trim() }
        ]
    };

    fetch('TournamentDB-Api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        //console.log(data);
        if (data.error) {
            let errorMsg = data.message;
            let fieldsToHighlight = [];

            formMessage.style.display = 'none';

            let takenMail = null;
            if (errorMsg.includes("@tm1.edu.pl")) {
                takenMail = errorMsg.trim().toLowerCase();
                console.log("Extracted email: " + takenMail);

                let p1EmailInput = document.getElementById('p1Email');
                let p2EmailInput = document.getElementById('p2Email');


                if (takenMail === p1EmailInput.value.trim().toLowerCase()) {

                    showError('ERROR: This email is already registered.', p1EmailInput);

                } else if (takenMail === p2EmailInput.value.trim().toLowerCase()) {

                    showError('ERROR: This email is already registered.', p2EmailInput);
                } else {
                    showError('ERROR: ' + errorMsg);
                }
                //console.log("Taken email: " + takenMail);
            }

        } else { 
            if (data.message === "Participants and team added successfully") {
            formMessage.textContent = 'TEAM REGISTERED SUCCESSFULLY!';
            formMessage.className = 'form-message success';
            formMessage.style.display = 'block';
            document.getElementById('tournamentForm').reset();
            }
        }
        
    })
    .catch(error => {
        showError('CONNECTION ERROR: Could not reach the API.');
    });
});