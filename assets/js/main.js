/* main.js - Ortiz Inmobiliaria */

const BASE = '';

// Sidebar toggle
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
document.addEventListener('click', e => {
    const sb = document.getElementById('sidebar');
    const btn = document.querySelector('.topbar-menu-btn');
    if (sb && !sb.contains(e.target) && btn && !btn.contains(e.target)) {
        sb.classList.remove('open');
    }
});

// Modales
function openModal(id) { const m = document.getElementById(id); if (m) m.classList.add('open'); }

function closeModal(id) { const m = document.getElementById(id); if (m) m.classList.remove('open'); }
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-ov')) e.target.classList.remove('open');
});

// Upload imagen preview
function setupUpload(inputId, previewId, zoneId) {
    const inp = document.getElementById(inputId);
    const prv = document.getElementById(previewId);
    const zn = document.getElementById(zoneId);
    if (!inp) return;
    zn.addEventListener('click', () => inp.click());
    inp.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const r = new FileReader();
            r.onload = e => {
                prv.src = e.target.result;
                prv.style.display = 'block';
                zn.style.display = 'none';
            };
            r.readAsDataURL(this.files[0]);
        }
    });
}

// Auto cerrar alertas
document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => {
        a.style.transition = 'opacity .5s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    }, 4500);
});

// Formatear dinero
function fmoney(n) {
    return 'S/ ' + parseFloat(n).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Confirmar acción
function confirm2(msg) { return confirm(msg || '¿Estás seguro?'); }