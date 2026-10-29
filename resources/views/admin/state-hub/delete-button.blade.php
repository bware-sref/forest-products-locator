{{--
  State Hub version of crud::buttons.delete: same markup, without a $crud. JS is in delete-script.blade.php.
  Access is decided by the caller ({table}.edit); the CRUD's DeleteOperation enforces it again server-side.
  With no crud table on the page, the JS redirects to data-redirect-route (the hub) after a successful delete.
--}}
<a href="javascript:void(0)"
    onclick="deleteEntry(this)"
    bp-button="delete"
    data-redirect-route="{{ $redirectUrl }}"
    data-route="{{ $route }}"
    data-warning-text="{!! trans('backpack::base.warning') !!}"
    data-confirm-text="{!! trans('backpack::crud.delete_confirm') !!}"
    data-cancel-text="{!! trans('backpack::crud.cancel') !!}"
    data-delete-text="{!! trans('backpack::crud.delete') !!}"
    data-error-title="{!! trans('backpack::crud.delete_confirmation_not_title') !!}"
    data-error-text="{!! trans('backpack::crud.delete_confirmation_not_message') !!}"
    data-delete-confirmation-text="{!! '<strong>'.trans('backpack::crud.delete_confirmation_title').'</strong><br>'.trans('backpack::crud.delete_confirmation_message') !!}"
    class="text-danger ms-3"
    data-button-type="delete"
>
    <i class="la la-trash"></i> {{ trans('backpack::crud.delete') }}
</a>

@include('admin.state-hub.delete-script')
