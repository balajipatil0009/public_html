let studentData = [];
let dataLoaded = true; // No longer need to load external data

// Load Excel data
// The previous loadExcelData function is no longer needed as data is fetched via API.

// Search for certificate
async function validateCertificate() {
    const certificateId = document.getElementById('certificateId').value.trim();
    const resultContainer = document.getElementById('validationResult');

    if (!certificateId) {
        showResult('<i class="fas fa-exclamation-triangle"></i> Please enter a certificate reference ID.', 'error');
        return;
    }

    showResult('<i class="fas fa-sync fa-spin"></i> Communicating with secure archives...', 'info');

    try {
        const response = await fetch(`backend/api/validate.php?id=${encodeURIComponent(certificateId)}`);
        const result = await response.json();

        if (result.success) {
            const student = result.data;
            const content = `
                <div class="verified-badge"><i class="fas fa-check-circle"></i> Authenticated Document</div>
                <div class="data-row">
                    <div class="data-label">Candidate Name</div>
                    <div class="data-value">${student.name}</div>
                </div>
                <div class="data-row">
                    <div class="data-label">Certified Specialization</div>
                    <div class="data-value">${student.course}</div>
                </div>
                <div class="data-row">
                    <div class="data-label">Issuance Date</div>
                    <div class="data-value">${student.date}</div>
                </div>
                <div class="data-row">
                    <div class="data-label">Global Reference ID</div>
                    <div class="data-value" style="font-family: monospace;">${student.id}</div>
                </div>
                <div style="margin-top: 2rem; text-align: center;">
                    <a href="generate-certificate.php?roll=${encodeURIComponent(student.id)}&download=1" target="_blank" class="btn-sophisticated" style="background: var(--secondary); border: none; text-decoration: none;">
                        Download PDF Certificate <i class="fas fa-file-pdf" style="margin-left: 8px;"></i>
                    </a>
                </div>

            `;
            showResult(content, 'success');
        } else {
            showResult(`<div style="text-align: center; color: #ef4444;"><i class="fas fa-times-circle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i> ${result.message}</div>`, 'error');
        }
    } catch (error) {
        console.error('Validation Error:', error);
        showResult('<div style="text-align: center; color: #ef4444;"><i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i> Service unavailable. Please try again later.</div>', 'error');
    }
}

function showResult(html, type) {
    const resultContainer = document.getElementById('validationResult');
    resultContainer.innerHTML = html;
    resultContainer.style.display = 'block';

    // In this sophisticated layout, we don't change background colors of the whole panel
    // but we can add minor subtle styles if needed.
    resultContainer.style.borderTopColor = type === 'success' ? 'var(--secondary)' : (type === 'error' ? '#ef4444' : 'var(--primary)');

    resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    // loadExcelData(); // No longer needed as data is fetched via API

    const certificateInput = document.getElementById('certificateId');
    if (certificateInput) {
        certificateInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                validateCertificate();
            }
        });
    }
});
