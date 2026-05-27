{{-- This file is used for menu items by any Backpack v7 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-item title="Users" icon="la la-question" :link="backpack_url('user')" />
<x-backpack::menu-item title="Agents" icon="la la-question" :link="backpack_url('agent')" />
<x-backpack::menu-item title="Counties" icon="la la-question" :link="backpack_url('county')" />
<x-backpack::menu-item title="Mills" icon="la la-question" :link="backpack_url('mill')" />
<x-backpack::menu-item title="Mill Edits" icon="la la-question" :link="backpack_url('mill-edits')" />
<x-backpack::menu-item title="Mill Types" icon="la la-question" :link="backpack_url('mill-type')" />
<x-backpack::menu-item title="States" icon="la la-question" :link="backpack_url('state')" />
<x-backpack::menu-item title="Wood Species" icon="la la-question" :link="backpack_url('wood-species')" />
<x-backpack::menu-item title="FAQ Categories" icon="la la-question" :link="backpack_url('faq-category')" />
<x-backpack::menu-item title="FAQs" icon="la la-question" :link="backpack_url('faq')" />
<x-backpack::menu-item title="Statistics" icon="la la-question" :link="backpack_url('statistics')" />