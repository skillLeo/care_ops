(() => {
    const modalId = 'pinModal';
    let modalElement = null;
    let activeForm = null;

    const buildModal = () => {
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-labelledby="${modalId}Label" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${modalId}Label">Enter PIN</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-2">This action cannot be reversed. Please provide your PIN to confirm.</p>
                            <div class="form-group">
                                <input type="text" class="form-control pin-input" name="pin_input" autocomplete="off" inputmode="numeric" />
                                <small class="text-danger" data-pin-error></small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" data-pin-confirm>Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modalElement = document.getElementById(modalId);
        const style = document.createElement('style');
        style.textContent = '.pin-input{-webkit-text-security:disc;}';
        document.head.appendChild(style);
    };

    const getModal = () => {
        if (!modalElement) {
            modalElement = document.getElementById(modalId);
        }

        if (!modalElement) {
            buildModal();
        }

        return modalElement;
    };

    const showModal = () => {
        const modal = getModal();
        const input = modal.querySelector('input[name="pin_input"]');
        const error = modal.querySelector('[data-pin-error]');

        if (input) {
            input.value = '';
            input.focus();
        }
        if (error) {
            error.textContent = '';
        }

        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery(modal).modal('show');
        } else {
            modal.style.display = 'block';
            modal.classList.add('show');
        }
    };

    const hideModal = () => {
        const modal = getModal();

        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery(modal).modal('hide');
        } else {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
    };

    const handleConfirm = () => {
        const modal = getModal();
        const input = modal.querySelector('input[name="pin_input"]');
        const error = modal.querySelector('[data-pin-error]');
        const pinValue = input ? input.value.trim() : '';

        if (!pinValue) {
            if (error) {
                error.textContent = 'PIN is required.';
            }
            return;
        }

        if (!activeForm) {
            hideModal();
            return;
        }

        let hiddenInput = activeForm.querySelector('input[name="pin"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'pin';
            activeForm.appendChild(hiddenInput);
        }

        hiddenInput.value = pinValue;
        activeForm.submit();
    };

    document.addEventListener('DOMContentLoaded', () => {
        const forms = document.querySelectorAll('form[data-pin-form="true"]');

        if (!forms.length) {
            return;
        }

        const modal = getModal();
        const confirmButton = modal.querySelector('[data-pin-confirm]');

        forms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.pinSubmitted === 'true') {
                    form.dataset.pinSubmitted = '';
                    return;
                }

                event.preventDefault();
                activeForm = form;
                showModal();
            });
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', () => {
                if (activeForm) {
                    activeForm.dataset.pinSubmitted = 'true';
                }
                handleConfirm();
            });
        }

        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery(modal).on('hidden.bs.modal', () => {
                activeForm = null;
            });
        }
    });
})();
