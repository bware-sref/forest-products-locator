import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

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

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
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
    mill_name?: string;
    latitude?: string;
    longitude?: string;
    year?: string;
    physical_address?: string;
    physical_city?: string;
    county_name?: string;
    physical_state?: string;
    physical_zip?: string;
    mailing_address?: string;
    mailing_city?: string;
    mailing_state?: string;
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
    [key: string]: unknown; // This allows for additional properties...
}

export interface WoodSpecies {
    id: number;
    name: string;
    mills?: Mill[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface State {
    id: number;
    name: string;
    abbreviation: string;
    latitude?: string;
    longitude?: string;
    counties?: County[];
    mills?: Mill[];
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
    [key: string]: unknown; // This allows for additional properties...
}