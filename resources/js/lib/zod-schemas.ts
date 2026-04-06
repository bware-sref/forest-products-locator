// Externalize our Zod schemas to clean up components
import { z } from 'zod';

// attempting to safeParse() undefined is the "correct" way to determine if a field is required in Zod 4.
export function doesZodRequire(schema: z.ZodObject<any>, field: string): boolean {
    const fieldSchema = schema.shape[field];
    return !fieldSchema.safeParse(undefined).success;
}

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
    // how about a checkbox for specifying that street and mailing addresses are the same?
    mailing_address_same_as_physical: z
        .boolean(),
        // .optional()
        /**
         * FYI, default() makes the field optional, but it doesn't work with checkboxes because they always submit a value (true or false)
         */
        // .default(true),

    /**
     * more fields!
     * we should be able to copy and paste the mailing_* fields once we build the physical versions
     * mailing_address
     * mailing_city
     * mailing_state_id
     * mailing_zip
     * mailing_county_id
     */
    mailing_address: z
        .string()
        .min(5, 'Address must be at least 5 characters.')
        .max(255, 'Address must be at most 255 characters.')
        .optional()
        .or(z.literal('')),

    mailing_city: z
        .string()
        .min(3, 'City must be at least 3 characters.')
        .max(255, 'City must be at most 255 characters.')
        .optional()
        .or(z.literal('')),

    mailing_state_id: z
        .string()
        .optional()
        .or(z.literal('')),

    // probably should add a regex for 
    mailing_zip: z
        .string()
        .regex(/^\d{5}(-\d{4})?$/, 'ZIP Code must be at least 5 digits. Optional 4-digit suffix must follow a "-", ex: 12345-6789.')
        .optional()
        .or(z.literal('')),

    // we may want to define (or duh, find) a regex for phone numbers
    telephone: z
        .string()
        .regex(/^\+?1?(\s*[\-\.]\s*|\s+)?\(?[2-9][0-9]{2}\)?(\s*[\-\.]\s*|\s+)?[0-9]{3}(\s*[\-\.]\s*|\s+)?[0-9]{4}$/, 'Telephone must be a valid US phone number.')
        .optional()
        .or(z.literal('')),

    fax: z
        .string()
        .regex(/^\+?1?(\s*[\-\.]\s*|\s+)?\(?[2-9][0-9]{2}\)?(\s*[\-\.]\s*|\s+)?[0-9]{3}(\s*[\-\.]\s*|\s+)?[0-9]{4}$/, 'Fax must be a valid US phone number.')
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

    // millTypes - array containing zero or more string mill_type_ids
    mill_types: z
        .array(z.string()),
        // .default([]),

    // woodSpecies - array containing zero or more string wood_species_ids
    wood_species: z
        .array(z.string()),
        // .default([]),

    // submitter_email - required
    submitter_email: z
        .email(),

});