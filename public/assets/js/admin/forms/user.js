const agentRoleName = crud.field('agent_role_name').value;
const isAgent = crud.field('is_agent');
const stateId = crud.field('state_id');
const $czech = $('div.checkbox label:contains("' + agentRoleName + '") :checkbox');

$czech.on('change', function (e){
    if ($czech[0].checked) {
        stateId.show().enable().require();
        isAgent.input.value = 'true';
    } else {
        // we don't want to disable it because we want to clear the value
        stateId.hide().unrequire();
        stateId.input.value = '';
        isAgent.input.value = 'false';
    }
}).trigger('change');