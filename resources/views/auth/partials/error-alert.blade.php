@if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-200" role="alert">
        {{ $errors->first() }}
    </div>
@endif
