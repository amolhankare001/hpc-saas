// HPC Card SaaS - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Rubric level selection
    document.querySelectorAll('.rubric-level').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var group = this.closest('.rubric-group');
            group.querySelectorAll('.rubric-level').forEach(function(b) {
                b.classList.remove('selected');
            });
            this.classList.add('selected');
            var input = group.querySelector('input[type="hidden"]');
            if (input) input.value = this.dataset.value;
        });
    });

    // Interest item toggle
    document.querySelectorAll('.interest-item').forEach(function(item) {
        item.addEventListener('click', function() {
            var checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('selected', checkbox.checked);
        });
    });

    // Photo upload preview
    var photoInput = document.getElementById('photo_input');
    var photoPreview = document.getElementById('photo_preview');
    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.innerHTML = '<img src="' + e.target.result + '" alt="Photo">';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Auto-calculate attendance percentage
    document.querySelectorAll('.attendance-working, .attendance-present').forEach(function(input) {
        input.addEventListener('input', calculateAttendance);
    });

    // Calculate age from DOB
    var dobInput = document.getElementById('date_of_birth');
    if (dobInput) {
        dobInput.addEventListener('change', function() {
            var dob = new Date(this.value);
            var today = new Date();
            var age = today.getFullYear() - dob.getFullYear();
            var m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
            var ageField = document.getElementById('age');
            if (ageField) ageField.value = age;
        });
    }

    // Form validation
    document.querySelectorAll('form.needs-validation').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});

function calculateAttendance() {
    var totalWorking = 0;
    var totalPresent = 0;
    var months = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];
    
    months.forEach(function(month) {
        var working = document.querySelector('input[name="working_' + month + '"]');
        var present = document.querySelector('input[name="present_' + month + '"]');
        if (working && present) {
            var w = parseInt(working.value) || 0;
            var p = parseInt(present.value) || 0;
            totalWorking += w;
            totalPresent += p;
        }
    });

    var totalWorkingEl = document.getElementById('total_working');
    var totalPresentEl = document.getElementById('total_present');
    var percentageEl = document.getElementById('attendance_percentage');

    if (totalWorkingEl) totalWorkingEl.textContent = totalWorking;
    if (totalPresentEl) totalPresentEl.textContent = totalPresent;
    if (percentageEl) {
        var pct = totalWorking > 0 ? ((totalPresent / totalWorking) * 100).toFixed(1) : 0;
        percentageEl.textContent = pct + '%';
    }
}

// Fix PDF links: ensure they point to the working inner path /hpc/generate_pdf.php
// This handles cases where server-side OPcache serves old code with outdated URLs
(function() {
    document.querySelectorAll('a[href*="generate_pdf.php"]').forEach(function(a) {
        var href = a.getAttribute('href');
        if (href && href.indexOf('/hpc/generate_pdf.php') === -1) {
            a.setAttribute('href', href.replace('generate_pdf.php', 'hpc/generate_pdf.php'));
        }
    });
})();

// Print HPC Card
function printHPC() {
    window.print();
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'तुम्हाला खात्री आहे का? हे कायमचे हटवले जाईल.');
}
