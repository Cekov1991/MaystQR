{{--
    Copy-to-clipboard for any element carrying data-copy.

    The value is read from the attribute rather than interpolated into a JS
    string literal: scan content is owner-supplied, and an apostrophe or a
    newline in a message used to break the whole page for the person scanning.
--}}
<script>
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            navigator.clipboard.writeText(button.dataset.copy).then(function () {
                const original = button.textContent;
                button.textContent = 'Copied';
                setTimeout(function () {
                    button.textContent = original;
                }, 2000);
            });
        });
    });
</script>
