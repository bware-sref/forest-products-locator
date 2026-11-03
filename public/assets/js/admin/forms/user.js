const agentRoleName = crud.field('agent_role_name').value;
const isAgent = crud.field('is_agent');
const stateId = crud.field('state_id');
const $czech = $('div.checkbox label:contains("' + agentRoleName + '") :checkbox');

/**
 * State is only for State Agents. The server enforces this too (ValidatesUserRoles, UserCrudController),
 * so this is just about showing the field when it matters.
 */
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

/**
 * One role per user (the server rejects more than one, see ValidatesUserRoles).
 * While a role is ticked, the other role checkboxes are disabled; untick it to choose a different one.
 * Disabling the checkboxes doesn't affect what's submitted: the roles field posts hidden inputs, not these.
 */
const $roles = $('input.primary_list');

function lockOtherRoles() {
    const $ticked = $roles.filter(':checked');
    $roles.not($ticked).prop('disabled', $ticked.length > 0);
}

$roles.on('change', lockOtherRoles);
lockOtherRoles();
