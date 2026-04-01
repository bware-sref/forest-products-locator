// Externalize our Zod schemas to clean up components
import { z } from 'zod';

export const millFormSchema =  z.object({

    mill_name: z
        .string()
        .min(2, "Mill Name must be at least 2 characters.")
        .max(255, "Mill Name must be at most 255 characters."),

    // oh man, schemata are composible
    // meaning we could define an reusable address schema
    physical_address: z
        .string()
        .min(5, 'Address must be at least 5 characters.')
        .max(255, 'Address must be at most 255 characters.')
        .optional()
        // or() with a literal is the magic that makes optional work properly
        .or(z.literal('')),

    physical_city: z
        .string()
        .min(3, 'City must be at least 3 characters.')
        .max(255, 'City must be at most 255 characters.')
        .optional()
        .or(z.literal('')),

    state_id: z
        .string(),

    // probably should add a regex for 
    physical_zip: z
        .string()
        .regex(/^\d{5}(-\d{4})?$/, 'ZIP Code must be at least 5 digits. Optional 4-digit suffix must follow a "-", ex: 12345-6789.')
        // .min(5, 'ZIP Code must be at least 5 characters.')
        // .max(10, 'ZIP Code must be at most 10 characters.')
        .optional()
        .or(z.literal('')),

    // do we want to add counties yet?
    // I'm not sure we ever want to add County if we are able to look it up
    // I ask because county_id select would need to update when state changes
    // which is fine, but seems a tad advanced for the first pass

    /**
     * more fields!
     * we should be able to copy and paste the mailing_* fields once we build the physical versions
     * mailing_address
     * mailing_city
     * mailing_state_id
     * mailing_zip
     * mailing_county_id
     */

    // we may want to define (or duh, find) a regex for phone numbers
    telephone: z
        .string()
        .min(10, 'Telephone must be at least 10 characters.')
        .max(17, 'Telephone must be at most 17 characters.')
        .optional()
        .or(z.literal('')),

    fax: z
        .string()
        .min(10, 'Fax must be at least 10 characters.')
        .max(17, 'Fax must be at most 17 characters.')
        .optional()
        .or(z.literal('')),

    email: z
        .email()
        .optional()
        .or(z.literal('')),

    web_site: z.url({
            protocol: /^https?$/,
            hostname: z.regexes.domain
        })
        .optional()
        .or(z.literal('')),

    size: z
        .string()
        .max(255, 'Size must be at most 255 characters.')
        .optional()
        .or(z.literal('')),

    // millTypes
    // woodSpecies
});