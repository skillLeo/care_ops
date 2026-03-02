<div class="modal fade" id="duplicateSubmissionModal" tabindex="-1" role="dialog" aria-labelledby="duplicateSubmissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateSubmissionModalLabel">Possible duplicate submission</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>There is one existing submission matching this criteria. Are you sure you want to continue?</p>
                <ul class="mb-0" id="duplicateSubmissionList"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmDuplicateSubmissionContinue">Confirm and continue</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.checkSubmissionDuplicates = async function(config) {
        const payload = {
            ...config,
            view_route: config.viewRoute,
        };
        delete payload.viewRoute;

        const response = await fetch(@json(route('attendance-submissions.duplicate-check')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            return true;
        }

        const result = await response.json();
        const duplicates = Array.isArray(result.duplicates) ? result.duplicates : [];

        if (duplicates.length === 0) {
            return true;
        }

        const list = document.getElementById('duplicateSubmissionList');
        list.innerHTML = '';

        duplicates.forEach((item, index) => {
            const entry = document.createElement('li');
            entry.innerHTML = `Entry #${index + 1}${item.submitted_by ? ` by ${item.submitted_by}` : ''}${item.created_at ? ` (${item.created_at})` : ''} <a href="${item.view_url}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary ml-2">View</a>`;
            list.appendChild(entry);
        });

        return await new Promise((resolve) => {
            const modalElement = document.getElementById('duplicateSubmissionModal');
            let modalInstance = null;
            let modalBackdrop = null;

            const cleanup = () => {
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    modalElement.classList.remove('show');
                    modalElement.style.display = 'none';
                    modalElement.setAttribute('aria-hidden', 'true');
                    if (modalBackdrop) {
                        modalBackdrop.remove();
                        modalBackdrop = null;
                    }
                    document.body.classList.remove('modal-open');
                }
            };

            const confirmButton = document.getElementById('confirmDuplicateSubmissionContinue');
            const cancelButtons = modalElement.querySelectorAll('[data-dismiss="modal"]');

            const handleConfirm = () => {
                confirmButton.removeEventListener('click', handleConfirm);
                cancelButtons.forEach((button) => button.removeEventListener('click', handleCancel));
                cleanup();
                resolve(true);
            };

            const handleCancel = () => {
                confirmButton.removeEventListener('click', handleConfirm);
                cancelButtons.forEach((button) => button.removeEventListener('click', handleCancel));
                cleanup();
                resolve(false);
            };

            confirmButton.addEventListener('click', handleConfirm);
            cancelButtons.forEach((button) => button.addEventListener('click', handleCancel));

            if (window.bootstrap?.Modal) {
                modalInstance = new bootstrap.Modal(modalElement);
                modalInstance.show();
            } else {
                modalElement.classList.add('show');
                modalElement.style.display = 'block';
                modalElement.removeAttribute('aria-hidden');
                modalBackdrop = document.createElement('div');
                modalBackdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(modalBackdrop);
                document.body.classList.add('modal-open');
            }
        });
    }
</script>
