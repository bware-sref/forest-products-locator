/**
 * For shadcn-components-extend InputSelect
 */
import { 
    Dispatch,
    ReactNode,
    SetStateAction,
} from "react";
import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';
import type { Map as LeafletMap } from 'leaflet';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

/**
 * Is this necessary?
 * Dunno.
 */
export interface ErrorValueType {
    errors: string[];
    [key: string]: unknown;
}

export interface SeoDefaults {
    description: string;
    ogType: string;
    twitterHandle: string | null;
    /** Origin only, no trailing slash (e.g. "https://example.com"). */
    siteUrl: string;
}

/**
 * Per-page override, shaped to match <Seo>'s props directly (see
 * resources/js/components/seo.tsx). Distinct from SeoDefaults (the
 * site-wide fallback shared on every request as `seo`) -- this is set
 * per-page as `pageSeo`, via App\Models\PageSeo::resolve() or computed
 * directly from a record's own fields for dynamic pages.
 */
export interface PageSeoOverride {
    title: string;
    description: string;
    image?: string | null;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    flash: FlashDataType;
    seo: SeoDefaults;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Mill {
    id: number;
    match_id: string;
    mill_id?: string;
    // It's mill_name on the model, so I'm intentionally making a redundant field
    mill_name?: string;
    latitude?: string;
    longitude?: string;
    year?: string;
    physical_address?: string;
    physical_city?: string;
    county_name?: string;
    physical_state?: string;
    state_id?: State|number;
    physical_zip?: string;
    mailing_address?: string;
    mailing_city?: string;
    // mailing_state may become a State|string
    mailing_state?: State|string;
    // need to add mailing_state_id after adding the relation to the Model
    mailing_state_id?: State|number;
    // need to add mailing_county_id after adding the relation to the Model
    mailing_county?: County|string|number;
    mailing_county_id?: County|number;
    mailing_zip?: string;
    telephone?: string;
    fax?: string;
    type?: string;
    species?: string;
    email?: string;
    web_site?: string;
    size?: string;
    modification_date?: string;
    // accessor!
    physical_address_two?: string;
    // relationships are represented as...other types!
    state?: State|string;
    county?: County|string;
    mill_types?: MillType[];
    wood_species?: WoodSpecies[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface MillType {
    id: number;
    name: string;
    mills?: Mill[];
    value: string;
    label: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface WoodSpecies {
    id: number;
    name: string;
    mills?: Mill[];
    value: string;
    label: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface State {
    id: number;
    name: string;
    slug: string;
    abbreviation: string;
    latitude?: string;
    longitude?: string;
    polygon?: string;
    resource_summary?: string;
    counties?: County[];
    mills?: Mill[];
    state_resources?: StateResource[];
    value: string;
    label: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface County {
    id: number;
    name: string;
    type?: string;
    latitude?: string;
    longitude?: string;
    geo_shape?: string;
    county_code?: string; // string to allow for leading zeros
    fips_code?: string; // string to allow for leading zeros
    gnis_code?: string; // string to allow for leading zeros
    state?: State;
    value?: string;
    label?: string;
    [key: string]: unknown; // This allows for additional properties...
}

export type SetState<T> = Dispatch<SetStateAction<T>>;

export type SelectOption = {
  value: string;
  label: string;
  icon?: React.ComponentType<{ className?: string }>;
  [key: string]: unknown; // allow for additional properties
};

export type SearchParams = {
    q?: string;
    state?: string;
    county?: string;
    millType?: string;
    woodSpecies?: string;
    _token?: string;
    /**
     * Proximity filter fields.
     * lat, lng, and radius are always sent together or not at all —
     * the API ignores lat/lng unless radius is also present.
     */
    lat?: string;
    lng?: string;
    /** Radius in miles */
    radius?: string;
};

/**
 * Tracks the state of the browser Geolocation API permission/request.
 *
 * - idle        — user has not yet interacted with the location button
 * - requesting  — getCurrentPosition() call is in flight
 * - granted     — coordinates are available; proximity controls are active
 * - denied      — user declined the permission prompt
 * - unavailable — browser does not support geolocation, or an unexpected
 *                 PositionError occurred (e.g. position unavailable)
 */
export type GeolocationStatus = 'idle' | 'requesting' | 'granted' | 'denied' | 'unavailable';

/**
 * for mill map and mill list
 * It's the same as MillListProps, so it should probably just be one type for both.
 */
export interface MillListProps {
    mills: Mill[];
    children?: ReactNode;
    coordinates?: {lat: number, lng: number} | null;
    radius?: string | null;
    /** Called with the underlying Leaflet map instance once it's mounted. */
    onMapReady?: (map: LeafletMap) => void;
    [key: string]: unknown;
}

export interface IMillListItemProps {
    mill: Mill;
    children?: ReactNode;
    [key: string]: unknown;
}

export interface IFaq {
    id: number;
    question: string;
    answer: string;
    order: number;
    faq_category_id: number | null;
    [key: string]: unknown;
}

export interface IFaqCategory {
    id: number;
    name: string;
    slug: string;
    order: number;
    faqs: IFaq[];
    [key: string]: unknown;
}

export interface StateResource {
    id: number;
    state_id: number;
    title: string;
    content: string;
    teaser?: string;
    sort_weight: number;
    [key: string]: unknown;
}