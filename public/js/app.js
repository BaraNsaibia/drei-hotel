/**
 * Drei Hotel — Vanilla JavaScript Helper
 * Handles dynamic price calculation, date range constraints, and UI interactions
 */
document.addEventListener('DOMContentLoaded', function () {
    // 1. Live Price & Nights Calculator on Reservation Form
    const checkInInput = document.querySelector('input[name$="[checkIn]"]') || document.getElementById('reservation_checkIn');
    const checkOutInput = document.querySelector('input[name$="[checkOut]"]') || document.getElementById('reservation_checkOut');
    const priceDisplay = document.getElementById('calculated-price-display');
    const nightsDisplay = document.getElementById('calculated-nights-display');
    const pricePerNightAttr = document.getElementById('room-price-per-night');

    function updateLiveCalculation() {
        if (!checkInInput || !checkOutInput || !pricePerNightAttr) return;

        const checkInVal = checkInInput.value;
        const checkOutVal = checkOutInput.value;
        const pricePerNight = parseFloat(pricePerNightAttr.dataset.price || '0');

        if (checkInVal && checkOutVal) {
            const checkInDate = new Date(checkInVal);
            const checkOutDate = new Date(checkOutVal);

            // Update min date of check-out
            const nextDay = new Date(checkInDate);
            nextDay.setDate(nextDay.getDate() + 1);
            checkOutInput.min = nextDay.toISOString().split('T')[0];

            if (checkOutDate > checkInDate) {
                const diffTime = Math.abs(checkOutDate - checkInDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const total = (diffDays * pricePerNight).toFixed(2);

                if (nightsDisplay) nightsDisplay.textContent = diffDays + (diffDays > 1 ? ' nuits' : ' nuit');
                if (priceDisplay) priceDisplay.textContent = total + ' €';
            } else {
                if (nightsDisplay) nightsDisplay.textContent = '—';
                if (priceDisplay) priceDisplay.textContent = '— €';
            }
        }
    }

    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', updateLiveCalculation);
        checkOutInput.addEventListener('change', updateLiveCalculation);
        // Initial run if prefilled
        updateLiveCalculation();
    }

    // 2. Auto-close bootstrap alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }
        }, 6000);
    });
});
