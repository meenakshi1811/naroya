<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
                        <input type="password" class="form-control" id="resetPasswordNew" name="password" autocomplete="new-password" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="resetPasswordConfirm">Confirm new password</label>
                        <input type="password" class="form-control" id="resetPasswordConfirm" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="resetPasswordSubmitBtn">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function openResetPasswordModal(userId, displayName, resetUrl) {
        $('#resetPasswordUserId').val(userId);
        $('#resetPasswordUrl').val(resetUrl);
        $('#resetPasswordUserLabel').text('Set a new password for: ' + (displayName || 'User'));
        $('#resetPasswordNew').val('');
        $('#resetPasswordConfirm').val('');
        $('#resetPasswordModal').modal('show');
    }

    $(document).off('submit', '#resetPasswordForm').on('submit', '#resetPasswordForm', function (e) {
        e.preventDefault();

        var password = $('#resetPasswordNew').val();
        var passwordConfirmation = $('#resetPasswordConfirm').val();
        var resetUrl = $('#resetPasswordUrl').val();
        var $submitBtn = $('#resetPasswordSubmitBtn');

        if (!password || password.length < 8) {
            Swal.fire({ icon: 'warning', title: 'Invalid password', text: 'Please enter a password of at least 8 characters.' });
            return;
        }

        if (password !== passwordConfirmation) {
            Swal.fire({ icon: 'warning', title: 'Passwords do not match', text: 'New password and confirmation must match.' });
            return;
        }

        $submitBtn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: resetUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                password: password,
                password_confirmation: passwordConfirmation
            },
            success: function (response) {
                $('#resetPasswordModal').modal('hide');
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
