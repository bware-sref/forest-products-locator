{{-- This file is used for menu items by any Backpack v7 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-dropdown title="Mills" icon="la la-group">
    <x-backpack::menu-dropdown-item title="Manage Mills" icon="la la-question" :link="backpack_url('mill')" />
    <x-backpack::menu-dropdown-item title="Mill Edits" icon="la la-question" :link="backpack_url('mill-edits')" />
    <x-backpack::menu-dropdown-item title="Mill Types" icon="la la-question" :link="backpack_url('mill-type')" />
    <x-backpack::menu-dropdown-item title="Wood Species" icon="la la-question" :link="backpack_url('wood-species')" />
</x-backpack::menu-dropdown>
<x-backpack::menu-dropdown title="States" icon="la la-group"> 
    <x-backpack::menu-dropdown-item title="States" icon="la la-question" :link="backpack_url('state')" />
    <x-backpack::menu-dropdown-item title="Agents" icon="la la-question" :link="backpack_url('agent')" />
    <x-backpack::menu-dropdown-item title="Counties" icon="la la-question" :link="backpack_url('county')" />
</x-backpack::menu-dropdown>
<x-backpack::menu-dropdown title="FAQs" icon="la la-group">
    <x-backpack::menu-dropdown-item title="FAQs" icon="la la-question" :link="backpack_url('faq')" />
    <x-backpack::menu-dropdown-item title="FAQ Categories" icon="la la-question" :link="backpack_url('faq-category')" />
</x-backpack::menu-dropdown>
@if(backpack_user()->can('view statistics'))
<x-backpack::menu-dropdown title="Stats" icon="la la-group">
    <x-backpack::menu-dropdown-item title="Statistics" icon="la la-question" :link="backpack_url('statistics')" />
    <x-backpack::menu-dropdown-item title="Updated" icon="la la-question" :link="backpack_url('statistics/updated')" />
    <x-backpack::menu-dropdown-item title="Additions" icon="la la-question" :link="backpack_url('statistics/additions')" />
</x-backpack::menu-dropdown>
@endif
<x-backpack::menu-dropdown title="Users" icon="la la-group">
    <x-backpack::menu-dropdown-item title="Users" icon="la la-question" :link="backpack_url('user')" />
    @if(backpack_user()->can('edit roles'))
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-question" :link="backpack_url('role')" />
    @endif
    @if(backpack_user()->can('edit permissions'))
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-question" :link="backpack_url('permission')" />
    @endif
</x-backpack::menu-dropdown>
