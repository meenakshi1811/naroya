{{-- Triggered by any element with data-cm-confirm="{action url}", data-title, data-message, data-confirm-label. --}}
<div class="modal fade cm-modal" id="cmConfirmModal" tabindex="-1" aria-labelledby="cmConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="cmConfirmForm">
                @csrf
                @method('DELETE')
                <div class="modal-body pt-4">
                    <span class="cm-confirm-icon"><i class="bi bi-exclamation-triangle"></i></span>
                    <h5 class="modal-title mb-2" id="cmConfirmTitle">Are you sure?</h5>
                    <p class="cm-muted mb-0" id="cmConfirmMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="cmConfirmSubmit">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-cm-confirm]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        document.getElementById('cmConfirmForm').action = trigger.getAttribute('data-cm-confirm');
        document.getElementById('cmConfirmTitle').textContent = trigger.getAttribute('data-title') || 'Are you sure?';
        document.getElementById('cmConfirmMessage').textContent = trigger.getAttribute('data-message') || '';
        document.getElementById('cmConfirmSubmit').textContent = trigger.getAttribute('data-confirm-label') || 'Delete';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cmConfirmModal')).show();
    });
</script>
