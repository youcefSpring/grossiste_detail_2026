{{--
    The shell a form gets when it was opened inside a modal: no sidebar, no
    header, just the content and the title modal.js puts in the header bar.
--}}
<div data-modal-content data-title="@yield('title')">
    @yield('content')
</div>
