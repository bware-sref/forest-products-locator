console.log('user.js is in the house!');

var stateId = crud.field('state_id');

var $czech = $('div.checkbox label:contains("State Agent") :checkbox');
console.log('$czech: ', $czech);


$czech.on('change', function (e){
    console.log('e: ', e);
    if ($czech[0].checked) {
        stateId.show().enable().require();
        console.log('enabling stateId');
    } else {
        stateId.hide().disable().unrequire();
        console.log('disabling stateId');
    }
}).trigger('change');