document.addEventListener('DOMContentLoaded', function() {
    // Handle country change
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');

    countrySelect.addEventListener('change', function() {
        const countryId = this.value;
        console.log('Country changed:', countryId);
        
        // Clear state and city selections
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';

        // If country is selected, fetch states
        if (countryId) {
            console.log('Fetching states for country:', countryId);
            fetch(`/api/states/${countryId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    console.log('API response status:', response.status);
                    return response.json();
                })
                .then(states => {
                    console.log('Received states:', states);
                    states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.id;
                        option.textContent = state.name;
                        stateSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching states:', error);
                });
        }
    });

    // Handle state change
    stateSelect.addEventListener('change', function() {
        const stateId = this.value;
        citySelect.innerHTML = '<option value="">Select City</option>';

        // If state is selected, fetch cities
        if (stateId) {
            fetch(`/api/cities/${stateId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(cities => {
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        citySelect.appendChild(option);
                    });
                });
        }
    });
});
