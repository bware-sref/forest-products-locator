(function() {
    console.log('faqs!');

    crud.field("published_at").onChange(function(field) {
        // Check if first_date has any value populated
        if (field.value) {
            crud.field("unpublished_at").show();
        } else {
            crud.field("unpublished_at").hide();
        }
    }).change(); // Run instantly on page load to set correct initial state

})();