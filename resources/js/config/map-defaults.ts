import {
    IMapConfigs
} from '@/types'

export const mapDefaults: IMapConfigs = {
    center: {
        latitude: 34.887494,
        longitude: -88.873249,
    },
    cluster: {
        polygon: {
            hover: false,
        }
    },
    tile: {
        attribution: {
            text: 'OpenStreetMap',
            url: 'https://www.openstreetmap.org/copyright',
            full: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
        },
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    },
    zoom: {
        multiple: 5,
        single: 14,
    },
}