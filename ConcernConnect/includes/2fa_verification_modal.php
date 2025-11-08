<!-- Two-Factor Authentication Verification Modal -->
<div class="modal fade" id="twoFactorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header" style="border-bottom: 2px solid #f2f2f2;">
                <h5 class="modal-title" id="modalTitle">Enable Two-Step Verification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="step1" class="verification-step">
                    <p class="text-muted">To enable two-step verification, please verify your password first.</p>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current-password" placeholder="Enter your password">
                        <div class="invalid-feedback" id="password-error"></div>
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="verify-password-btn">Verify Password</button>
                </div>

                <div id="step2" class="verification-step" style="display: none;">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> A verification code has been sent to your email address.
                    </div>
                    <p class="text-muted">Please enter the 6-digit code we sent to <strong id="user-email"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Verification Code</label>
                        <input type="text" class="form-control text-center" id="verification-code" maxlength="6" placeholder="000000" style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                        <div class="invalid-feedback" id="code-error"></div>
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="verify-code-btn">Confirm Code</button>
                    <button type="button" class="btn btn-link w-100 mt-2" id="resend-code-btn">Resend Code</button>
                </div>

                <div id="step3" class="verification-step" style="display: none;">
                    <div class="text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                        <h4 class="mt-3">Two-Step Verification <span id="action-status">Enabled</span>!</h4>
                        <p class="text-muted">Your account is now more secure.</p>
                    </div>
                    <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="modalAction" value="">

<script>
$(document).ready(function() {
    let currentAction = 'enable';

    // When modal is shown
    $('#twoFactorModal').on('show.bs.modal', function() {
        currentAction = $('#modalAction').val();
        resetModal();
        
        if (currentAction === 'enable') {
            $('#modalTitle').text('Enable Two-Step Verification');
            $('#action-status').text('Enabled');
        } else {
            $('#modalTitle').text('Disable Two-Step Verification');
            $('#action-status').text('Disabled');
        }
    });

    // Verify Password
// Verify Password
$('#verify-password-btn').click(function() {
    const password = $('#current-password').val();
    
    console.log('Button clicked!'); // Debug
    console.log('Password entered:', password ? 'Yes' : 'No'); // Debug
    
    if (!password) {
        $('#current-password').addClass('is-invalid');
        $('#password-error').text('Please enter your password');
        return;
    }

    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Verifying...');

    console.log('Sending AJAX request...'); // Debug

    $.ajax({
        url: 'includes/verify_password.php',
        type: 'POST',
        data: { password: password },
        dataType: 'json',
        success: function(response) {
            console.log('Response received:', response); // Debug
            if (response.success) {
                $('#step1').hide();
                $('#step2').show();
                $('#user-email').text(response.email);
                
                // Send verification code
                sendVerificationCode();
            } else {
                $('#current-password').addClass('is-invalid');
                $('#password-error').text(response.message || 'Incorrect password');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error); // Debug
            console.error('Status:', status); // Debug
            console.error('Response:', xhr.responseText); // Debug
            alert('An error occurred. Check console for details.');
        },
        complete: function() {
            $('#verify-password-btn').prop('disabled', false).html('Verify Password');
        }
    });
});


    // Send Verification Code
    function sendVerificationCode() {
        $.ajax({
            url: 'includes/send_2fa_code.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    alert('Failed to send verification code. Please try again.');
                }
            }
        });
    }

    // Verify Code
    $('#verify-code-btn').click(function() {
        const code = $('#verification-code').val();
        
        if (!code || code.length !== 6) {
            $('#verification-code').addClass('is-invalid');
            $('#code-error').text('Please enter a valid 6-digit code');
            return;
        }

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Verifying...');

        $.ajax({
            url: 'includes/verify_2fa_code.php',
            type: 'POST',
            data: { 
                code: code,
                action: currentAction
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#step2').hide();
                    $('#step3').show();
                    
                    // Update status text
                    $('#2fa-status').text(currentAction === 'enable' ? 'Enabled' : 'Disabled');
                } else {
                    $('#verification-code').addClass('is-invalid');
                    $('#code-error').text('Invalid or expired code');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $('#verify-code-btn').prop('disabled', false).html('Confirm Code');
            }
        });
    });

    // Resend Code
    $('#resend-code-btn').click(function() {
        $(this).prop('disabled', true).text('Sending...');
        sendVerificationCode();
        
        setTimeout(() => {
            $(this).prop('disabled', false).text('Resend Code');
            alert('Verification code resent!');
        }, 2000);
    });

    // Reset Modal
    function resetModal() {
        $('#step1').show();
        $('#step2, #step3').hide();
        $('#current-password, #verification-code').val('').removeClass('is-invalid');
        $('#password-error, #code-error').text('');
    }

    // When modal is hidden
    $('#twoFactorModal').on('hidden.bs.modal', function() {
        // If user closed modal without completing, revert toggle
        const isEnabled = $('#2fa-status').text() === 'Enabled';
        $('#two-step-toggle').prop('checked', isEnabled);
    });
});
</script>
