var datepickerOptions = {
    dateFormat: 'yy-mm-dd',
    changeYear: true,
    changeMonth: true,
    showButtonPanel: true,
};

$(function () {

});

$(function () {
    var monthYearOptions = Object.assign({}, datepickerOptions, {
        dateFormat: 'yy-mm',
        onClose: function (dateText, inst) {
            function isDonePressed() {
                return $('#ui-datepicker-div').html().indexOf('ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all ui-state-hover') > -1;
            }

            if (isDonePressed()) {
                var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
                var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
                $(this).datepicker('setDate', new Date(year, month, 1)).trigger('change');

                $('#mthYr').focusout();
            }
        },
        beforeShow: function (input, inst) {
            inst.dpDiv.addClass('month_year_datepicker');

            if ((datestr = $(this).val()).length > 0) {
                var year = datestr.substring(0, 4);
                var month = datestr.substring(datestr.length - 2, datestr.length);
                $(this).datepicker('option', 'defaultDate', new Date(year, month - 1, 1));
                $(this).datepicker('setDate', new Date(year, month - 1, 1));
            }
        }
    });

    $('#month_year').datepicker(monthYearOptions);
});

//# sourceMappingURL=datepicker-utils.js.map
