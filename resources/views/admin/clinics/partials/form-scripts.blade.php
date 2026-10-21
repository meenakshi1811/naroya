<script>
    (function () {
        // Image uploaders: live preview, plus a remove flag the controller reads on update.
        document.querySelectorAll('[data-cm-uploader]').forEach(function (uploader) {
            var input = uploader.querySelector('input[type="file"]');
            var preview = uploader.querySelector('.cm-uploader-preview');
            var image = preview.querySelector('img');
            var fallback = preview.querySelector('.cm-uploader-fallback');
            var removeButton = uploader.querySelector('[data-cm-remove]');
            var removeFlag = uploader.querySelector('[data-cm-remove-flag]');

            function showImage(src) {
                image.src = src;
                image.hidden = false;
                fallback.hidden = true;
                preview.classList.add('has-image');
                removeButton.hidden = false;
            }

            function clearImage() {
                image.removeAttribute('src');
                image.hidden = true;
                fallback.hidden = false;
                preview.classList.remove('has-image');
                removeButton.hidden = true;
            }

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file) {
                    return;
                }

                removeFlag.value = '0';
                var reader = new FileReader();
                reader.onload = function (event) {
                    showImage(event.target.result);
                };
                reader.readAsDataURL(file);
            });

            removeButton.addEventListener('click', function () {
                input.value = '';
                removeFlag.value = '1';
                clearImage();
            });
        });

        // State -> city dropdown, using the same public endpoint as the apps.
        var stateSelect = document.getElementById('state');
        var citySelect = document.getElementById('city_id');
        var cityUrl = @json(url('api/city-list'));

        if (!stateSelect || !citySelect) {
            return;
        }

        function resetCities(label, disabled) {
            citySelect.innerHTML = '';
            citySelect.appendChild(new Option(label, ''));
            citySelect.disabled = disabled;
        }

        stateSelect.addEventListener('change', function () {
            var stateId = stateSelect.value;

            if (!stateId) {
                resetCities('Select a state first', true);
                return;
            }

            resetCities('Loading cities…', true);

            fetch(cityUrl + '?state_id=' + encodeURIComponent(stateId), { headers: { 'Accept': 'application/json' } })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Request failed');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    var cities = (payload.data && payload.data.cities) || [];
                    resetCities(cities.length ? 'Select city' : 'No cities listed for this state', cities.length === 0);
                    cities.forEach(function (city) {
                        citySelect.appendChild(new Option(city.name, city.id));
                    });
                })
                .catch(function () {
                    resetCities('Could not load cities. Try again.', true);
                });
        });
    })();
</script>
