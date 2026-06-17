document.addEventListener('DOMContentLoaded', function () {
    var modalOverlay = document.getElementById('addOppModal');
    var btnOpenModal = document.getElementById('btnOpenModal');
    var btnCloseModals = document.querySelectorAll('.btn-close-modal');

    function openModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (btnOpenModal) {
        btnOpenModal.addEventListener('click', openModal);
    }

    btnCloseModals.forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    if (modalOverlay) {
        modalOverlay.addEventListener('click', function (e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    }

    var filterBtns = document.querySelectorAll('.filter-btn');
    var tableRows = document.querySelectorAll('.opp-row');

    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) {
                b.classList.remove('active');
            });
            btn.classList.add('active');

            var filterValue = btn.getAttribute('data-filter');

            tableRows.forEach(function (row) {
                if (filterValue === 'all') {
                    row.style.display = '';
                    return;
                }

                if (row.getAttribute('data-status') === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
