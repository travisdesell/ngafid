$(function () {
    // Provide auto-complete feature to Airports text box
    $('#airport').autocomplete({
        source: '/airports/autocomplete',
        minLength: 3,
        select: function (event, ui) {
            var idx = ui.item.id;
            var CSRF_TOKEN = $('input[name="_token"]').val();
            $.ajax({
                type: 'GET',
                url: '/runways/autocomplete',
                data: {code: idx, _token: CSRF_TOKEN}
            }).done(function (data, textStatus, jqXHR) {
                var items = Object.keys(data).map(function (key) {
                    return '<option value="' + key + '">' + data[key] + '</option>';
                }).join('');

                $('#runway').html(items);
            });
        }
    });
});

//# sourceMappingURL=airport-runway-autocomplete.js.map
