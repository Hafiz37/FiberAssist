let allTickets = [];
let allLaporan = [];
let currentFilter = 'ALL';
let currentMainTab = 'tiket';
let currentUser = null;
let ticketInterval = null;

// Cache data pakar
let pakarGejalaList = [];
let pakarKerusakanList = [];
let pakarAturanList = [];

// Instance Chart.js
let chartDiagnosaInstance = null;
let chartTrenInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    checkSession();
});

// -------------------------------------------------------------
// 1. OTENTIKASI & CHECK SESSION
// -------------------------------------------------------------
async function checkSession() {
    try {
        const response = await fetch('/FiberAssist/api/auth.php?action=check');
        const data = await response.json();

        if (data.status === 'SUCCESS' && data.authenticated) {
            currentUser = data.user;
            showDashboardView();
        } else {
            showLoginView();
        }
    } catch (error) {
        console.error('Session check failed:', error);
        showLoginView();
    }
}

function showLoginView() {
    currentUser = null;
    if (ticketInterval) {
        clearInterval(ticketInterval);
        ticketInterval = null;
    }
    document.getElementById('loginOverlay').style.display = 'flex';
    document.getElementById('dashboardContainer').style.display = 'none';
}

function showDashboardView() {
    document.getElementById('loginOverlay').style.display = 'none';
    document.getElementById('dashboardContainer').style.display = 'block';

    // Update Header Profile Info
    if (currentUser) {
        document.getElementById('userFullName').innerText = currentUser.nama_lengkap;
        document.getElementById('userAvatar').innerText = currentUser.nama_lengkap.charAt(0).toUpperCase();
        
        const badgeEl = document.getElementById('userRoleBadge');
        badgeEl.innerText = currentUser.role;
        badgeEl.className = 'role-badge ' + (currentUser.role === 'ADMIN' ? 'role-admin' : 'role-cs');

        // Tampilkan Tombol Kelola User, Data Pakar, & Pengaturan hanya jika ADMIN
        const manageBtn = document.getElementById('btnManageUsers');
        const pakarBtn = document.getElementById('btnManagePakar');
        const settingsBtn = document.getElementById('btnManageSettings');
        if (currentUser.role === 'ADMIN') {
            if (manageBtn) manageBtn.style.display = 'inline-flex';
            if (pakarBtn) pakarBtn.style.display = 'inline-flex';
            if (settingsBtn) settingsBtn.style.display = 'inline-flex';
        } else {
            if (manageBtn) manageBtn.style.display = 'none';
            if (pakarBtn) pakarBtn.style.display = 'none';
            if (settingsBtn) settingsBtn.style.display = 'none';
        }
    }

    loadData();
    if (!ticketInterval) {
        ticketInterval = setInterval(loadData, 10000);
    }
}

async function handleLogin(event) {
    event.preventDefault();
    const alertEl = document.getElementById('loginAlert');
    alertEl.style.display = 'none';

    const usernameInput = document.getElementById('loginUsername').value.trim();
    const passwordInput = document.getElementById('loginPassword').value.trim();

    try {
        const response = await fetch('/FiberAssist/api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: usernameInput, password: passwordInput })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            currentUser = res.user;
            document.getElementById('loginForm').reset();
            showDashboardView();
        } else {
            alertEl.innerText = res.message || 'Login gagal, periksa username & password.';
            alertEl.style.display = 'block';
        }
    } catch (err) {
        console.error('Login error:', err);
        alertEl.innerText = 'Terjadi kesalahan koneksi server.';
        alertEl.style.display = 'block';
    }
}

async function handleLogout() {
    if (!confirm('Apakah Anda yakin ingin keluar dari sistem?')) return;

    try {
        await fetch('/FiberAssist/api/auth.php?action=logout', { method: 'POST' });
        showLoginView();
    } catch (err) {
        console.error('Logout error:', err);
        showLoginView();
    }
}

// -------------------------------------------------------------
// 2. MANAJEMEN TIKET GANGGUAN & LAPORAN
// -------------------------------------------------------------
async function loadData() {
    if (currentMainTab === 'tiket') {
        await loadTickets();
    } else {
        await loadLaporan();
    }
}

async function loadTickets() {
    try {
        const response = await fetch('/FiberAssist/api/tiket.php');
        const data = await response.json();

        if (data.status === 'SUCCESS') {
            allTickets = data.data;
            updateStats();
            renderTable();
            renderAnalyticsCharts();
        }
    } catch (error) {
        console.error('Error fetching tickets:', error);
    }
}

async function loadLaporan() {
    try {
        const response = await fetch('/FiberAssist/api/laporan.php');
        const data = await response.json();

        if (data.status === 'SUCCESS') {
            allLaporan = data.data;
            updateStatsLaporan();
            renderLaporanTable();
            renderAnalyticsChartsLaporan();
        }
    } catch (error) {
        console.error('Error fetching laporan:', error);
    }
}

function switchMainTab(tabName, btn) {
    currentMainTab = tabName;
    
    document.querySelectorAll('.main-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (tabName === 'tiket') {
        document.getElementById('statusFilterTabs').style.display = 'flex';
        currentFilter = 'ALL';
        document.querySelectorAll('#statusFilterTabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('#statusFilterTabs .tab-btn')[0].classList.add('active');
        loadTickets();
    } else {
        document.getElementById('statusFilterTabs').style.display = 'none';
        loadLaporan();
    }
}

function updateStats() {
    const statCards = document.querySelectorAll('.stat-card');
    
    if (statCards[0]) statCards[0].querySelector('h3').innerText = 'Total Tiket';
    if (statCards[1]) statCards[1].querySelector('h3').innerText = 'Belum Diproses (Open)';
    if (statCards[2]) statCards[2].querySelector('h3').innerText = 'Sedang Diproses';
    if (statCards[3]) statCards[3].querySelector('h3').innerText = 'Selesai (Resolved)';
    
    document.getElementById('stat-total').innerText = allTickets.length;
    document.getElementById('stat-open').innerText = allTickets.filter(t => t.status_tiket === 'OPEN').length;
    document.getElementById('stat-progress').innerText = allTickets.filter(t => t.status_tiket === 'IN_PROGRESS').length;
    document.getElementById('stat-resolved').innerText = allTickets.filter(t => t.status_tiket === 'RESOLVED').length;
}

function updateStatsLaporan() {
    const totalLaporan = allLaporan.length;
    const butuhTeknisi = allLaporan.filter(l => l.butuh_teknisi === 1).length;
    const mandiri = allLaporan.filter(l => l.butuh_teknisi === 0).length;
    
    const today = new Date().toISOString().split('T')[0];
    const hariIni = allLaporan.filter(l => l.updated_at && l.updated_at.startsWith(today)).length;

    document.getElementById('stat-total').innerText = totalLaporan;
    document.getElementById('stat-open').innerText = butuhTeknisi;
    document.getElementById('stat-progress').innerText = mandiri;
    document.getElementById('stat-resolved').innerText = hariIni;

    const statCards = document.querySelectorAll('.stat-card');
    if (statCards[0]) statCards[0].querySelector('h3').innerText = 'Total Laporan';
    if (statCards[1]) statCards[1].querySelector('h3').innerText = 'Butuh Teknisi';
    if (statCards[2]) statCards[2].querySelector('h3').innerText = 'Selesai Mandiri';
    if (statCards[3]) statCards[3].querySelector('h3').innerText = 'Laporan Hari Ini';
}

function renderAnalyticsCharts() {
    if (typeof Chart === 'undefined') return;

    const dataSource = currentMainTab === 'tiket' ? allTickets : allLaporan;

    // 1. Data Agregasi Diagnosa Kerusakan
    const countsByDiagnosa = {};
    dataSource.forEach(t => {
        const name = t.nama_kerusakan || 'Lainnya';
        countsByDiagnosa[name] = (countsByDiagnosa[name] || 0) + 1;
    });

    const diagnosaLabels = Object.keys(countsByDiagnosa);
    const diagnosaData = Object.values(countsByDiagnosa);

    const canvasDiagnosa = document.getElementById('chartDiagnosa');
    if (canvasDiagnosa) {
        if (chartDiagnosaInstance) {
            chartDiagnosaInstance.destroy();
        }

        const colors = ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#8b5cf6', '#ec4899', '#06b6d4'];

        chartDiagnosaInstance = new Chart(canvasDiagnosa.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: diagnosaLabels.length ? diagnosaLabels : ['Belum ada data'],
                datasets: [{
                    data: diagnosaData.length ? diagnosaData : [1],
                    backgroundColor: diagnosaLabels.length ? colors.slice(0, diagnosaLabels.length) : ['#334155'],
                    borderColor: '#172036',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } }
                    }
                }
            }
        });
    }

    // 2. Data Agregasi Tren Tanggal
    const countsByDate = {};
    dataSource.forEach(t => {
        const dateStr = new Date(currentMainTab === 'tiket' ? t.created_at : t.updated_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        countsByDate[dateStr] = (countsByDate[dateStr] || 0) + 1;
    });

    const dateLabels = Object.keys(countsByDate).reverse();
    const dateData = Object.values(countsByDate).reverse();

    const canvasTren = document.getElementById('chartTren');
    if (canvasTren) {
        if (chartTrenInstance) {
            chartTrenInstance.destroy();
        }

        chartTrenInstance = new Chart(canvasTren.getContext('2d'), {
            type: 'bar',
            data: {
                labels: dateLabels.length ? dateLabels : ['Hari Ini'],
                datasets: [{
                    label: currentMainTab === 'tiket' ? 'Jumlah Tiket Gangguan' : 'Jumlah Laporan',
                    data: dateData.length ? dateData : [0],
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                    y: { ticks: { color: '#94a3b8', stepSize: 1 }, grid: { color: 'rgba(255, 255, 255, 0.05)' }, beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
}

function renderAnalyticsChartsLaporan() {
    renderAnalyticsCharts();
}

function renderTable() {
    if (currentMainTab === 'tiket') {
        renderTicketTable();
    } else {
        renderLaporanTable();
    }
}

function renderTicketTable() {
    const tbody = document.getElementById('tableBody');
    const tableHeader = document.getElementById('tableHeader');
    const searchInput = document.getElementById('searchInput');
    
    if (!tbody || !tableHeader || !searchInput) {
        console.error('renderTicketTable: Required elements not found');
        return;
    }
    
    const searchVal = searchInput.value.toLowerCase();

    tableHeader.innerHTML = `
        <tr>
            <th>No. Tiket</th>
            <th>No. WA Pelanggan</th>
            <th>Hasil Diagnosa</th>
            <th>Tanggal Masuk</th>
            <th>Status Tiket</th>
            <th>Aksi / Update Status</th>
        </tr>
    `;

    let filtered = allTickets;

    if (currentFilter !== 'ALL') {
        filtered = filtered.filter(t => t.status_tiket === currentFilter);
    }

    if (searchVal) {
        filtered = filtered.filter(t => 
            t.no_tiket.toLowerCase().includes(searchVal) || 
            t.no_wa.toLowerCase().includes(searchVal) ||
            t.nama_kerusakan.toLowerCase().includes(searchVal)
        );
    }

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Tidak ada data tiket gangguan.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(t => `
        <tr>
            <td style="font-weight: 600;">#${t.no_tiket}</td>
            <td>${t.no_wa}</td>
            <td>${t.nama_kerusakan}</td>
            <td>${new Date(t.created_at).toLocaleString('id-ID')}</td>
            <td>
                <span class="badge badge-${t.status_tiket}">${t.status_tiket.replace('_', ' ')}</span>
            </td>
            <td style="white-space: nowrap;">
                <button class="btn-detail" onclick="showDetailTiket('${t.no_tiket}')">
                    <i class="fa-solid fa-eye"></i> Detail
                </button>
                <select class="status-select" onchange="updateTicketStatus('${t.no_tiket}', this.value)" style="margin-right: 0.3rem;">
                    <option value="OPEN" ${t.status_tiket === 'OPEN' ? 'selected' : ''}>OPEN</option>
                    <option value="IN_PROGRESS" ${t.status_tiket === 'IN_PROGRESS' ? 'selected' : ''}>IN PROGRESS</option>
                    <option value="RESOLVED" ${t.status_tiket === 'RESOLVED' ? 'selected' : ''}>RESOLVED</option>
                </select>
                <button class="btn-danger-sm" onclick="deleteTicket('${t.no_tiket}')" title="Hapus Tiket">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            </td>
        </tr>
    `).join('');
}

function renderLaporanTable() {
    const tbody = document.getElementById('tableBody');
    const tableHeader = document.getElementById('tableHeader');
    const searchInput = document.getElementById('searchInput');
    
    if (!tbody || !tableHeader || !searchInput) {
        console.error('renderLaporanTable: Required elements not found');
        return;
    }
    
    const searchVal = searchInput.value.toLowerCase();

    tableHeader.innerHTML = `
        <tr>
            <th>#</th>
            <th>No. WA Pelanggan</th>
            <th>Hasil Diagnosa</th>
            <th>Tanggal</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    `;

    let filtered = allLaporan;

    if (searchVal) {
        filtered = filtered.filter(l => 
            l.no_wa.toLowerCase().includes(searchVal) ||
            l.nama_kerusakan.toLowerCase().includes(searchVal) ||
            l.kode_kerusakan.toLowerCase().includes(searchVal)
        );
    }

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Tidak ada data laporan.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map((l, index) => {
        const statusBadge = l.butuh_teknisi === 1 
            ? '<span class="badge-teknisi"><i class="fa-solid fa-wrench"></i> Butuh Teknisi</span>'
            : '<span class="badge-mandiri"><i class="fa-solid fa-circle-check"></i> Selesai Mandiri</span>';
        
        const aksiTiket = l.no_tiket 
            ? `<button class="btn-detail" onclick="showDetailTiket('${l.no_tiket}')" style="margin-left:0.3rem;">
                    <i class="fa-solid fa-ticket"></i> Lihat Tiket
               </button>`
            : '';

        return `
            <tr>
                <td style="font-weight: 600;">${index + 1}</td>
                <td>${l.no_wa}</td>
                <td>
                    <span class="kode-diagnosa">${l.kode_kerusakan}</span>
                    <span class="diagnosa-highlight">${l.nama_kerusakan}</span>
                </td>
                <td>${new Date(l.updated_at).toLocaleString('id-ID')}</td>
                <td>${statusBadge}</td>
                <td style="white-space: nowrap;">
                    <button class="btn-detail" onclick="showDetailLaporan(${l.id_sesi})">
                        <i class="fa-solid fa-eye"></i> Detail
                    </button>
                    ${aksiTiket}
                </td>
            </tr>
        `;
    }).join('');
}

async function deleteTicket(no_tiket) {
    if (!confirm(`Apakah Anda yakin ingin menghapus tiket #${no_tiket}?`)) return;

    try {
        const response = await fetch('/FiberAssist/api/tiket.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ no_tiket })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            loadData();
        } else {
            alert('Gagal menghapus tiket: ' + res.message);
        }
    } catch (err) {
        console.error('Delete ticket error:', err);
    }
}

function filterStatus(status, btn) {
    currentFilter = status;
    document.querySelectorAll('.filter-tabs .tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderTable();
}

function filterSearch() {
    renderTable();
}

async function updateTicketStatus(no_tiket, newStatus) {
    try {
        const response = await fetch('/FiberAssist/api/tiket.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ no_tiket: no_tiket, status_tiket: newStatus })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            loadData();
        } else {
            alert('Gagal update status: ' + res.message);
        }
    } catch (err) {
        console.error('Update status error:', err);
    }
}

function showDetailTiket(no_tiket) {
    const t = allTickets.find(ticket => ticket.no_tiket === no_tiket);
    if (!t) return;

    document.getElementById('modalTitle').innerText = `Detail Tiket #${t.no_tiket}`;
    document.getElementById('modalNoWA').innerText = t.no_wa;
    document.getElementById('modalNoTiketRow').style.display = 'block';
    document.getElementById('modalNoTiket').innerText = '#' + t.no_tiket;
    document.getElementById('modalKerusakan').innerText = `${t.kode_kerusakan} - ${t.nama_kerusakan}`;
    document.getElementById('modalDetail').innerText = t.detail_diagnosa;
    document.getElementById('modalSolusi').innerText = t.solusi;
    document.getElementById('modalFaktaRow').style.display = 'none';

    document.getElementById('detailModal').style.display = 'flex';
}

function showDetailLaporan(id_sesi) {
    const l = allLaporan.find(lap => lap.id_sesi === id_sesi);
    if (!l) return;

    document.getElementById('modalTitle').innerText = `Detail Laporan #${l.id_sesi}`;
    document.getElementById('modalNoWA').innerText = l.no_wa;
    document.getElementById('modalNoTiketRow').style.display = l.no_tiket ? 'block' : 'none';
    document.getElementById('modalNoTiket').innerText = l.no_tiket ? '#' + l.no_tiket : '-';
    document.getElementById('modalKerusakan').innerText = `${l.kode_kerusakan} - ${l.nama_kerusakan}`;
    
    const faktaHtml = Array.isArray(l.fakta_terkumpul) && l.fakta_terkumpul.length > 0
        ? l.fakta_terkumpul.map(f => `<span class="fact-badge">${escapeHtml(f)}</span>`).join(' ')
        : '(Tidak ada fakta)';
    
    document.getElementById('modalFaktaRow').style.display = 'block';
    document.getElementById('modalFakta').innerHTML = faktaHtml;
    
    const detailText = `Rule: ${l.kode_rule || 'N/A'} | Status: ${l.butuh_teknisi === 1 ? 'Butuh Teknisi' : 'Mandiri'}`;
    document.getElementById('modalDetail').innerText = detailText;
    document.getElementById('modalSolusi').innerText = l.solusi;

    document.getElementById('detailModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}

// -------------------------------------------------------------
// 3. MANAJEMEN USER (KHUSUS ADMINISTRATOR)
// -------------------------------------------------------------
function openUserModal() {
    if (!currentUser || currentUser.role !== 'ADMIN') return;
    document.getElementById('userModalAlert').style.display = 'none';
    document.getElementById('userModal').style.display = 'flex';
    loadUsers();
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

async function loadUsers() {
    try {
        const response = await fetch('/FiberAssist/api/users.php');
        const res = await response.json();
        const tbody = document.getElementById('userTableBody');

        if (res.status === 'SUCCESS') {
            const users = res.data;
            if (users.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Belum ada user.</td></tr>`;
                return;
            }

            tbody.innerHTML = users.map(u => `
                <tr>
                    <td>#${u.id_user}</td>
                    <td style="font-weight: 600;">${escapeHtml(u.username)}</td>
                    <td>${escapeHtml(u.nama_lengkap)}</td>
                    <td>
                        <span class="role-badge ${u.role === 'ADMIN' ? 'role-admin' : 'role-cs'}">${u.role}</span>
                    </td>
                    <td>${new Date(u.created_at).toLocaleString('id-ID')}</td>
                    <td>
                        ${u.id_user === currentUser.id_user ? 
                            '<span style="font-size:0.75rem; color: var(--text-muted);">(Akun Aktif)</span>' : 
                            `<button class="btn-danger-sm" onclick="deleteUser(${u.id_user}, '${escapeHtml(u.username)}')"><i class="fa-solid fa-trash"></i> Hapus</button>`
                        }
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #f87171;">${res.message}</td></tr>`;
        }
    } catch (err) {
        console.error('Load users error:', err);
    }
}

async function handleAddUser(event) {
    event.preventDefault();
    const alertEl = document.getElementById('userModalAlert');
    alertEl.style.display = 'none';

    const username = document.getElementById('newUsername').value.trim();
    const nama_lengkap = document.getElementById('newNamaLengkap').value.trim();
    const password = document.getElementById('newPassword').value.trim();
    const role = document.getElementById('newRole').value;

    try {
        const response = await fetch('/FiberAssist/api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, nama_lengkap, password, role })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            document.getElementById('addUserForm').reset();
            loadUsers();
            alertEl.className = 'alert success';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        } else {
            alertEl.className = 'alert error';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        }
    } catch (err) {
        console.error('Add user error:', err);
        alertEl.className = 'alert error';
        alertEl.innerText = 'Terjadi kesalahan sistem.';
        alertEl.style.display = 'block';
    }
}

async function deleteUser(id_user, username) {
    if (!confirm(`Apakah Anda yakin ingin menghapus user "${username}"?`)) return;

    try {
        const response = await fetch('/FiberAssist/api/users.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_user })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            loadUsers();
        } else {
            alert('Gagal menghapus user: ' + res.message);
        }
    } catch (err) {
        console.error('Delete user error:', err);
    }
}

// -------------------------------------------------------------
// 4. MANAJEMEN DATA PAKAR (GEJALA, KERUSAKAN, ATURAN)
// -------------------------------------------------------------
function openPakarModal() {
    if (!currentUser || currentUser.role !== 'ADMIN') return;
    document.getElementById('pakarModalAlert').style.display = 'none';
    document.getElementById('pakarModal').style.display = 'flex';

    loadPakarGejala();
    loadPakarKerusakan();
    loadPakarAturan();
}

function closePakarModal() {
    document.getElementById('pakarModal').style.display = 'none';
}

function switchPakarSubtab(tabName, btn) {
    document.querySelectorAll('.subtab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.getElementById('pakarTabGejala').style.display = (tabName === 'gejala') ? 'block' : 'none';
    document.getElementById('pakarTabKerusakan').style.display = (tabName === 'kerusakan') ? 'block' : 'none';
    document.getElementById('pakarTabAturan').style.display = (tabName === 'aturan') ? 'block' : 'none';
}

// --- A. GEJALA ---
async function loadPakarGejala() {
    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=gejala');
        const res = await response.json();
        const tbody = document.getElementById('gejalaTableBody');

        if (res.status === 'SUCCESS') {
            pakarGejalaList = res.data;
            populateAturanDropdowns();

            if (pakarGejalaList.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:var(--text-muted);">Belum ada data gejala.</td></tr>`;
                return;
            }

            tbody.innerHTML = pakarGejalaList.map(g => `
                <tr>
                    <td style="font-weight:700; color:var(--primary);">${escapeHtml(g.kode_gejala)}</td>
                    <td style="font-weight:600;">${escapeHtml(g.nama_gejala)}</td>
                    <td style="font-size:0.85rem; color:var(--text-muted);">${escapeHtml(g.pertanyaan_wa)}</td>
                    <td>
                        <button class="btn-edit-sm" onclick="editGejala(${g.id_gejala}, '${escapeJsString(g.kode_gejala)}', '${escapeJsString(g.nama_gejala)}', '${escapeJsString(g.pertanyaan_wa)}')"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                        <button class="btn-danger-sm" onclick="deleteGejala(${g.id_gejala}, '${escapeJsString(g.kode_gejala)}')"><i class="fa-solid fa-trash"></i> Hapus</button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (err) {
        console.error('Load gejala error:', err);
    }
}

async function handleSaveGejala(event) {
    event.preventDefault();
    const alertEl = document.getElementById('pakarModalAlert');
    alertEl.style.display = 'none';

    const id_gejala = parseInt(document.getElementById('gejalaId').value);
    const kode_gejala = document.getElementById('gejalaKode').value.trim();
    const nama_gejala = document.getElementById('gejalaNama').value.trim();
    const pertanyaan_wa = document.getElementById('gejalaTanya').value.trim();

    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=gejala', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_gejala, kode_gejala, nama_gejala, pertanyaan_wa })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            resetGejalaForm();
            loadPakarGejala();
            alertEl.className = 'alert success';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        } else {
            alertEl.className = 'alert error';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        }
    } catch (err) {
        console.error('Save gejala error:', err);
    }
}

function editGejala(id, kode, nama, tanya) {
    document.getElementById('gejalaId').value = id;
    document.getElementById('gejalaKode').value = kode;
    document.getElementById('gejalaNama').value = nama;
    document.getElementById('gejalaTanya').value = tanya;
    document.getElementById('formGejalaTitle').innerHTML = `<i class="fa-solid fa-pen-to-square"></i> Edit Data Gejala (#${kode})`;
}

function resetGejalaForm() {
    document.getElementById('gejalaForm').reset();
    document.getElementById('gejalaId').value = 0;
    document.getElementById('formGejalaTitle').innerHTML = `<i class="fa-solid fa-plus-circle"></i> Tambah / Edit Data Gejala`;
}

async function deleteGejala(id_gejala, kode) {
    if (!confirm(`Apakah Anda yakin ingin menghapus data gejala ${kode}?`)) return;

    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=gejala', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_gejala })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            loadPakarGejala();
        } else {
            alert('Gagal hapus gejala: ' + res.message);
        }
    } catch (err) {
        console.error('Delete gejala error:', err);
    }
}

// --- B. KERUSAKAN ---
async function loadPakarKerusakan() {
    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=kerusakan');
        const res = await response.json();
        const tbody = document.getElementById('kerusakanTableBody');

        if (res.status === 'SUCCESS') {
            pakarKerusakanList = res.data;
            populateAturanDropdowns();

            if (pakarKerusakanList.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--text-muted);">Belum ada data kerusakan.</td></tr>`;
                return;
            }

            tbody.innerHTML = pakarKerusakanList.map(k => `
                <tr>
                    <td style="font-weight:700; color:var(--progress-color);">${escapeHtml(k.kode_kerusakan)}</td>
                    <td style="font-weight:600;">${escapeHtml(k.nama_kerusakan)}</td>
                    <td style="font-size:0.85rem; color:var(--text-muted);">${escapeHtml(k.solusi)}</td>
                    <td>
                        <span class="badge ${k.butuh_teknisi == 1 ? 'badge-OPEN' : 'badge-RESOLVED'}">
                            ${k.butuh_teknisi == 1 ? 'Ya (Teknisi)' : 'Mandiri'}
                        </span>
                    </td>
                    <td>
                        <button class="btn-edit-sm" onclick="editKerusakan(${k.id_kerusakan}, '${escapeJsString(k.kode_kerusakan)}', '${escapeJsString(k.nama_kerusakan)}', '${escapeJsString(k.solusi)}', ${k.butuh_teknisi})"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                        <button class="btn-danger-sm" onclick="deleteKerusakan(${k.id_kerusakan}, '${escapeJsString(k.kode_kerusakan)}')"><i class="fa-solid fa-trash"></i> Hapus</button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (err) {
        console.error('Load kerusakan error:', err);
    }
}

async function handleSaveKerusakan(event) {
    event.preventDefault();
    const alertEl = document.getElementById('pakarModalAlert');
    alertEl.style.display = 'none';

    const id_kerusakan = parseInt(document.getElementById('kerusakanId').value);
    const kode_kerusakan = document.getElementById('kerusakanKode').value.trim();
    const nama_kerusakan = document.getElementById('kerusakanNama').value.trim();
    const solusi = document.getElementById('kerusakanSolusi').value.trim();
    const butuh_teknisi = parseInt(document.getElementById('kerusakanTeknisi').value);

    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=kerusakan', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_kerusakan, kode_kerusakan, nama_kerusakan, solusi, butuh_teknisi })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            resetKerusakanForm();
            loadPakarKerusakan();
            alertEl.className = 'alert success';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        } else {
            alertEl.className = 'alert error';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        }
    } catch (err) {
        console.error('Save kerusakan error:', err);
    }
}

function editKerusakan(id, kode, nama, solusi, teknisi) {
    document.getElementById('kerusakanId').value = id;
    document.getElementById('kerusakanKode').value = kode;
    document.getElementById('kerusakanNama').value = nama;
    document.getElementById('kerusakanSolusi').value = solusi;
    document.getElementById('kerusakanTeknisi').value = teknisi;
    document.getElementById('formKerusakanTitle').innerHTML = `<i class="fa-solid fa-pen-to-square"></i> Edit Data Kerusakan (#${kode})`;
}

function resetKerusakanForm() {
    document.getElementById('kerusakanForm').reset();
    document.getElementById('kerusakanId').value = 0;
    document.getElementById('formKerusakanTitle').innerHTML = `<i class="fa-solid fa-plus-circle"></i> Tambah / Edit Data Kerusakan & Solusi`;
}

async function deleteKerusakan(id_kerusakan, kode) {
    if (!confirm(`Apakah Anda yakin ingin menghapus data kerusakan ${kode}?`)) return;

    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=kerusakan', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_kerusakan })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            loadPakarKerusakan();
        } else {
            alert('Gagal hapus kerusakan: ' + res.message);
        }
    } catch (err) {
        console.error('Delete kerusakan error:', err);
    }
}

// --- C. ATURAN (FORWARD CHAINING RULES) ---
async function loadPakarAturan() {
    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=aturan');
        const res = await response.json();
        const tbody = document.getElementById('aturanTableBody');

        if (res.status === 'SUCCESS') {
            pakarAturanList = res.data;

            if (pakarAturanList.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--text-muted);">Belum ada data aturan.</td></tr>`;
                return;
            }

            tbody.innerHTML = pakarAturanList.map(a => `
                <tr>
                    <td style="font-weight:700; color:var(--accent);">${escapeHtml(a.kode_rule)}</td>
                    <td><strong style="color:var(--primary);">${escapeHtml(a.id_gejala_1)}</strong> - ${escapeHtml(a.nama_gejala_1 || '')}</td>
                    <td><strong style="color:var(--primary);">${escapeHtml(a.id_gejala_2)}</strong> - ${escapeHtml(a.nama_gejala_2 || '')}</td>
                    <td><strong style="color:var(--progress-color);">${escapeHtml(a.id_kerusakan)}</strong> - ${escapeHtml(a.nama_kerusakan || '')}</td>
                    <td>
                        <button class="btn-edit-sm" onclick="editAturan(${a.id_aturan}, '${escapeJsString(a.kode_rule)}', '${escapeJsString(a.id_gejala_1)}', '${escapeJsString(a.id_gejala_2)}', '${escapeJsString(a.id_kerusakan)}')"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                        <button class="btn-danger-sm" onclick="deleteAturan(${a.id_aturan}, '${escapeJsString(a.kode_rule)}')"><i class="fa-solid fa-trash"></i> Hapus</button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (err) {
        console.error('Load aturan error:', err);
    }
}

function populateAturanDropdowns() {
    const selG1 = document.getElementById('aturanGejala1');
    const selG2 = document.getElementById('aturanGejala2');
    const selK = document.getElementById('aturanKerusakan');

    if (!selG1 || !selG2 || !selK) return;

    const gOptions = pakarGejalaList.map(g => `<option value="${g.kode_gejala}">${g.kode_gejala} - ${g.nama_gejala}</option>`).join('');
    const kOptions = pakarKerusakanList.map(k => `<option value="${k.kode_kerusakan}">${k.kode_kerusakan} - ${k.nama_kerusakan}</option>`).join('');

    selG1.innerHTML = `<option value="">-- Pilih Gejala 1 --</option>` + gOptions;
    selG2.innerHTML = `<option value="">-- Pilih Gejala 2 --</option>` + gOptions;
    selK.innerHTML = `<option value="">-- Pilih Diagnosa Kerusakan --</option>` + kOptions;
}

async function handleSaveAturan(event) {
    event.preventDefault();
    const alertEl = document.getElementById('pakarModalAlert');
    alertEl.style.display = 'none';

    const id_aturan = parseInt(document.getElementById('aturanId').value);
    const kode_rule = document.getElementById('aturanKodeRule').value.trim();
    const id_gejala_1 = document.getElementById('aturanGejala1').value;
    const id_gejala_2 = document.getElementById('aturanGejala2').value;
    const id_kerusakan = document.getElementById('aturanKerusakan').value;

    if (id_gejala_1 === id_gejala_2) {
        alertEl.className = 'alert error';
        alertEl.innerText = 'Gejala 1 dan Gejala 2 tidak boleh sama!';
        alertEl.style.display = 'block';
        return;
    }

    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=aturan', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_aturan, kode_rule, id_gejala_1, id_gejala_2, id_kerusakan })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            resetAturanForm();
            loadPakarAturan();
            alertEl.className = 'alert success';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        } else {
            alertEl.className = 'alert error';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        }
    } catch (err) {
        console.error('Save aturan error:', err);
    }
}

function editAturan(id, kode_rule, g1, g2, k) {
    document.getElementById('aturanId').value = id;
    document.getElementById('aturanKodeRule').value = kode_rule;
    document.getElementById('aturanGejala1').value = g1;
    document.getElementById('aturanGejala2').value = g2;
    document.getElementById('aturanKerusakan').value = k;
    document.getElementById('formAturanTitle').innerHTML = `<i class="fa-solid fa-pen-to-square"></i> Edit Aturan Rule (#${kode_rule})`;
}

function resetAturanForm() {
    document.getElementById('aturanForm').reset();
    document.getElementById('aturanId').value = 0;
    document.getElementById('formAturanTitle').innerHTML = `<i class="fa-solid fa-plus-circle"></i> Tambah / Edit Aturan Rule Forward Chaining`;
}

async function deleteAturan(id_aturan, kode_rule) {
    if (!confirm(`Apakah Anda yakin ingin menghapus aturan rule ${kode_rule}?`)) return;

    try {
        const response = await fetch('/FiberAssist/api/pakar.php?target=aturan', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_aturan })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            loadPakarAturan();
        } else {
            alert('Gagal hapus aturan: ' + res.message);
        }
    } catch (err) {
        console.error('Delete aturan error:', err);
    }
}

// -------------------------------------------------------------
// 5. RIWAYAT SESI DIAGNOSA WHATSAPP (SESSION LOGS)
// -------------------------------------------------------------
let allSesiLogs = [];

function openSesiModal() {
    document.getElementById('sesiModal').style.display = 'flex';
    loadSesiLogs();
}

function closeSesiModal() {
    document.getElementById('sesiModal').style.display = 'none';
}

async function loadSesiLogs() {
    try {
        const response = await fetch('/FiberAssist/api/sesi.php');
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            allSesiLogs = res.data;
            renderSesiTable();
        } else {
            document.getElementById('sesiTableBody').innerHTML = `<tr><td colspan="7" style="text-align:center; color:#f87171;">${res.message}</td></tr>`;
        }
    } catch (err) {
        console.error('Load sesi error:', err);
    }
}

function renderSesiTable() {
    const tbody = document.getElementById('sesiTableBody');
    const searchVal = document.getElementById('searchSesiInput').value.toLowerCase();

    let filtered = allSesiLogs;
    if (searchVal) {
        filtered = filtered.filter(s => s.no_wa.toLowerCase().includes(searchVal));
    }

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:var(--text-muted);">Belum ada riwayat sesi diagnosa.</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(s => {
        const factsArr = Array.isArray(s.fakta_terkumpul) ? s.fakta_terkumpul : [];
        const factsHtml = factsArr.length > 0 ? 
            factsArr.map(f => `<span class="fact-badge">${escapeHtml(f)}</span>`).join('') : 
            '<span style="color:var(--text-muted); font-size:0.8rem;">(Kosong)</span>';

        return `
            <tr>
                <td>#${s.id_sesi}</td>
                <td style="font-weight:600;">${escapeHtml(s.no_wa)}</td>
                <td>Step ${s.step}</td>
                <td>${factsHtml}</td>
                <td>
                    <span class="badge badge-${s.status}">${s.status}</span>
                </td>
                <td>${new Date(s.updated_at).toLocaleString('id-ID')}</td>
                <td>
                    <button class="btn-danger-sm" onclick="deleteSesiLog(${s.id_sesi})">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function filterSesiSearch() {
    renderSesiTable();
}

async function deleteSesiLog(id_sesi) {
    if (!confirm(`Apakah Anda yakin ingin menghapus log sesi #${id_sesi}?`)) return;

    try {
        const response = await fetch('/FiberAssist/api/sesi.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_sesi })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            loadSesiLogs();
        } else {
            alert('Gagal hapus log sesi: ' + res.message);
        }
    } catch (err) {
        console.error('Delete sesi error:', err);
    }
}

// -------------------------------------------------------------
// 6. KONFIGURASI WHATSAPP GATEWAY & WEBHOOK (SETTINGS)
// -------------------------------------------------------------
function openSettingsModal() {
    if (!currentUser || currentUser.role !== 'ADMIN') return;
    document.getElementById('settingsAlert').style.display = 'none';
    document.getElementById('settingsModal').style.display = 'flex';
    loadSettings();
}

function closeSettingsModal() {
    document.getElementById('settingsModal').style.display = 'none';
}

async function loadSettings() {
    try {
        const response = await fetch('/FiberAssist/api/settings.php');
        const res = await response.json();

        if (res.status === 'SUCCESS' && res.data) {
            document.getElementById('cfgPhoneId').value = res.data.wa_phone_number_id || '1224307307443062';
            document.getElementById('cfgWaToken').value = res.data.wa_access_token || '';
            document.getElementById('cfgWebhookUrl').value = res.data.webhook_url || '';
            document.getElementById('cfgEngineUrl').value = res.data.engine_url || '';
        }
    } catch (err) {
        console.error('Load settings error:', err);
    }
}

async function handleSaveSettings(event) {
    event.preventDefault();
    const alertEl = document.getElementById('settingsAlert');
    alertEl.style.display = 'none';

    const wa_phone_number_id = document.getElementById('cfgPhoneId').value.trim();
    const wa_access_token = document.getElementById('cfgWaToken').value.trim();
    const webhook_url = document.getElementById('cfgWebhookUrl').value.trim();
    const engine_url = document.getElementById('cfgEngineUrl').value.trim();

    try {
        const response = await fetch('/FiberAssist/api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ wa_phone_number_id, wa_access_token, webhook_url, engine_url })
        });
        const res = await response.json();

        if (res.status === 'SUCCESS') {
            alertEl.className = 'alert success';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        } else {
            alertEl.className = 'alert error';
            alertEl.innerText = res.message;
            alertEl.style.display = 'block';
        }
    } catch (err) {
        console.error('Save settings error:', err);
        alertEl.className = 'alert error';
        alertEl.innerText = 'Terjadi kesalahan saat menyimpan pengaturan.';
        alertEl.style.display = 'block';
    }
}

function toggleTokenVisibility() {
    const input = document.getElementById('cfgWaToken');
    const icon = document.getElementById('eyeTokenIcon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

function copyWebhookUrl() {
    const urlInput = document.getElementById('cfgWebhookUrl');
    if (!urlInput.value) return;

    navigator.clipboard.writeText(urlInput.value).then(() => {
        alert('URL Webhook berhasil disalin ke clipboard:\n' + urlInput.value);
    }).catch(err => {
        urlInput.select();
        document.execCommand('copy');
        alert('URL Webhook berhasil disalin!');
    });
}

// -------------------------------------------------------------
// HELPER FUNCTIONS
// -------------------------------------------------------------
function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escapeJsString(str) {
    return String(str || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
}
