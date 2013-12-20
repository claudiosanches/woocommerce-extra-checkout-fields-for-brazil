jQuery(document).ready(function($) {
    function person_type_switch() {
        var target = $(".wrap form table:eq(2), .wrap form h3:eq(2)");

        if ($("#person_type").is(":checked")) {
            target.show();
        } else {
            target.hide();
        }
    }

    person_type_switch();

    $("#person_type").on('click', function() {
        person_type_switch();
    });
});
