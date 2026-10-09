{{-- This file is used for menu items by any Backpack v7 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

@if(backpack_user()->canAny([
    'mills.see', 'mills.edit', 'mills.import',
    'mill_types.see', 'mill_types.edit',
    'wood_species.see', 'wood_species.edit',
]))
<x-backpack::menu-dropdown title="Mills" icon="la la-industry">
    @if(backpack_user()->canAny(['mills.see', 'mills.edit']))
        <x-backpack::menu-dropdown-item title="Mills" icon="la la-industry" :link="backpack_url('mill')" />
    @endif
    {{-- @if(backpack_user()->canAny(['mill_edits.see', 'mill_edits.edit']))
        <x-backpack::menu-dropdown-item title="Mill Edits" icon="la la-edit" :link="backpack_url('mill-edits')" />
    @endif --}}
    @if(backpack_user()->canAny(['mill_types.see', 'mill_types.edit']))
        <x-backpack::menu-dropdown-item title="Mill Types" icon="la la-keyboard" :link="backpack_url('mill-type')" />
    @endif
    @if(backpack_user()->canAny(['wood_species.see', 'wood_species.edit']))
        <x-backpack::menu-dropdown-item title="Wood Species" icon="la la-tree" :link="backpack_url('wood-species')" />
    @endif
    @if(backpack_user()->can('mills.import'))
        <x-backpack::menu-dropdown-item title="Import" icon="la la-tree" :link="backpack_url('mill/import')" />
    @endif
</x-backpack::menu-dropdown>
@endif
@if(backpack_user()->canAny(['states.see', 'states.edit', 'counties.see', 'counties.edit']))
<x-backpack::menu-dropdown title="States" icon="la la-landmark"> 
    @if(backpack_user()->canAny(['states.see', 'states.edit',]))
        <x-backpack::menu-dropdown-item title="States" icon="la la-flag-usa" :link="backpack_url('state')" />
    @endif
    @if(backpack_user()->canAny(['counties.see', 'counties.edit']))
        <x-backpack::menu-dropdown-item title="Counties" icon="la la-hotdog" :link="backpack_url('county')" />
    @endif
</x-backpack::menu-dropdown>
@endif
@if(backpack_user()->canAny(\App\Http\Controllers\Admin\StateHubController::permissions()))
    <x-backpack::menu-item title="State Page" icon="la la-sitemap" :link="route('admin.page.state-hub.show')" />
@endif
{{-- the per-section CRUD lists are for Admins and Supers; everyone else works through the State Page (hub) --}}
@if((backpack_user()->isAdmin() || backpack_user()->isSuper()) && backpack_user()->canAny([
    'state_pages.see', 'state_pages.edit',
    'state_contacts.see', 'state_contacts.edit',
    'state_forest_overviews.see', 'state_forest_overviews.edit',
    'state_forest_types.see', 'state_forest_types.edit',
    'state_forest_products.see', 'state_forest_products.edit',
    'state_economic_impacts.see', 'state_economic_impacts.edit',
    'state_forestry_agencies.see', 'state_forestry_agencies.edit',
    'state_assistance_categories.see', 'state_assistance_categories.edit',
    'state_assistance_links.see', 'state_assistance_links.edit',
]))
<x-backpack::menu-dropdown title="State Pages" icon="la la-file-alt">
    @if(backpack_user()->canAny(['state_pages.see', 'state_pages.edit']))
        <x-backpack::menu-dropdown-item title="Hero / Page Content" icon="la la-heading" :link="backpack_url('state-page')" />
    @endif
    @if(backpack_user()->canAny(['state_contacts.see', 'state_contacts.edit']))
        <x-backpack::menu-dropdown-item title="Contacts" icon="la la-address-card" :link="backpack_url('state-contact')" />
    @endif
    @if(backpack_user()->canAny(['state_forest_overviews.see', 'state_forest_overviews.edit']))
        <x-backpack::menu-dropdown-item title="Forest Overview" icon="la la-tree" :link="backpack_url('state-forest-overview')" />
    @endif
    @if(backpack_user()->canAny(['state_forest_types.see', 'state_forest_types.edit']))
        <x-backpack::menu-dropdown-item title="Regional Forest Types" icon="la la-leaf" :link="backpack_url('state-forest-type')" />
    @endif
    @if(backpack_user()->canAny(['state_forest_products.see', 'state_forest_products.edit']))
        <x-backpack::menu-dropdown-item title="Forest Products" icon="la la-boxes" :link="backpack_url('state-forest-product')" />
    @endif
    @if(backpack_user()->canAny(['state_economic_impacts.see', 'state_economic_impacts.edit']))
        <x-backpack::menu-dropdown-item title="Economic Impact" icon="la la-chart-line" :link="backpack_url('state-economic-impact')" />
    @endif
    @if(backpack_user()->canAny(['state_forestry_agencies.see', 'state_forestry_agencies.edit']))
        <x-backpack::menu-dropdown-item title="Forestry Agency" icon="la la-landmark" :link="backpack_url('state-forestry-agency')" />
    @endif
    @if(backpack_user()->canAny(['state_assistance_categories.see', 'state_assistance_categories.edit']))
        <x-backpack::menu-dropdown-item title="Assistance Categories" icon="la la-th-list" :link="backpack_url('state-assistance-category')" />
    @endif
    @if(backpack_user()->canAny(['state_assistance_links.see', 'state_assistance_links.edit']))
        <x-backpack::menu-dropdown-item title="Assistance Links" icon="la la-link" :link="backpack_url('state-assistance-link')" />
    @endif
</x-backpack::menu-dropdown>
@endif
@if(backpack_user()->canAny(['faqs.see', 'faqs.edit', 'faq_categories.see', 'faq_categories.edit']))
<x-backpack::menu-dropdown title="FAQs" icon="la la-question">
    @if(backpack_user()->canAny(['faqs.see', 'faqs.edit']))
        <x-backpack::menu-dropdown-item title="FAQs" icon="la la-question" :link="backpack_url('faq')" />
    @endif
    @if(backpack_user()->canAny(['faq_categories.see', 'faq_categories.edit']))
        <x-backpack::menu-dropdown-item title="FAQ Categories" icon="la la-question-circle" :link="backpack_url('faq-category')" />
    @endif
</x-backpack::menu-dropdown>
@endif
@if(backpack_user()->can('statistics.see'))
<x-backpack::menu-dropdown title="Stats" icon="la la-chart-pie">
    <x-backpack::menu-dropdown-item title="Statistics" icon="la la-chart-area" :link="backpack_url('statistics')" />
    <x-backpack::menu-dropdown-item title="Updated" icon="la la-chart-bar" :link="backpack_url('statistics/updated')" />
    <x-backpack::menu-dropdown-item title="Additions" icon="la la-chart-line" :link="backpack_url('statistics/additions')" />
</x-backpack::menu-dropdown>
@endif
@if(backpack_user()->canAny(['users.see', 'users.edit', 'roles.edit', 'permissions.edit']))
<x-backpack::menu-dropdown title="Users" icon="la la-group">
    @if(backpack_user()->canAny(['users.see', 'users.edit']))
        <x-backpack::menu-dropdown-item title="Users" icon="la la-user-alt" :link="backpack_url('user')" />
    @endif
    @if(backpack_user()->can('roles.edit'))
        <x-backpack::menu-dropdown-item title="Roles" icon="la la-dice" :link="backpack_url('role')" />
    @endif
    @if(backpack_user()->can('permissions.edit'))
        <x-backpack::menu-dropdown-item title="Permissions" icon="la la-hat-wizard" :link="backpack_url('permission')" />
    @endif
</x-backpack::menu-dropdown>
@endif
@if(backpack_user()->canAny(['page_seos.see', 'page_seos.edit']))
    <x-backpack::menu-item
        title="Page SEO"
        icon="la la-skull-crossbones"
        :link="backpack_url('page-seo')"
    />
@endif