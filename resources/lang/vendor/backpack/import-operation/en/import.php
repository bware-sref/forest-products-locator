<?php

return [
    /**
     * .xls cannot be queued because of some issue with non-Unicode characters.
     * As such, we don't want to allow .xls files.
     */
    'accepted_types' => 'Upload a .csv or an .xlsx file',
    'cant_find_log' => 'The specified import could not be found.',
    'click_here_to_download_file' => 'Click here to download your import file',
    'confirm_import' => "Confirm Import",
    'confirm_mapping' => 'Confirm Mapping',
    'confirm_selection' => 'Confirm Selection',
    'confirm_your_import' => 'Confirm your Import Settings',
    'dont_import' => "Don't Import",
    'download_example' => 'Click here to download an example file.',
    'import' => 'Import',
    'import_data' => 'Import Data',
    'import_data_from' => 'Import Data From File',
    'into_field' => 'Into Database Field',
    'map_fields' => 'Map Fields',
    'map_fields_for' => 'Map Fields For',
    'please_map_at_least_one' => "Please map at least one file column to a field.",
    'please_map_the_primary_key' => "Please map a file column to the primary key.",
    'primary_key' => 'Primary Key',
    'primary_key_not_found' => "Unable to find a primary key for :model, please specify a column with 'primary_key' => true or add a column with your model's primary key.",
    /**
     * just noting that the previous item wraps the line
     */
    'remap_import' => 'Remap Import',
    'restart_import' => 'Restart Import',
    'select_a_column' => 'Select a file column',
    'select_a_file' => 'Select a file to import',
    'start_import' => 'Start Import',
    'start_over' => 'Start Over',
    'upload_new_file' => 'Upload New File',
    'your_import_has_been_processed' => 'Your import has been processed successfully.',
    'your_import_has_been_queued' => 'Your import has been queued and will be processed in the background.',

    /**
     * The following describe column types in mappings.
     */
    'text' => 'Text',
    'number' => 'Number',
    'date' => 'Date',
    'boolean' => 'Boolean',
    'array' => 'Array'
];
