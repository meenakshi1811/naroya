<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .reset-password-input-group {
        position: relative;
    }
    .reset-password-input-group .form-control {
        padding-right: 2.75rem;
    }
    .reset-password-toggle {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 2.75rem;
        border: none;
        background: transparent;
        color: #6c757d;
        cursor: pointer;
        z-index: 4;
    }
    .reset-password-toggle:hover {
        color: #109014;
    }
</style>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="close" aria-label="Close" onclick="closeResetPasswordModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="resetPasswordForm" novalidate>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="resetPasswordUserLabel"></p>
                    <input type="hidden" id="resetPasswordUserId" value="">
                    <input type="hidden" id="resetPasswordUrl" value="">
                    <div class="form-group">
                        <label for="resetPasswordNew">New password</label>
                        <div class="reset-password-input-group">
                            <input type="text" class="form-control" id="resetPasswordNew" name="password" autocomplete="new-password">
                            <button type="button" class="reset-password-toggle" data-target="#resetPasswordNew" aria-label="Hide password" title="Hide password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="resetPasswordConfirm">Confirm new password</label>
                        <div class="reset-password-input-group">
                            <input type="text" class="form-control" id="resetPasswordConfirm" name="password_confirmation" autocomplete="new-password">
                            <button type="button" class="reset-password-toggle" data-target="#resetPasswordConfirm" aria-label="Hide password" title="Hide password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" onclick="closeResetPasswordModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="resetPasswordSubmitBtn">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function setResetPasswordFieldVisible($input, visible) {
        var $toggle = $input.siblings('.reset-password-toggle');
        var $icon = $toggle.find('i');

        if (visible) {
            $input.attr('type', 'text');
            $icon.removeClass('bi-eye-slash').addClass('bi-eye');
            $toggle.attr({ 'aria-label': 'Hide password', title: 'Hide password' });
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('bi-eye').addClass('bi-eye-slash');
            $toggle.attr({ 'aria-label': 'Show password', title: 'Show password' });
        }
    }

    function resetResetPasswordFieldsVisibility() {
        setResetPasswordFieldVisible($('#resetPasswordNew'), true);
        setResetPasswordFieldVisible($('#resetPasswordConfirm'), true);
    }

    function closeResetPasswordModal() {
        var modalEl = document.getElementById('resetPasswordModal');
        var $modal = $('#resetPasswordModal');

        if ($modal.length && typeof $modal.modal === 'function') {
            $modal.modal('hide');
        }

        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
            }
        }

        $modal.removeClass('show').css('display', 'none').attr('aria-hidden', 'true');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    function openResetPasswordModal(userId, displayName, resetUrl) {
        $('#resetPasswordUserId').val(userId);
        $('#resetPasswordUrl').val(resetUrl);
        $('#resetPasswordUserLabel').text('Set a new password for: ' + (displayName || 'User'));
        $('#resetPasswordNew').val('');
        $('#resetPasswordConfirm').val('');
        resetResetPasswordFieldsVisibility();

        var modalEl = document.getElementById('resetPasswordModal');
        var $modal = $('#resetPasswordModal');

        if ($modal.length && typeof $modal.modal === 'function') {
            $modal.modal('show');
        } else if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $modal.addClass('show').css('display', 'block').attr('aria-hidden', 'false');
            $('body').addClass('modal-open');
            if (!$('.modal-backdrop').length) {
                $('<div class="modal-backdrop fade show"></div>').appendTo('body');
            }
        }
    }

    $(document).off('click', '.reset-password-toggle').on('click', '.reset-password-toggle', function () {
        var $input = $($(this).data('target'));
        var isVisible = $input.attr('type') === 'text';
        setResetPasswordFieldVisible($input, !isVisible);
    });

    $(document).off('submit', '#resetPasswordForm').on('submit', '#resetPasswordForm', function (e) {
        e.preventDefault();

        var password = $('#resetPasswordNew').val();
        var resetUrl = $('#resetPasswordUrl').val();
        var $submitBtn = $('#resetPasswordSubmitBtn');

        $submitBtn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: resetUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                password: password
            },
            success: function (response) {
                closeResetPasswordModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message || 'Password updated successfully.'
                });
            },
            error: function (xhr) {
                var message = 'Unable to update password. Please try again.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        message = Object.keys(errors).map(function (key) {
                            return errors[key][0];
                        }).join('\n');
                    }
                }
                Swal.fire({ icon: 'error', title: 'Error', text: message });
            },
            complete: function () {
                $submitBtn.prop('disabled', false).text('Update Password');
            }
        });
    });
</script>
