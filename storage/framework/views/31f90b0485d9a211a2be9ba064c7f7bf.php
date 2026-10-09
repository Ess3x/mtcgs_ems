<script>
    document.addEventListener('DOMContentLoaded', function () {
        const firstNameInput = document.querySelector('input[name="first_name"]');
        const dateOfBirthInput = document.querySelector('input[name="date_of_birth"]');
        const genderSelect = document.querySelector('select[name="gender"]');
        const ageDisplay = document.getElementById('age-display');
        const genderSuggestion = document.getElementById('gender-suggestion');

        if (dateOfBirthInput && ageDisplay) {
            const updateAge = function () {
                if (!dateOfBirthInput.value) {
                    ageDisplay.textContent = 'Enter a date of birth to calculate age.';
                    return;
                }

                const parts = dateOfBirthInput.value.split('-').map(Number);
                if (parts.length !== 3 || parts.some(Number.isNaN)) {
                    ageDisplay.textContent = 'Enter a valid date of birth.';
                    return;
                }

                const [year, month, day] = parts;
                const birthDate = new Date(year, month - 1, day);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (birthDate.getFullYear() !== year || birthDate.getMonth() !== month - 1 || birthDate.getDate() !== day) {
                    ageDisplay.textContent = 'Enter a valid date of birth.';
                    return;
                }
                if (birthDate > today) {
                    ageDisplay.textContent = 'Date of birth cannot be in the future.';
                    return;
                }

                let age = today.getFullYear() - year;
                if (today.getMonth() < month - 1 || (today.getMonth() === month - 1 && today.getDate() < day)) {
                    age--;
                }
                ageDisplay.textContent = 'Age: ' + age + (age === 1 ? ' year' : ' years');
            };

            dateOfBirthInput.addEventListener('input', updateAge);
            dateOfBirthInput.addEventListener('change', updateAge);
            updateAge();
        }

        if (!firstNameInput || !genderSelect || !genderSuggestion) {
            return;
        }

        const genderByGivenName = {};
        const addNames = function (names, gender) {
            names.split(',').forEach(function (name) {
                genderByGivenName[name.trim()] = gender;
            });
        };
        addNames('alejandro,andres,antonio,ben,benjamin,brian,bryan,carlo,carlos,christian,christopher,daniel,david,dominic,edward,emmanuel,eric,ernesto,fernando,francis,francisco,gabriel,gerald,henry,ismael,jaime,james,jerome,jesus,joaquin,john,jose,joseph,juan,julius,kenneth,kevin,leo,lorenzo,luis,manuel,marco,mark,mario,matthew,michael,miguel,nathaniel,noel,oscar,patrick,paolo,paul,peter,ramon,renato,ricardo,roberto,romeo,ryan,salvador,samuel,santiago,steven,tomas,vincent,william', 'Male');
        addNames('ana,andrea,angelica,angela,anne,ann,arlene,ashley,beatriz,bernadette,bella,camille,carla,cecilia,clarissa,corazon,cristina,christine,daisy,danica,daniela,diana,elaine,elizabeth,emma,erika,evelyn,faith,frances,gemma,gloria,grace,hannah,hazel,helen,irene,isabella,jasmine,jennifer,jessica,joanna,joyce,julia,karen,kathryn,katrina,kristine,leah,liza,lourdes,lucille,maria,maricel,mariel,marife,marilyn,marites,maureen,melissa,michelle,monica,nicole,patricia,princess,rachel,rowena,samantha,sarah,sheryl,sofia,sophia,stephanie,susan,teresa,theresa,veronica,victoria,vivian,zenaida', 'Female');

        let userChangedGender = genderSelect.value !== '';
        let lastSuggestedGender = '';

        genderSelect.addEventListener('change', function () {
            userChangedGender = true;
        });

        const updateGenderSuggestion = function () {
            const givenName = firstNameInput.value
                .trim()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .split(/\s+/)[0]
                .replace(/[^a-z-]/g, '');
            const suggestion = genderByGivenName[givenName] || '';

            if (!suggestion) {
                genderSuggestion.textContent = 'No reliable suggestion for this given name; please choose gender manually.';
                if (!userChangedGender && genderSelect.value === lastSuggestedGender) {
                    genderSelect.value = '';
                }
                lastSuggestedGender = '';
                return;
            }

            genderSuggestion.textContent = 'Suggestion: ' + suggestion + ' based on the given name. Please confirm or change it.';
            if (!userChangedGender && (!genderSelect.value || genderSelect.value === lastSuggestedGender)) {
                genderSelect.value = suggestion;
                lastSuggestedGender = suggestion;
            }
        };

        firstNameInput.addEventListener('input', updateGenderSuggestion);
        updateGenderSuggestion();
    });
</script>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\partials\employee-demographics-script.blade.php ENDPATH**/ ?>