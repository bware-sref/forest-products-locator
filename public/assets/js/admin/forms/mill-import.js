(function(){
    console.log('mill-import.js in the house!');
    // Jerk-ass Backpack crud JS object isn't included in all their page templates.
    // It's not a distinct script either.
    // Instead, it's defined across multiple blade files which contain inline script tags.
    const $form = $('#import-preview');

    if (1 > $form.length) {
        console.log('#import-preview form not found.');
    }

    const $btn = $form.find('#start-import');

    // on submit, we want to disable the submit button and show a spinner somewhere to indicate stuff is happening
    $form.on('submit', function (e) {
        $btn.prop('disabled', true);
        $btn.find('span.loader').show();
        return true;
    });
})();