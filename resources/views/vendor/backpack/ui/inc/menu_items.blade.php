{{-- This file is used for menu items by any Backpack v7 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-dropdown title="Mills" icon="la la-industry">
    <x-backpack::menu-dropdown-item title="Mills" icon="la la-industry" :link="backpack_url('mill')" />
    <x-backpack::menu-dropdown-item title="Mill Edits" icon="la la-edit" :link="backpack_url('mill-edits')" />
    <x-backpack::menu-dropdown-item title="Mill Types" icon="la la-keyboard" :link="backpack_url('mill-type')" />
    <x-backpack::menu-dropdown-item title="Wood Species" icon="la la-tree" :link="backpack_url('wood-species')" />
    <x-backpack::menu-dropdown-item title="Import" icon="la la-tree" :link="backpack_url('mill/import')" />
</x-backpack::menu-dropdown>
<x-backpack::menu-dropdown title="States" icon="la la-landmark"> 
    <x-backpack::menu-dropdown-item title="States" icon="la la-flag-usa" :link="backpack_url('state')" />
    @if(false)
    <x-backpack::menu-dropdown-item title="Agents" icon="la la-question" :link="backpack_url('agent')" />
    @endif
    <x-backpack::menu-dropdown-item title="Counties" icon="la la-hotdog" :link="backpack_url('county')" />
    <x-backpack::menu-dropdown-item title="State Resources" icon="la la-boxes" :link="backpack_url('state-resource')" />
</x-backpack::menu-dropdown>
@if(backpack_user()->canAny(['faqs.see', 'faqs.edit']))
<x-backpack::menu-dropdown title="FAQs" icon="la la-question">
    <x-backpack::menu-dropdown-item title="FAQs" icon="la la-question" :link="backpack_url('faq')" />
    <x-backpack::menu-dropdown-item title="FAQ Categories" icon="la la-question-circle" :link="backpack_url('faq-category')" />
</x-backpack::menu-dropdown>
@endif
@if(backpack_user()->can('statistics.see'))
<x-backpack::menu-dropdown title="Stats" icon="la la-chart-pie">
    <x-backpack::menu-dropdown-item title="Statistics" icon="la la-chart-area" :link="backpack_url('statistics')" />
    <x-backpack::menu-dropdown-item title="Updated" icon="la la-chart-bar" :link="backpack_url('statistics/updated')" />
    <x-backpack::menu-dropdown-item title="Additions" icon="la la-chart-line" :link="backpack_url('statistics/additions')" />
</x-backpack::menu-dropdown>
@endif
@if(backpack_user()->canAny(['users.see', 'users.edit']))
<x-backpack::menu-dropdown title="Users" icon="la la-group">
    <x-backpack::menu-dropdown-item title="Users" icon="la la-user-alt" :link="backpack_url('user')" />
    @if(backpack_user()->can('roles.edit'))
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-dice" :link="backpack_url('role')" />
    @endif
    @if(backpack_user()->can('permissions.edit'))
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-hat-wizard" :link="backpack_url('permission')" />
    @endif
</x-backpack::menu-dropdown>
@endif