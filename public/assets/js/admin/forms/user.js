const stateAgent = 'State Agent';
const stateId = crud.field('state_id');
const roleNames = crud.field('checked_role_names');
const $czech = $('div.checkbox label:contains("' + stateAgent + '") :checkbox');
// console.log('$czech: ', $czech);

const $czechLabels = [];
const $allCzechs = $('div.checkbox label [label="Roles"]:checked');
// console.log('$allCzechs: ', $allCzechs);
// console.log('$allCzechs.length: ', $allCzechs.length);
$allCzechs.each(function(index, item) {
    let $item = $(item);
    // console.log('item: ', item);
    // console.log('$item: ', $item);
    let label = $item.parent().text().trim();
    // console.log('label: ', label);
    appendToField(roleNames, label);
});

function appendToField(field, value, separator = ',') {
    // console.log('append: ', {
    //     field: field,
    //     fieldValue: field.value,
    //     fieldInputValue: field.input.value,
    // });
    if ('' === String(field.input.value).trim()) {
        // field.value = value;
        field.input.value = value;
        // console.log('append, was empty, now: ', field.value);
        // console.log('OR was it? append, was empty, now: ', field.input.value);
        return;
    }
    // let names = String(roleNames.value).split(',');
    if (! String(field.input.value).trim().split(separator).includes(value)) {
        let v = [String(field.input.value).trim(), value].join(separator);
        // console.log('in append, new value: ', v);
        // field.value = [String(field.value).trim(), value].join(separator);
        field.input.value = v.replace(/^,+|,+$/, '');
    }
}

function removeFromField(field, value, separator = ',') {
    // console.log('remove: ', {
    //     field: field,
    //     fieldValue: field.value,
    //     fieldInputValue: field.input.value,
    // });
    if ('' === String(field.input.value).trim()) {
        // console.log('nothing to remove');
        return;
    }
    let fieldValues = String(field.input.value).trim().split(separator);
    let index = fieldValues.indexOf(value); 
    if (-1 !== index) {
        delete fieldValues[index];
        let v = fieldValues.join(separator);
        // console.log("in remove, new value: ", v);
        // field.value = fieldValues.join(separator);
        field.input.value = v.replace(/^,+|,+$/, '');
    }
}

$czech.on('change', function (e){
    // console.log('e: ', e);
    if ($czech[0].checked) {
        stateId.show().enable().require();
        // console.log('enabling stateId');
        appendToField(roleNames, stateAgent);
    } else {
        // we don't want to disable it because we want to clear the value
        stateId.hide().unrequire();
        stateId.input.value = '';
        // console.log('disabling stateId');
        removeFromField(roleNames, stateAgent);
    }

    // console.log('roleNames.value: ', roleNames.value);
    console.log('roleNames.input.value: ', roleNames.input.value);
}).trigger('change');