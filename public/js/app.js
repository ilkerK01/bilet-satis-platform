document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }
        });
    }, 5000);


    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Lütfen tüm gerekli alanları doldurun.', 'danger');
            }
        });
    });
});


function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
}

function selectSeat(seatNo) {
    document.querySelectorAll('.seat.selected').forEach(seat => {
        seat.classList.remove('selected');
        seat.classList.add('available');
    });

    const seatElement = document.querySelector(`[data-seat="${seatNo}"]`);
    if (seatElement && seatElement.classList.contains('available')) {
        seatElement.classList.remove('available');
        seatElement.classList.add('selected');

        const selectedSeatInput = document.getElementById('selectedSeat');
        if (selectedSeatInput) {
            selectedSeatInput.value = seatNo;
        }

        const buyButton = document.getElementById('buyButton');
        if (buyButton) {
            buyButton.disabled = false;
        }
    }
}


function checkCoupon() {
    const couponInput = document.getElementById('coupon_code');
    const couponButton = document.getElementById('check_coupon');
    
    if (couponInput && couponButton) {
        const couponCode = couponInput.value.trim();
        
        if (couponCode) {
            couponButton.textContent = 'Kontrol Ediliyor...';
            couponButton.disabled = true;
            
            setTimeout(() => {
                couponButton.textContent = 'Kontrol Et';
                couponButton.disabled = false;
                showAlert('Kupon kodu kontrol edildi.', 'info');
            }, 1000);
        }
    }
}


function validateDate(dateInput) {
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        dateInput.setCustomValidity('Geçmiş bir tarih seçemezsiniz.');
        return false;
    } else {
        dateInput.setCustomValidity('');
        return true;
    }
}


function formatPrice(price) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(price);
}


function showLoading(show = true) {
    let spinner = document.getElementById('loading-spinner');
    
    if (show && !spinner) {
        spinner = document.createElement('div');
        spinner.id = 'loading-spinner';
        spinner.className = 'position-fixed top-50 start-50 translate-middle';
        spinner.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        `;
        document.body.appendChild(spinner);
    } else if (!show && spinner) {
        spinner.remove();
    }
}
