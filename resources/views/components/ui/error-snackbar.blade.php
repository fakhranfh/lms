{{--
    Global validation-error snackbar. Shown by window.handleFormValidationErrors
    (resources/js/form-error-scroll.js) whenever a form submission fails with
    more than one invalid field, so every failure stays visible even if its
    input has scrolled off-screen. Included once in layouts/app.blade.php,
    driven entirely via the plain window.showFormErrorSnackbar /
    window.hideFormErrorSnackbar globals defined below (matching the rest of
    the app's non-Alpine, vanilla-JS DOM helpers).
--}}
<div
    id="form-error-snackbar"
    class="fixed top-space-lg left-space-lg z-[200] w-full max-w-sm"
    hidden
>
    <div class="bg-surface border border-error rounded-lg shadow-lg overflow-hidden">
        <div class="flex items-center justify-between gap-space-sm px-space-md py-space-sm bg-error/10 border-b border-error/30">
            <p class="font-label-md text-label-md text-error">Please fix the following</p>
            <button type="button" id="form-error-snackbar-close" class="text-secondary hover:text-on-surface" aria-label="Dismiss">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        <ul id="form-error-snackbar-list" class="max-h-80 overflow-y-auto divide-y divide-outline-variant"></ul>
    </div>
</div>

<script>
    (function () {
        const snackbar = document.getElementById('form-error-snackbar');
        const list = document.getElementById('form-error-snackbar-list');
        const closeButton = document.getElementById('form-error-snackbar-close');

        if (!snackbar || !list) {
            return;
        }

        window.showFormErrorSnackbar = function (items) {
            list.innerHTML = '';

            items.forEach((item) => {
                const li = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left px-space-md py-space-sm hover:bg-error/5 transition-colors';

                const labelEl = document.createElement('span');
                labelEl.className = 'block font-label-sm text-label-sm text-on-surface';
                labelEl.textContent = item.label;

                const messageEl = document.createElement('span');
                messageEl.className = 'block font-body-xs text-body-xs text-error mt-space-xs';
                messageEl.textContent = item.message;

                button.append(labelEl, messageEl);
                button.addEventListener('click', () => {
                    item.focus();
                    window.hideFormErrorSnackbar();
                });
                li.appendChild(button);
                list.appendChild(li);
            });

            snackbar.hidden = false;
        };

        window.hideFormErrorSnackbar = function () {
            snackbar.hidden = true;
        };

        closeButton.addEventListener('click', window.hideFormErrorSnackbar);
    })();
</script>
