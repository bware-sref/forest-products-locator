{{-- CKeditor --}}
@php
    $field['options'] ??= [];
    $field['options']['extraPlugins'] = $field['extra_plugins'] ?? $field['options']['extra_plugins'] ?? [];
    $hasElfinder = class_exists('Backpack\FileManager\FileManagerServiceProvider') && 
                    (isset($field['elfinderOptions']) && $field['elfinderOptions'] !== false ? true : false);

    $toolbar = $field['options']['toolbar'] ?? ['undo', 'redo', '|', 'heading', 'outdent', 'indent', '|', 'bold', 'italic', '|', 'blockQuote', 'insertTable', 'bulletedList', 'numberedList', '|', 'link', 'mediaEmbed'];

    if($hasElfinder && !in_array('ckfinder', $toolbar)) {
        $toolbar = array_merge($toolbar, ['|', 'ckfinder']);
    }

    $defaultOptions = [
        'language' => app()->getLocale(),
        'extraPlugins' => $field['options']['extraPlugins'],
        'toolbar' => $toolbar,
    ];
    $field['options'] = array_merge($defaultOptions, $field['options'] ?? []);

    $field['elfinderOptions'] ??= [];
    if(!is_array($field['elfinderOptions'])) {
        $field['elfinderOptions'] = [];
    }

    $initFunction = $field['attributes']['data-init-function'] ?? 'bpFieldInitCKEditorElement';
@endphp

@include('crud::fields.inc.wrapper_start')
<label for="{{ $field['name'] }}">{!! $field['label'] !!}</label>
@include('crud::fields.inc.translatable_icon')
<textarea
    id="{{ $field['name'] }}"
    name="{{ $field['name'] }}"
    data-init-function="{{$initFunction}}"
    data-options="{{ trim(json_encode($field['options'])) }}"
    data-elfinder-options="{{ trim(json_encode($field['elfinderOptions'])) }}"
    data-elfinder="{{ var_export($hasElfinder) }}"
    data-elfinder-url="{{ url(config('elfinder.route.prefix').'/popup/elfinder?multiple=1') }}"
    bp-field-main-input        
    @include('crud::fields.inc.attributes', ['default_class' => 'form-control'])
    >{{ old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '' }}
</textarea>

{{-- HINT --}}
@if (isset($field['hint']))
    <p class="help-block">{!! $field['hint'] !!}</p>
@endif
@include('crud::fields.inc.wrapper_end')

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@push('crud_fields_styles')
    @if($hasElfinder)
        @basset('https://unpkg.com/jquery-colorbox@1.6.4/example2/colorbox.css')
        @basset('https://unpkg.com/jquery-colorbox@1.6.4/example2/images/loading.gif', false)
        @basset('https://unpkg.com/jquery-colorbox@1.6.4/example2/images/controls.png', false)
        @bassetBlock('backpack/pro/fields/browse-field.css')
        <style>
            #cboxContent, #cboxLoadedContent, .cboxIframe {
                background: transparent;
            }
        </style>
        @endBassetBlock
    @endif
@endpush

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('crud_fields_scripts')
    @if(isset($field['custom_build']))
        @foreach($field['custom_build'] as $script)
            @basset($script)
        @endforeach
    @else
        @basset('https://cdn.ckeditor.com/ckeditor5/37.1.0/classic/ckeditor.js')
        @if($hasElfinder)
            @basset('https://unpkg.com/jquery-colorbox@1.6.4/jquery.colorbox-min.js')
        @endif
        @bassetBlock('backpack/pro/fields/ckeditor.js')
        <script>
            // global variable to store elfinder element while waiting for colorbox to close
            var elfinderTarget = false;

            // if processSelectedMultipleFiles is not defined, define it
            if (typeof window.processSelectedMultipleFiles !== 'function') {
                function processSelectedMultipleFiles(files, input) {
                    elfinderTarget.trigger('createInputsForItemsSelectedWithElfinder', [files]);
                    elfinderTarget = false;
                }
            }

            async function bpFieldInitCKEditorElement(element) {
                let hasElfinder = element.data('elfinder');
                // To create CKEditor 5 classic editor
                let ckeditorInstance = await ClassicEditor.create(element[0], element.data('options')).catch(error => {
                    console.error( error );
                });

                if (!ckeditorInstance) return;
                if(hasElfinder) {
                    let ckf = ckeditorInstance.commands.get('ckfinder');
                    if (ckf) {
                        // Take over ckfinder execute()
                        ckf.execute = () => {
                            window.ckeditorInstance = ckeditorInstance;
                            window.elfinderOptions = element.data('elfinder-options') ?? {};

                            // remember which element the elFinder was triggered by
                            elfinderTarget = element;
                            
                            // trigger the reveal modal with elfinder inside
                            $.colorbox({
                                href: element.data('elfinder-url'),
                                fastIframe: false,
                                iframe: true,
                                width: '80%',
                                height: '80%',
                                onClosed: function () {
                                    elfinderTarget = false;
                                    window.ckeditorInstance = null;
                                    window.elfinderOptions = {};
                                },
                            });
                        };
                    }

                    element.on('createInputsForItemsSelectedWithElfinder', function (e, files) {
                        let imgs = [];
                        
                        $.each(files, function(i, f) {
                            if (f && f.mime.match(/^image\//i)) {
                                imgs.push(f.url);
                            } else {
                                ckeditorInstance.execute('link', f.url);
                            }
                        });
                        if (imgs.length) {
                            const ntf = ckeditorInstance.plugins.get('Notification');
                            const i18 = ckeditorInstance.locale.t;
                            const imgCmd = ckeditorInstance.commands.get('imageUpload');
                            if (!imgCmd.isEnabled) {
                                ntf.showWarning(i18('Could not insert image at the current position.'), {
                                    title: i18('Inserting image failed'),
                                    namespace: 'ckfinder'
                                });
                                return;
                            }
                            ckeditorInstance.execute('imageInsert', { source: imgs });
                        }
                    });
                }

                element.on('CrudField:delete', function (e) {
                    ckeditorInstance.destroy();
                });

                // trigger the change event on textarea when ckeditor changes
                ckeditorInstance.editing.view.document.on('layoutChanged', function (e) {
                    element.trigger('change');
                });

                element.on('CrudField:disable', function (e) {
                    ckeditorInstance.enableReadOnlyMode('CrudField');
                });

                element.on('CrudField:enable', function (e) {
                    ckeditorInstance.disableReadOnlyMode('CrudField');
                });
            }
        </script>
        @endBassetBlock
    @endif
@endpush

{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
